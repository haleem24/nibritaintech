<?php

namespace App\Http\Livewire;
    use App\Http\Controllers\BackMarketAPIController;
    use Livewire\Component;
    use App\Models\Admin_model;
    use App\Models\Variation_model;
    use App\Models\Products_model;
    use App\Models\Stock_model;
    use App\Models\Order_model;
    use App\Models\Order_item_model;
    use App\Models\Order_status_model;
    use App\Models\Customer_model;
    use App\Models\Currency_model;
    use App\Models\Country_model;
    use App\Models\Storage_model;
    use Carbon\Carbon;
    use App\Exports\OrdersExport;
    use App\Exports\PickListExport;
    use App\Exports\LabelsExport;
    use App\Exports\DeliveryNotesExport;
    use App\Exports\OrdersheetExport;
use Illuminate\Support\Facades\DB;
    use Maatwebsite\Excel\Facades\Excel;
    use TCPDF;
    use App\Mail\InvoiceMail;
use App\Models\Account_transaction_model;
use App\Models\Color_model;
use App\Models\Grade_model;
use App\Models\Order_issue_model;
use App\Models\Process_model;
use App\Models\Process_stock_model;
use App\Models\Product_color_merge_model;
use App\Models\Product_storage_sort_model;
use App\Models\Stock_operations_model;
use App\Models\Stock_movement_model;
use App\Models\Vendor_grade_model;
use Illuminate\Support\Facades\Mail;
use TCPDF_FONTS;

class Order extends Component
{

    public function mount()
    {
        $user_id = session('user_id');
        if($user_id == NULL){
            return redirect('index');
        }
    }
    public function render()
    {
        // ini_set('memory_limit', '2560M');
        $data['title_page'] = "Sales";
        session()->put('page_title', $data['title_page']);
        $data['storages'] = Storage_model::pluck('name','id');
        $data['colors'] = Color_model::pluck('name','id');
        $data['grades'] = Grade_model::pluck('name','id');

        $data['currencies'] = Currency_model::pluck('sign', 'id');
        $data['last_hour'] = Carbon::now()->subHour(2);
        $data['admins'] = Admin_model::pluck('first_name','id');
        $data['testers'] = Admin_model::where('role_id',7)->pluck('last_name');
        $user_id = session('user_id');
        $data['user_id'] = $user_id;
        $data['pending_orders_count'] = Order_model::where('order_type_id',3)->where('status',2)->count();
        $data['missing_charge_count'] = Order_model::where('order_type_id',3)->whereNot('status',2)->whereNull('charges')->where('processed_at','<=',now()->subHours(12))->count();
        $data['missing_processed_at_count'] = Order_model::where('order_type_id',3)->whereIn('status',[3,6])->where('processed_at',null)->count();
        $data['order_statuses'] = Order_status_model::pluck('name','id');
        if(request('per_page') != null){
            $per_page = request('per_page');
        }else{
            $per_page = 10;
        }
        // if(request('care')){
        //     foreach(Order_model::where('status',2)->pluck('reference_id') as $pend){
        //         $this->recheck($pend);
        //     }
        // }

        switch (request('sort')){
            case 2: $sort = "orders.reference_id"; $by = "ASC"; break;
            case 3: $sort = "products.model"; $by = "DESC"; break;
            case 4: $sort = "products.model"; $by = "ASC"; break;
            default: $sort = "orders.reference_id"; $by = "DESC";
        }
        if(request('start_date') != '' && request('start_time') != ''){
            $start_date = request('start_date').' '.request('start_time');
        }elseif(request('start_date') != ''){
            $start_date = request('start_date');
        }else{
            $start_date = 0;
        }

        if(request('end_date') != '' && request('end_time') != ''){
            $end_date = request('end_date').' '.request('end_time');
        }elseif(request('end_date') != ''){
            $end_date = request('end_date')." 23:59:59";
        }else{
            $end_date = now();
        }

        $orders = Order_model::with(['customer','customer.orders','order_items','order_items.variation','order_items.variation.product', 'order_items.variation.grade_id', 'order_items.stock'])
        // ->where('orders.order_type_id',3)

        ->when(request('type') == '', function ($q) {
            return $q->where('orders.order_type_id',3);
        })
        ->when(request('items') == 1, function ($q) {
            return $q->whereHas('order_items', operator: '>', count: 1);
        })

        ->when(request('start_date') != '', function ($q) use ($start_date) {
            if(request('adm') > 0){
                return $q->where('orders.processed_at', '>=', $start_date);
            }else{
                return $q->where('orders.created_at', '>=', $start_date);

            }
        })
        ->when(request('end_date') != '', function ($q) use ($end_date) {
            if(request('adm') > 0){
                return $q->where('orders.processed_at', '<=',$end_date)->orderBy('orders.processed_at','desc');
            }else{
                return $q->where('orders.created_at', '<=',$end_date);
            }
        })
        ->when(request('status') != '', function ($q) {
            return $q->where('orders.status', request('status'));
        })
        ->when(request('adm') != '', function ($q) {
            if(request('adm') == 0){
                return $q->where('orders.processed_by', null);
            }
            return $q->where('orders.processed_by', request('adm'));
        })
        ->when(request('care') != '', function ($q) {
            return $q->whereHas('order_items', function ($query) {
                $query->where('care_id', '!=', null);
            });
        })
        ->when(request('missing') == 'reimburse', function ($q) {
            return $q->whereHas('order_items.linked_child', function ($qu) {
                $qu->whereHas('order', function ($q) {
                    $q->where('status', '!=', 1);
                });
            })->where('status', 3);
        })
        ->when(request('missing') == 'refund', function ($q) {
            return $q->whereDoesntHave('order_items.linked_child')->wherehas('order_items.stock', function ($q) {
                $q->where('status', '!=', null);
            })->where('status', 6);
        })
        ->when(request('missing') == 'charge', function ($q) {
            return $q->whereNot('status', 2)->whereNull('charges')->where('processed_at', '<=', now()->subHours(12));
        })
        ->when(request('missing') == 'scan', function ($q) {
            return $q->whereIn('status', [3,6])->whereNull('scanned')->where('processed_at', '<=', now()->subHours(48));
        })
        ->when(request('missing') == 'purchase', function ($q) {
            return $q->whereHas('order_items.stock', function ($q) {
                $q->whereNull('status');
            });
        })
        ->when(request('missing') == 'processed_at', function ($q) {
            return $q->whereIn('status', [3,6])->whereNull('processed_at');
        })
        ->when(request('order_id') != '', function ($q) {
            if(str_contains(request('order_id'),'<')){
                $order_ref = str_replace('<','',request('order_id'));
                return $q->where('orders.reference_id', '<', $order_ref);
            }elseif(str_contains(request('order_id'),'>')){
                $order_ref = str_replace('>','',request('order_id'));
                return $q->where('orders.reference_id', '>', $order_ref);
            }elseif(str_contains(request('order_id'),'<=')){
                $order_ref = str_replace('<=','',request('order_id'));
                return $q->where('orders.reference_id', '<=', $order_ref);
            }elseif(str_contains(request('order_id'),'>=')){
                $order_ref = str_replace('>=','',request('order_id'));
                return $q->where('orders.reference_id', '>=', $order_ref);
            }elseif(str_contains(request('order_id'),'-')){
                $order_ref = explode('-',request('order_id'));
                return $q->whereBetween('orders.reference_id', $order_ref);
            }elseif(str_contains(request('order_id'),',')){
                $order_ref = explode(',',request('order_id'));
                return $q->whereIn('orders.reference_id', $order_ref);
            }elseif(str_contains(request('order_id'),' ')){
                $order_ref = explode(' ',request('order_id'));
                return $q->whereIn('orders.reference_id', $order_ref);
            }else{
                return $q->where('orders.reference_id', 'LIKE', request('order_id') . '%');
            }
        })
        ->when(request('sku') != '', function ($q) {
            return $q->whereHas('order_items.variation', function ($q) {
                $q->where('sku', 'LIKE', '%' . request('sku') . '%');
            });
        })
        ->when(request('imei') != '', function ($q) {
            if(str_contains(request('imei'),' ')){
                $imei = explode(' ',request('imei'));
                return $q->whereHas('order_items.stock', function ($q) use ($imei) {
                    $q->whereIn('imei', $imei);
                });
            }else{

                return $q->whereHas('order_items.stock', function ($q) {
                    $q->where('imei', 'LIKE', '%' . request('imei') . '%');
                });
            }
        })
        ->when(request('currency') != '', function ($q) {
            return $q->where('currency', request('currency'));
        })
        ->when(request('tracking_number') != '', function ($q) {
            if(strlen(request('tracking_number')) == 21){
                $tracking = substr(request('tracking_number'),1);
            }else{
                $tracking = request('tracking_number');
            }
            return $q->where('tracking_number', 'LIKE', '%' . $tracking . '%');
        })
        ->when(request('with_stock') == 2, function ($q) {
            return $q->whereHas('order_items', function ($q) {
                $q->where('stock_id', 0);
            });
        })
        ->when(request('with_stock') == 1, function ($q) {
            return $q->whereHas('order_items', function ($q) {
                $q->where('stock_id','>', 0);
            });
        })
        // ->orderBy($sort, $by) // Order by variation name
        // ->when(request('sort') == 4, function ($q) {
        //     return $q->whereHas('order_items.variation.product', function ($q) {
        //         $q->orderBy('model', 'ASC');
        //     })->whereHas('order_items.variation', function ($q) {
        //         $q->orderBy('variation.storage', 'ASC');
        //     })->whereHas('order_items.variation', function ($q) {
        //         $q->orderBy('variation.color', 'ASC');
        //     })->whereHas('order_items.variation', function ($q) {
        //         $q->orderBy('variation.grade', 'ASC');
        //     });

        ->when(request('sort') == 4, function ($q) {
            return $q->join('order_items', 'order_items.order_id', '=', 'orders.id')
                ->join('variation', 'order_items.variation_id', '=', 'variation.id')
                ->join('products', 'variation.product_id', '=', 'products.id')
                ->where(['orders.deleted_at' => null, 'order_items.deleted_at' => null, 'variation.deleted_at' => null, 'products.deleted_at' => null])
                ->orderBy('products.model', 'ASC')
                ->orderBy('variation.storage', 'ASC')
                ->orderBy('variation.color', 'ASC')
                ->orderBy('variation.grade', 'ASC')
                ->orderBy('variation.sku', 'ASC')
                ->select('orders.id','orders.reference_id','orders.customer_id','orders.delivery_note_url','orders.label_url','orders.tracking_number','orders.status','orders.processed_by','orders.created_at','orders.processed_at');
        })
        // })
        ->when(request('adm') > 0, function ($q) {
            return $q->orderBy('orders.processed_at', 'desc');
        })
        ->orderBy('orders.reference_id', 'desc'); // Secondary order by reference_id


        if(request('bulk_invoice') && request('bulk_invoice') == 1){
            $order_ids = [];
            ini_set('max_execution_time', 300);
            $data['orders2'] = $orders
            ->get();
            foreach($data['orders2'] as $order){
                if(!in_array($order->reference_id,$order_ids)){
                    $order_ids[] = $order->reference_id;
                }else{
                    continue;
                }
                $data2 = [
                    'order' => $order,
                    'customer' => $order->customer,
                    'orderItems' => $order->order_items,
                ];

                Mail::mailer('no-reply')->to($order->customer->email)->send(new InvoiceMail($data2));
                // $recipientEmail = $order->customer->email;
                // $subject = 'Invoice for Your Recent Purchase';

                // app(GoogleController::class)->sendEmailInvoice($recipientEmail, $subject, new InvoiceMail($data2));
                sleep(2);

            }
            // return redirect()->back();

        }

        $data['orders'] = $orders
        ->paginate($per_page)
        ->onEachSide(5)
        ->appends(request()->except('page'));


        if(request('missing') == 'processed_at'){
            $reference_ids = $data['orders']->pluck('reference_id');
            foreach($reference_ids as $ref){
                $this->recheck($ref);
            }
        }
        $ors = explode(' ',request('order_id'));
        if(count($data['orders']) != count($ors) && request('order_id')){
            foreach($ors as $or){
                $this->recheck($or);
            }
        }
        // dd($data['orders']);
        return view('livewire.order')->with($data);
    }
    public function mark_scanned($id)
    {
        $order = Order_model::find($id);
        if($order->scanned == null && ($order->status == 3 || $order->status == 6) && ($order->label_url == null || $order->reference != null)){
            $order->scanned = 1;
            $order->save();
        }elseif($order->scanned == null && request('force') == 1){
            $order->scanned = 2;
            $order->save();
        }

        session()->flash('message', 'Order marked as scanned');
        session()->put('success', 'Order marked as scanned');
        return redirect()->back();
    }

    public function sales_allowed()
    {
        $data['title_page'] = "Sales (Admin)";
        session()->put('page_title', $data['title_page']);

        $data['grades'] = Grade_model::all();
        $data['last_hour'] = Carbon::now()->subHour(72);
        $data['admins'] = Admin_model::where('id','!=',1)->get();
        $data['testers'] = Admin_model::where('role_id',7)->pluck('last_name');
        $user_id = session('user_id');
        $data['user_id'] = $user_id;
        $data['pending_orders_count'] = Order_model::where('order_type_id',3)->where('status',2)->count();
        $data['order_statuses'] = Order_status_model::get();
        if(request('per_page') != null){
            $per_page = request('per_page');
        }else{
            $per_page = 10;
        }
        // if(request('care')){
        //     foreach(Order_model::where('status',2)->pluck('reference_id') as $pend){
        //         $this->recheck($pend);
        //     }
        // }

        switch (request('sort')){
            case 2: $sort = "orders.reference_id"; $by = "ASC"; break;
            case 3: $sort = "products.model"; $by = "DESC"; break;
            case 4: $sort = "products.model"; $by = "ASC"; break;
            default: $sort = "orders.reference_id"; $by = "DESC";
        }

        $orders = Order_model::with(['order_items','order_items.variation', 'order_items.variation.grade_id', 'order_items.stock'])
        ->where('order_type_id',3)
        ->when(request('start_date') != '', function ($q) {
            if(request('adm') > 0){
                return $q->where('processed_at', '>=', request('start_date', 0));
            }else{
                return $q->where('created_at', '>=', request('start_date', 0));

            }
        })
        ->when(request('end_date') != '', function ($q) {
            if(request('adm') > 0){
                return $q->where('processed_at', '<=', request('end_date', 0) . " 23:59:59")->orderBy('processed_at','desc');
            }else{
                return $q->where('created_at', '<=', request('end_date', 0) . " 23:59:59");
            }
        })
        ->when(request('status') != '', function ($q) {
            return $q->where('status', request('status'));
        })
        ->when(request('adm') != '', function ($q) {
            if(request('adm') == 0){
                return $q->where('processed_by', null);
            }
            return $q->where('processed_by', request('adm'));
        })
        ->when(request('care') != '', function ($q) {
            return $q->whereHas('order_items', function ($query) {
                $query->where('care_id', '!=', null);
            });
        })
        ->when(request('order_id') != '', function ($q) {
            return $q->where('reference_id', 'LIKE', request('order_id') . '%');
        })
        ->when(request('sku') != '', function ($q) {
            return $q->whereHas('order_items.variation', function ($q) {
                $q->where('sku', 'LIKE', '%' . request('sku') . '%');
            });
        })
        ->when(request('imei') != '', function ($q) {
            return $q->whereHas('order_items.stock', function ($q) {
                $q->where('imei', 'LIKE', '%' . request('imei') . '%');
            });
        })
        ->when(request('tracking_number') != '', function ($q) {
            if(strlen(request('tracking_number')) == 21){
                $tracking = substr(request('tracking_number'),1);
            }else{
                $tracking = request('tracking_number');
            }
            return $q->where('tracking_number', 'LIKE', '%' . $tracking . '%');
        })
        ->orderBy($sort, $by) // Order by variation name
        ->when(request('sort') == 4, function ($q) {
            return $q->whereHas('order_items.variation.product', function ($q) {
                $q->orderBy('model', 'ASC');
            })->whereHas('order_items.variation', function ($q) {
                $q->orderBy('variation.storage', 'ASC');
            })->whereHas('order_items.variation', function ($q) {
                $q->orderBy('variation.color', 'ASC');
            })->whereHas('order_items.variation', function ($q) {
                $q->orderBy('variation.grade', 'ASC');
            });

        })
        ->orderBy('reference_id', 'desc'); // Secondary order by reference_id
        if(request('bulk_invoice') && request('bulk_invoice') == 1){

            $data['orders2'] = $orders
            ->get();
            foreach($data['orders2'] as $order){

                $data2 = [
                    'order' => $order,
                    'customer' => $order->customer,
                    'orderItems' => $order->order_items,
                ];
                Mail::to($order->customer->email)->send(new InvoiceMail($data2));

            }
            // return redirect()->back();

        }
        $data['orders'] = $orders
        ->paginate($per_page)
        ->onEachSide(5)
        ->appends(request()->except('page'));

        if(count($data['orders']) == 0 && request('order_id')){
            $this->recheck(request('order_id'));
        }
        // dd($data['orders']);
        return view('livewire.sales_allowed')->with($data);
    }
    public function purchase()
    {

        $data['title_page'] = "Purchases";
        session()->put('page_title', $data['title_page']);
        $data['latest_reference'] = Order_model::where('order_type_id',1)->orderBy('reference_id','DESC')->first()->reference_id ?? 9998;
        $data['vendors'] = Customer_model::whereNotNull('is_vendor')->pluck('first_name','id');
        $data['order_statuses'] = Order_status_model::get();
        if(request('per_page') != null){
            $per_page = request('per_page');
        }else{
            $per_page = 10;
        }

        $data['orders'] = Order_model::with('order_items', 'order_issues')->withCount('order_items_available as available_stock')
        // select(
        //     'orders.id',
        //     'orders.reference_id',
        //     'orders.customer_id',
        //     DB::raw('SUM(order_items.price) as total_price'),
        //     DB::raw('COUNT(order_items.id) as total_quantity'),
        //     DB::raw('COUNT(CASE WHEN stock.status = 1 THEN order_items.id END) as available_stock'),
        //     'orders.status',
        //     'orders.created_at')
        ->where('orders.order_type_id', 1)
        // ->join('order_items', 'orders.id', '=', 'order_items.order_id')
        // ->join('stock', 'order_items.stock_id', '=', 'stock.id')
        ->when(request('start_date'), function ($q) {
            return $q->where('orders.created_at', '>=', request('start_date'));
        })
        ->when(request('end_date'), function ($q) {
            return $q->where('orders.created_at', '<=', request('end_date') . " 23:59:59");
        })
        ->when(request('order_id'), function ($q) {
            return $q->where('orders.reference_id', 'LIKE', request('order_id') . '%');
        })
        ->when(request('status'), function ($q) {
            return $q->where('orders.status', request('status'));
        })
        ->when(request('status') == 3 && request('stock') == 0, function ($query) {
            return $query->having('available_stock', '=', 0);
        })
        ->when(request('status') == 3 && request('stock') == 1, function ($query) {
            return $query->having('available_stock', '>', 0);
        })
        // ->when(!request('stock'), function ($query) {
        //     return $query->having('available_stock');
        // })
        // ->when(request('stock'), function ($q) {
        //     if (request('stock') == 0) {
        //         return $q->havingRaw('COUNT(CASE WHEN stock.status = 1 THEN order_items.id END) = 0');
        //     } else {
        //         return $q->havingRaw('COUNT(CASE WHEN stock.status = 1 THEN order_items.id END) > 0');
        //     }
        // })

        // ->groupBy('orders.id', 'orders.reference_id', 'orders.customer_id', 'orders.status', 'orders.created_at')
        ->orderBy('orders.reference_id', 'desc') // Secondary order by reference_id
        ->paginate($per_page)
        ->onEachSide(5)
        ->appends(request()->except('page'));


        // dd($data['orders']);
        return view('livewire.purchase')->with($data);
    }
    public function purchase_approve($order_id){
        $order = Order_model::find($order_id);
        $order->reference = request('reference');
        $order->tracking_number = request('tracking_number');
        if(request('customer_id') != null){
            $order->customer_id = request('customer_id');
        }
        if(request('approve') == 1){
            $order->status = 3;
            $order->processed_at = now()->format('Y-m-d H:i:s');
        }
        $order->save();

        $transaction = Account_transaction_model::firstOrNew(['order_id'=>$order_id]);
        if($transaction->id == null && $order->status == 3){
            $transaction->amount = $order->order_items->sum('price');
            $transaction->currency = $order->currency;
            $transaction->exchange_rate = $order->exchange_rate;
            $transaction->customer_id = $order->customer_id;
            $transaction->transaction_type_id = 1;
            $transaction->status = 1;
            $transaction->description = $order->reference;
            $transaction->reference_id = $order->reference_id;
            $transaction->created_by = session('user_id');
            // $transaction->created_at = $order->created_at;

            $transaction->save();
        }elseif($transaction->id != null && $order->status == 3){
            $transaction->status = 2;
            $transaction->save();
        }

        if(request('approve') == 1){
            return redirect()->back();
        }else{
            return "Updated";
        }
    }
    public function purchase_revert_status($order_id){
        $order = Order_model::find($order_id);
        $order->status -= 1;
        $order->save();
        return redirect()->back();
    }
    public function delete_order($order_id){

        $stock = Stock_model::where(['order_id'=>$order_id,'status'=>2])->first();
        if($stock != null){
            session()->put('error', "Order cannot be deleted");
            return redirect()->back();
        }
        $items = Order_item_model::where('order_id',$order_id)->get();
        foreach($items as $orderItem){
            if($orderItem->stock){
                // Access the variation through orderItem->stock->variation
                $variation = $orderItem->stock->variation;

                // If a variation record exists and either product_id or sku is not null
                if ($variation->stock == 1 && $variation->product_id == null && $variation->sku == null) {
                    // Decrement the stock by 1

                    // Save the variation record
                    $variation->delete();
                } else {
                    $variation->stock -= 1;
                    // No variation record found or product_id and sku are both null, delete the order item
                }
                $stock = Stock_model::find($orderItem->stock_id);
                if($stock->status == 1){
                    $stock->delete();
                }else{
                    $stock->order_id = null;
                    $stock->status = null;
                    $stock->save();
                }
            }
            $orderItem->delete();
        }
        Order_model::where('id',$order_id)->delete();
        Order_issue_model::where('order_id',$order_id)->delete();
        return redirect(url('purchase'));
    }
    public function delete_sale_order($order_id){

        $items = Order_item_model::where('order_id',$order_id)->get();
        foreach($items as $orderItem){
            if($orderItem->stock){
                // Access the variation through orderItem->stock->variation
                $variation = $orderItem->stock->variation;

                // If a variation record exists and either product_id or sku is not null
                if ($variation->stock == 1 && $variation->product_id == null && $variation->sku == null) {
                    // Decrement the stock by 1

                    // Save the variation record
                    $variation->delete();
                } else {
                    $variation->stock += 1;
                    // No variation record found or product_id and sku are both null, delete the order item
                }
                // $stock = Stock_model::find($orderItem->stock_id);
                // if($stock->status == 1){
                //     $stock->delete();
                // }else{
                //     $stock->order_id = null;
                //     $stock->status = null;
                //     $stock->save();
                // }
            }
            $orderItem->delete();
        }
        Order_model::where('id',$order_id)->delete();
        Order_issue_model::where('order_id',$order_id)->delete();
        return redirect(url('purchase'));
    }
    public function delete_order_item($item_id){

        $orderItem = Order_item_model::find($item_id);

        if($orderItem == null){
            session()->put('error', "Order Item not found");
            return redirect()->back();
        }
        if($orderItem->stock->status == 2){
            session()->put('error', "Order Item cannot be deleted");
            return redirect()->back();
        }
        // Access the variation through orderItem->stock->variation
        $variation = $orderItem->stock->variation;

        $variation->stock -= 1;
        $variation->save();

        // No variation record found or product_id and sku are both null, delete the order item

        // $orderItem->stock->delete();
        $stock = Stock_model::find($orderItem->stock_id);
        $lp_item = Order_item_model::where('stock_id',$orderItem->stock_id)->where('order_id','!=',$orderItem->order_id)
        ->whereHas('order', function ($query) {
            $query->where('order_type_id', 1);
        })->orderBy('id','desc')->first();

        if($lp_item != null){
            $stock->order_id = $lp_item->order_id;
            $stock->save();
        }else{
            if($stock->status == 1){
                $stock->delete();
            }else{
                $stock->order_id = null;
                $stock->status = null;
                $stock->save();
            }
        }
        $orderItem->delete();

        return redirect()->back();
    }
    public function purchase_detail($order_id){
        // if previous url contains url('purchase') then set session previous to url()->previous()
        if(str_contains(url()->previous(),url('purchase')) && !str_contains(url()->previous(),'detail')){
            session()->put('previous', url()->previous());
        }


        DB::statement("SET SESSION group_concat_max_len = 1000000;");
        $data['title_page'] = "Purchase Detail";
        session()->put('page_title', $data['title_page']);
        $data['vendors'] = Customer_model::whereNotNull('is_vendor')->pluck('company','id');
        $data['products'] = Products_model::pluck('model','id');
        $data['storages'] = Storage_model::pluck('name','id');
        $data['colors'] = Color_model::pluck('name','id');
        $data['grades'] = Grade_model::pluck('name','id');

        if(request('summery') == 1){


            // Retrieve variations with related stocks
            $sold_stocks = Variation_model::whereHas('stocks', function ($query) use ($order_id) {
                $query->where('order_id', $order_id)->where('status', 2);
            })
            ->withCount([
                'stocks as quantity' => function ($query) use ($order_id) {
                    $query->where('order_id', $order_id)->where('status', 2);
                }
            ])
            ->with([
                'stocks' => function ($query) use ($order_id) {
                    $query->where('order_id', $order_id)->where('status', 2);
                }
            ])
            ->get(['product_id', 'storage']);

            // Process the retrieved data to get stock IDs
            $result = $sold_stocks->map(function ($variation) {
                $stocks = $variation->stocks;

                // Collect all stock IDs
                $stockIds = $stocks->pluck('id');

                return [
                    'product_id' => $variation->product_id,
                    'storage' => $variation->storage,
                    'quantity' => $variation->quantity, // Use quantity from withCount
                    'stock_ids' => $stockIds->toArray() // Convert collection to array
                ];
            });

            // Group the results by product_id and storage
            $groupedResult = $result->groupBy(function ($item) {
                return $item['product_id'] . '.' . $item['storage'];
            })->map(function ($items, $key) {
                list($product_id, $storage) = explode('.', $key);

                // Merge all stock IDs for the group
                $stockIds = $items->flatMap(function ($item) {
                    return $item['stock_ids'];
                })->unique()->values()->toArray(); // Convert to array

                // Sum the quantity
                $quantity = $items->sum('quantity'); // Sum the quantities

                return [
                    'product_id' => $product_id,
                    'storage' => $storage,
                    'quantity' => $quantity,
                    'stock_ids' => $stockIds // Already an array
                ];
            })->values();

            // Sort the results by quantity in descending order
            $sold_stocks_2 = $groupedResult->sortByDesc('quantity')->toArray();

            foreach($sold_stocks_2 as $key => $sold_stock){
                $average_cost = Order_item_model::whereIn('stock_id', $sold_stock['stock_ids'])->where('order_id',$order_id)->avg('price');
                $total_cost = Order_item_model::whereIn('stock_id', $sold_stock['stock_ids'])->where('order_id',$order_id)->sum('price');
                // $total_cost = 0;
                $total_price = 0;
                $total_quantity = 0;
                foreach($sold_stock['stock_ids'] as $stock_id){
                    $stock = Stock_model::find($stock_id);
                    // $total_cost += $stock->purchase_item->price;
                    $last_item = $stock->last_item();
                    if(in_array($last_item->order->order_type_id,[2,3,5])){
                        $total_price += $last_item->price;
                        $total_quantity++;
                    }
                }
                // $average_cost = $total_cost/$total_quantity;
                if($total_quantity == 0){
                    $average_price = "Issue";
                }else{
                    $average_price = $total_price/$total_quantity;
                }
                $sold_stocks_2[$key]['average_cost'] = $average_cost;
                $sold_stocks_2[$key]['total_cost'] = $total_cost;
                $sold_stocks_2[$key]['average_price'] = $average_price;
                $sold_stocks_2[$key]['total_price'] = $total_price;
                $sold_stocks_2[$key]['sold_quantity'] = $total_quantity;
            }

            // dd($sold_stocks_2);
            $data['sold_stock_summery'] = $sold_stocks_2;



            // Retrieve variations with related stocks
            $available_stocks = Variation_model::whereHas('stocks', function ($query) use ($order_id) {
                $query->where('order_id', $order_id)->where('status', 1);
            })
            ->withCount([
                'stocks as quantity' => function ($query) use ($order_id) {
                    $query->where('order_id', $order_id)->where('status', 1);
                }
            ])
            ->with([
                'stocks' => function ($query) use ($order_id) {
                    $query->where('order_id', $order_id)->where('status', 1);
                }
            ])
            ->get(['product_id', 'storage']);

            // Process the retrieved data to get stock IDs
            $result = $available_stocks->map(function ($variation) {
                $stocks = $variation->stocks;

                // Collect all stock IDs
                $stockIds = $stocks->pluck('id');

                return [
                    'product_id' => $variation->product_id,
                    'storage' => $variation->storage,
                    'quantity' => $variation->quantity, // Use quantity from withCount
                    'stock_ids' => $stockIds->toArray() // Convert collection to array
                ];
            });

            // Group the results by product_id and storage
            $groupedResult = $result->groupBy(function ($item) {
                return $item['product_id'] . '.' . $item['storage'];
            })->map(function ($items, $key) {
                list($product_id, $storage) = explode('.', $key);

                // Merge all stock IDs for the group
                $stockIds = $items->flatMap(function ($item) {
                    return $item['stock_ids'];
                })->unique()->values()->toArray(); // Convert to array

                // Sum the quantity
                $quantity = $items->sum('quantity'); // Sum the quantities

                return [
                    'product_id' => $product_id,
                    'storage' => $storage,
                    'quantity' => $quantity,
                    'stock_ids' => $stockIds // Already an array
                ];
            })->values();

            // Sort the results by quantity in descending order
            $available_stocks_2 = $groupedResult->sortByDesc('quantity')->toArray();

            foreach($available_stocks_2 as $key => $available_stock){
                $average_cost = Order_item_model::whereIn('stock_id', $available_stock['stock_ids'])->where('order_id',$order_id)->avg('price');
                $total_cost = Order_item_model::whereIn('stock_id', $available_stock['stock_ids'])->where('order_id',$order_id)->sum('price');
                $available_stocks_2[$key]['average_cost'] = $average_cost;
                $available_stocks_2[$key]['total_cost'] = $total_cost;
            }

            // dd($available_stocks_2);
            $data['available_stock_summery'] = $available_stocks_2;
        }elseif(request('summery') == 2){


            ini_set('memory_limit', '2048M');

            $repair_ids = Process_model::where('process_type_id',9)->pluck('id');
            $repair_stock_ids = Process_stock_model::whereIn('process_id',$repair_ids)->where('status',1)->pluck('stock_id');

            $product_storage_sort = Product_storage_sort_model::whereHas('stocks', function($q) use ($order_id){
                $q->where('stock.order_id', $order_id);
            })->orderBy('product_id')->orderBy('storage')->get();

            $variations = Variation_model::whereIn('product_storage_sort_id',$product_storage_sort->pluck('id'))->get();
            $rtg_variations = $variations->whereIn('grade', [1,2,3,4,5,7,9])->pluck('id');
            $other_variations = $variations->whereNotIn('id',$rtg_variations)->pluck('id');

            $result = [];
            foreach($product_storage_sort as $pss){
                $product = $pss->product;
                $storage = $pss->storage_id->name ?? null;



                $datas = [];
                $datas['pss_id'] = $pss->id;
                $datas['model'] = $product->model.' '.$storage;
                $datas['available_stock_count'] = $pss->stocks->where('order_id',$order_id)->where('status',1)->count();
                $datas['rtg_stock_count'] = $pss->stocks->where('order_id',$order_id)->where('status',1)->whereIn('variation_id',$rtg_variations)->count();
                $datas['other_stock_count'] = $pss->stocks->where('order_id',$order_id)->where('status',1)->whereIn('variation_id',$other_variations)->count();

                $datas['sold_stock_count'] = $pss->stocks->where('order_id',$order_id)->where('status',2)->whereNotIn('id',$repair_stock_ids)->count();
                $datas['repair_stock_count'] = $pss->stocks->where('order_id',$order_id)->where('status',2)->whereIn('id',$repair_stock_ids)->count();


                $result[] = $datas;
            }

            $data['stock_summery'] = $result;

            // dd($result);

        }else{
            if (!request('status') || request('status') == 1){
                $data['variations'] = Variation_model::with(['stocks' => function ($query) use ($order_id) {
                    $query->where(['order_id'=> $order_id, 'status'=>1]);
                },
                'stocks.stock_operations'
                ])
                ->whereHas('stocks', function ($query) use ($order_id) {
                    $query->where(['order_id'=> $order_id, 'status'=>1]);
                })
                ->orderBy('grade', 'asc')
                ->get();

            }

            if (!request('status') || request('status') == 2){

                $data['sold_stocks'] = Stock_model::with('order_items')
                ->where(['order_id'=> $order_id, 'status'=>2])
                ->orderBy('variation_id', 'asc')
                ->get();

                // $data['sold_stock_order_items'] = Order_item_model::whereHas('stock', function($q) use ($order_id){
                //     $q->where(['order_id'=> $order_id, 'status'=>2]);
                // // })->whereHas('order', function($q){
                // //     $q->whereIn('order_type_id', [2,3,5]);
                // })->latest()->distinct('stock_id')->get();

                // dd($data['sold_stock_order_items']);
            }

            $data['graded_count'] = Stock_model::select('grade.name as grade', 'variation.grade as grade_id', DB::raw('COUNT(*) as quantity'))
            ->when(request('status'), function ($q) {
                return $q->where('stock.status', request('status'));
            })
            ->where('stock.order_id', $order_id)
            ->join('variation', 'stock.variation_id', '=', 'variation.id')
            ->join('grade', 'variation.grade', '=', 'grade.id')
            ->groupBy('variation.grade', 'grade.name')
            ->orderBy('grade_id')
            ->get();

            // $sold_summery = Variation_model::withCount([
            //     'stocks as quantity' => function ($query) use ($order_id) {
            //         $query->where(['order_id'=> $order_id, 'status' => 2]);
            //     }])
            //     ->whereHas('stocks', function ($query) use ($order_id) {
            //         $query->where(['order_id'=> $order_id, 'status'=>2]);
            //     })->get();
            // dd($sold_summery);
            // $data['sold_summery'] = $sold_summery;
            $data['missing_stock'] = Order_item_model::where('order_id',$order_id)->whereHas('stock',function ($q) {
                $q->where(['imei'=>null,'serial_number'=>null]);
            })->get();
            // $order_issues = Order_issue_model::where('order_id',$order_id)->orderBy('message','ASC')->get();
            $order_issues = Order_issue_model::where('order_id',$order_id)->select(
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.name")) AS name'),
                'message',
                DB::raw('COUNT(*) as count'),
                DB::raw('GROUP_CONCAT(JSON_OBJECT("id", id, "order_id", order_id, "data", data, "message", message, "created_at", created_at, "updated_at", updated_at)) AS all_rows')
            )
            ->groupBy('name', 'message')
            ->get();
            // dd($order_issues);

            $data['order_issues'] = $order_issues;
            // dd($data['missing_stock']);
        }
        $data['all_variations'] = Variation_model::where('grade',9)->get();
        $data['order'] = Order_model::find($order_id);
        $data['order_id'] = $order_id;
        $data['currency'] = $data['order']->currency_id->sign;


        // echo "<pre>";
        // // print_r($items->stocks);
        // print_r($items);

        // echo "</pre>";
        // dd($data['variations']);
        return view('livewire.purchase_detail')->with($data);

    }

    public function purchase_model_graded_count($order_id, $pss_id){
        $pss = Product_storage_sort_model::find($pss_id);
        $stocks = $pss->stocks->where('order_id',$order_id);
        $grades = Grade_model::pluck('name','id');
        foreach($grades as $grade_id => $grade){
            $graded_variations = $pss->variations->where('grade',$grade_id);
            $data['graded_count'][$grade_id]['quantity'] = $stocks->whereIn('variation_id',$graded_variations->pluck('id'))->count();
            $data['graded_count'][$grade_id]['grade'] = $grade;
            $data['graded_count'][$grade_id]['grade_id'] = $grade_id;
        }

        // $data['graded_count'] = $stocks->select('grade.name as grade', 'variation.grade as grade_id', DB::raw('COUNT(*) as quantity'))
        // ->join('variation', 'stock.variation_id', '=', 'variation.id')
        // ->join('grade', 'variation.grade', '=', 'grade.id')
        // ->groupBy('variation.grade', 'grade.name')
        // ->orderBy('grade_id')
        // ->get();

        return response()->json($data['graded_count']);
    }

    public function add_purchase(){

        // dd(request('purchase'));
        $purchase = (object) request('purchase');
        $error = "";
        $issue = [];
        // Validate the uploaded file
        request()->validate([
            'purchase.sheet' => 'required|file|mimes:xlsx,xls',
        ]);

        // Store the uploaded file in a temporary location
        $filePath = request()->file('purchase.sheet')->store('temp');

        // // Perform operations on the Excel file
        // $spreadsheet = IOFactory::load(storage_path('app/'.$filePath));
        // // Perform your operations here...

        // Replace 'your-excel-file.xlsx' with the actual path to your Excel file
        $excelFilePath = storage_path('app/'.$filePath);

        $data = Excel::toArray([], $excelFilePath)[0];
        $dh = $data[0];
        // print_r($dh);
        unset($data[0]);
        $arrayLower = array_map('strtolower', $dh);
        $arrayLower = array_map('trim', $arrayLower);
        // Search for the lowercase version of the search value in the lowercase array
        $name = array_search('name', $arrayLower);
        // echo $name;
        $imei = array_search('imei', $arrayLower);
        // echo $imei;
        $cost = array_search('cost', $arrayLower);
        if(!$name){
            session()->put('error', "Heading not Found(name)");
            return redirect()->back();
        }
        if(!$imei){
            session()->put('error', "Heading not Found(imei)");
            return redirect()->back();
        }
        if(!$cost){
            session()->put('error', "Heading not Found(cost)");
            return redirect()->back();
        }

        if(!$name || !$imei || !$cost){
            print_r($dh);
            session()->put('error', "Heading not Found(name, imei, cost)");
            return redirect()->back();
        }
        if(!is_numeric($data[1][$cost])){
            session()->put('error', "Formula in Cost is not Allowed");
            return redirect()->back();

        }
        $color = array_search('color', $arrayLower);
        $v_grade = array_search('grade', $arrayLower);
        $note = array_search('notes', $arrayLower);

        // if($note){
        //     dd($data[1][$note]);
        // }
        // echo $cost;
        $grade = 9;


        $order = Order_model::firstOrNew(['reference_id' => $purchase->reference_id, 'order_type_id' => $purchase->type ]);

        if($order->id != null){
            if(session('user')->hasPermission('append_purchase_sheet')){}else{
                session()->put('error', "Append Purchase Sheet not Allowed");
                return redirect()->back();
            }
        }

        $order->customer_id = $purchase->vendor;
        $order->status = 2;
        $order->currency = 4;
        $order->order_type_id = $purchase->type;
        $order->processed_by = session('user_id');
        $order->save();

        $storages = Storage_model::pluck('name','id')->toArray();
        $colors = Color_model::pluck('name','id')->toArray();
        $grades = Vendor_grade_model::pluck('name','id')->toArray();
        // $grades = ['mix','a','a-','b+','b','c','asis','asis+','cpo','new'];

        $products = Products_model::pluck('model','id')->toArray();

        // $variations = Variation_model::where('grade',$grade)->get();

        foreach($data as $dr => $d){
            // $name = ;
            // echo $dr." ";
            // print_r($d);
            $n = trim($d[$name]);
            $c = $d[$cost];
            if(ctype_digit(trim($d[$imei]))){
                $i = trim($d[$imei]);
                $s = null;
            }else{
                $i = null;
                $s = trim($d[$imei]);
            }
            $names = explode(" ",$n);
            $last = end($names);
            if(in_array($last, $storages)){
                $gb = array_search($last,$storages);
                array_pop($names);
                $n = implode(" ", $names);
            }else{
                $gb = null;
            }

            if(trim($d[$imei]) == ''){
                if(trim($n) != '' || trim($c) != ''){
                    if(isset($storages[$gb])){$st = $storages[$gb];}else{$st = null;}
                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    if($color){
                        $issue[$dr]['data']['color'] = $d[$color];
                    }
                    if($v_grade){
                        $issue[$dr]['data']['v_grade'] = $d[$v_grade];
                    }
                    if($note){
                        $issue[$dr]['data']['note'] = $d[$note];
                    }
                    $issue[$dr]['data']['imei'] = $i.$s;
                    $issue[$dr]['data']['cost'] = $c;
                    $issue[$dr]['message'] = 'IMEI not Provided';
                }
                continue;
            }
            if(trim($n) == ''){
                if(trim($n) != '' || trim($c) != ''){
                    if(isset($storages[$gb])){$st = $storages[$gb];}else{$st = null;}
                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    if($color){
                        $issue[$dr]['data']['color'] = $d[$color];
                    }
                    if($v_grade){
                        $issue[$dr]['data']['v_grade'] = $d[$v_grade];
                    }
                    if($note){
                        $issue[$dr]['data']['note'] = $d[$note];
                    }
                    $issue[$dr]['data']['imei'] = $i.$s;
                    $issue[$dr]['data']['cost'] = $c;
                    $issue[$dr]['message'] = 'Name not Provided';
                }
                continue;
            }
            if(trim($c) == ''){
                if(trim($n) != '' || trim($c) != ''){
                if(isset($storages[$gb])){$st = $storages[$gb];}else{$st = null;}
                $issue[$dr]['data']['row'] = $dr;
                $issue[$dr]['data']['name'] = $n;
                $issue[$dr]['data']['storage'] = $st;
                if($color){
                    $issue[$dr]['data']['color'] = $d[$color];
                }
                if($v_grade){
                    $issue[$dr]['data']['v_grade'] = $d[$v_grade];
                }
                if($note){
                    $issue[$dr]['data']['note'] = $d[$note];
                }
                $issue[$dr]['data']['imei'] = $i.$s;
                $issue[$dr]['data']['cost'] = $c;
                $issue[$dr]['message'] = 'Cost not Provided';
                continue;
                }
            }
            // $last2 = end($names);
            // if($last2 == "5G"){
            //     array_pop($names);
            //     $n = implode(" ", $names);
            // }
            if(in_array(strtolower($n), array_map('strtolower',$products)) && ($i != null || $s != null)){
                $product = array_search(strtolower($n), array_map('strtolower',$products));
                $storage = $gb;
                if ($color) {
                    // Convert each color name to lowercase
                    $lowercaseColors = array_map('strtolower', $colors);

                    $colorName = strtolower($d[$color]); // Convert color name to lowercase

                    if (in_array($colorName, $lowercaseColors)) {
                        // If the color exists in the predefined colors array,
                        // retrieve its index
                        $clr = array_search($colorName, $lowercaseColors);
                    } else {
                        // If the color doesn't exist in the predefined colors array,
                        // create a new color record in the database
                        $newColor = Color_model::create([
                            'name' => $colorName
                        ]);
                        $colors = Color_model::pluck('name','id')->toArray();
                        $lowercaseColors = array_map('strtolower', $colors);
                        // Retrieve the ID of the newly created color
                        $clr = $newColor->id;
                    }
                    $check_merge_color = Product_color_merge_model::where(['product_id' => $product, 'color_from' => $clr])->first();
                    if($check_merge_color != null){
                        $clr = $check_merge_color->color_to;
                    }
                    $variation = Variation_model::firstOrNew(['product_id' => $product, 'grade' => $grade, 'storage' => $storage, 'color' => $clr]);

                }else{

                $variation = Variation_model::firstOrNew(['product_id' => $product, 'grade' => $grade, 'storage' => $storage]);
                }
                $grd = null;
                if ($v_grade) {
                    // Convert each v_grade name to lowercase
                    $lowercaseGrades = array_map('strtolower', $grades);

                    $v_gradeName = strtolower($d[$v_grade]); // Convert v_grade name to lowercase

                    $v_grd = Vendor_grade_model::firstOrNew(['name' => strtoupper($v_gradeName)]);
                    $v_grd->save();

                    $grd = $v_grd->id;
                }

                // echo $product." ".$grade." ".$storage." | ";

                $stock = Stock_model::firstOrNew(['imei' => $i, 'serial_number' => $s]);

                if($stock->id && $stock->status != null && $stock->order_id != null){
                    if(isset($storages[$gb])){$st = $storages[$gb];}else{$st = null;}
                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    if($variation){
                        $issue[$dr]['data']['variation'] = $variation->id;
                    }
                    if($color){
                        $issue[$dr]['data']['color'] = $d[$color];
                    }
                    if($v_grade){
                        $issue[$dr]['data']['v_grade'] = $d[$v_grade];
                    }
                    if($note){
                        $issue[$dr]['data']['note'] = $d[$note];
                    }
                    $issue[$dr]['data']['imei'] = $i.$s;
                    $issue[$dr]['data']['cost'] = $c;
                    if($stock->order_id == $order->id && $stock->status == 1){
                        $issue[$dr]['message'] = 'Item already added in this order';
                    }else{
                            $reference_id = $stock->order->reference_id ?? "Missing";
                        if($stock->status != 2){
                            $issue[$dr]['message'] = 'Item already available in inventory under order reference '.$reference_id;
                        }else{
                            $issue[$dr]['message'] = 'Item previously purchased in order reference '.$reference_id;
                        }

                    }

                }else{
                    $stock2 = Stock_model::withTrashed()->where(['imei' => $i, 'serial_number' => $s])->first();
                    if($stock2 != null){
                        $stock2->restore();
                        $stock2->order_id = $order->id;
                        $stock2->status = 1;
                        $stock2->save();
                        $order_item = Order_item_model::firstOrNew(['order_id' => $order->id, 'variation_id' => $variation->id, 'stock_id' => $stock2->id]);
                        $order_item->reference_id = $grd;
                        if($note){
                            $order_item->reference = $d[$note];
                        }
                        $order_item->quantity = 1;
                        $order_item->price = $c;
                        $order_item->status = 3;
                        $order_item->save();

                        $stock = $stock2;
                    }else{
                        $variation->stock += 1;
                        $variation->status = 1;
                        $variation->save();

                        $stock->product_id = $product;
                        $stock->variation_id = $variation->id;
                        $stock->added_by = session('user_id');
                        $stock->order_id = $order->id;
                        $stock->status = 1;
                        $stock->save();

                        $order_item = Order_item_model::firstOrNew(['order_id' => $order->id, 'variation_id' => $variation->id, 'stock_id' => $stock->id]);
                        $order_item->reference_id = $grd;
                        if($note){
                            $order_item->reference = $d[$note];
                        }
                        $order_item->quantity = 1;
                        $order_item->price = $c;
                        $order_item->status = 3;
                        $order_item->save();

                    }

                }

            }else{
                if(isset($storages[$gb])){$st = $storages[$gb];}else{$st = null;}
                if($n != null){
                    $error .= $n . " " . $st . " " . $i.$s . " || ";
                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    if($color){
                        $issue[$dr]['data']['color'] = $d[$color];
                    }
                    if($v_grade){
                        $issue[$dr]['data']['v_grade'] = $d[$v_grade];
                    }
                    if($note){
                        $issue[$dr]['data']['note'] = $d[$note];
                    }
                    $issue[$dr]['data']['imei'] = $i.$s;
                    $issue[$dr]['data']['cost'] = $c;
                    if($i == null && $s == null){
                        $issue[$dr]['message'] = 'IMEI/Serial Not Found';
                    }else{
                        $issue[$dr]['message'] = 'Product Name Not Found';
                    }

                }
            }

        }

        // Delete the temporary file
        // Storage::delete($filePath);
        if($error != ""){

            session()->put('error', $error);
            session()->put('missing', $issue);
        }
        if($issue != []){
            foreach($issue as $row => $datas){
                Order_issue_model::create([
                    'order_id' => $order->id,
                    'data' => json_encode($datas['data']),
                    'message' => $datas['message'],
                ]);
            }
        }
        return redirect(url('purchase/detail').'/'.$order->id);
    }
    public function add_purchase_item($order_id, $imei = null, $variation_id = null, $price = null, $return = null, $v_grade = null){
        $issue = [];
        if(request('imei')){
            $imei = request('imei');
        }
        if(request('order')){
            $order_id = request('order');
        }
        if(request('variation')){
            $variation_id = request('variation');
        }
        $variation = Variation_model::find($variation_id);
        if(request('price')){
            $price = request('price');
        }
        if(request('v_grade')){
            $v_grade = request('v_grade');
        }

        if(ctype_digit($imei)){
            $i = $imei;
            $s = null;
        }else{
            $i = null;
            $s = $imei;
        }

        if($variation == null){
            session()->put('error', 'Variation Not Found');
            return redirect()->back();
        }

        $stock = Stock_model::firstOrNew(['imei' => $i, 'serial_number' => $s]);
        if($stock->id && $stock->status != null && $stock->order_id != null && $stock->status != 2){
            $issue['data']['variation'] = $variation_id;
            $issue['data']['imei'] = $i.$s;
            $issue['data']['cost'] = $price;
            $issue['data']['stock_id'] = $stock->id;
            $issue['data']['v_grade'] = $v_grade;
            if($stock->order_id == $order_id && $stock->status == 1){
                $issue['message'] = 'Duplicate IMEI';
            }else{
                if($stock->status != 2){
                    $issue['message'] = 'IMEI Available In Inventory';
                }else{
                    $issue['message'] = 'IMEI Repurchase';
                }
            }
            // $stock->status = 2;
        }else{
            $stock2 = Stock_model::withTrashed()->where(['imei' => $i, 'serial_number' => $s])->orderByDesc('id')->first();
            if($stock2 != null){
                $stock2->restore();
                $stock2->order_id = $order_id;
                $stock2->status = 1;
                $stock2->save();
                $order_item = Order_item_model::firstOrNew(['order_id' => $order_id, 'variation_id' => $variation->id, 'stock_id' => $stock2->id]);
                $order_item->reference_id = $v_grade;
                $order_item->quantity = 1;
                $order_item->price = $price;
                $order_item->status = 3;
                $order_item->save();
                $stock = $stock2;

            }else{


                $variation->stock += 1;
                $variation->status = 1;
                $variation->save();


                $stock->added_by = session('user_id');
                $stock->order_id = $order_id;

                $stock->product_id = $variation->product_id;
                $stock->variation_id = $variation->id;
                $stock->status = 1;
                $stock->save();

                $order_item = new Order_item_model();
                $order_item->order_id = $order_id;
                $order_item->reference_id = $v_grade;
                $order_item->variation_id = $variation->id;
                $order_item->stock_id = $stock->id;
                $order_item->quantity = 1;
                $order_item->price = $price;
                $order_item->status = 3;
                $order_item->save();
            }

            $order = Order_model::find($order_id);
            if($order->status == 3 && !in_array($order_id,[8441,1,5,8,9,12,13,14,185,263,4739]) && $return == null){

                $issue['data']['variation'] = $variation_id;
                $issue['data']['imei'] = $i.$s;
                $issue['data']['cost'] = $price;
                $issue['data']['stock_id'] = $stock->id;
                $issue['data']['v_grade'] = $v_grade;
                $issue['message'] = 'Additional Item';
            }

        }

        if($issue != []){
            Order_issue_model::create([
                'order_id' => $order_id,
                'data' => json_encode($issue['data']),
                'message' => $issue['message'],
            ]);
        }else{
            $issue = 1;
        }
        // Delete the temporary file
        // Storage::delete($filePath);
        if($return == null){
            return redirect()->back();
        }else{
            return $issue;
        }

    }
    public function remove_issues(){
        // dd(request()->all());
        $ids = request('ids');
        $id = request('id');
        if(request('ids')){
            $issues = Order_issue_model::whereIn('id',$ids)->get();
        }
        if(request('id')){
            $issue = Order_issue_model::find($id);
        }

        if(request('remove_entries') == 1){
            foreach ($issues as $issue) {
                $issue->delete();
            }
        }
        if(request('remove_entry') == 1){
            // foreach ($issues as $issue) {
                $issue->delete();
            // }
        }
        if(request('insert_variation') == 1){
            $varia = request('variation');

            if(!ctype_digit($varia)){
                $storages = Storage_model::pluck('name','id')->toArray();
                $names = explode(" ",trim($varia));
                $last = end($names);
                if(in_array($last, $storages)){
                    $gb = array_search($last,$storages);
                    array_pop($names);
                    $n = implode(" ", $names);
                }else{
                    $gb = null;
                }
                $product = Products_model::where('model',$n)->first();
                if($product == null){
                    session()->put('error', 'Product Not Found');
                    // return redirect()->back();
                }
                $var = Variation_model::firstOrNew(['product_id' => $product->id, 'grade' => 9, 'storage' => $gb, 'color' => null]);
                $var->save();

                $variation = $var->id;
                // dd($variation);
            }else{
                $variation = $varia;
            }

            if(ctype_digit($variation)){

                foreach($issues as $issue){
                    $data = json_decode($issue->data);
                    // echo $variation." ".$data->imei." ".$data->cost;



                    if($this->add_purchase_item($issue->order_id,
                    $data->imei,
                    $variation,
                    $data->cost, 1) == 1){
                        $issue->delete();
                    }

                }
            }
        }
        if(request('add_imei') == 1){
            $imei = request('imei');
            $variation = request('variation');
            $data = json_decode($issue->data);
            // echo $variation." ".$data->imei." ".$data->cost;
            if($data->v_grade){
                $v_grade = Vendor_grade_model::where('name',$data->v_grade)->first()->id ?? null;
            }

            if($this->add_purchase_item($issue->order_id, $imei, $variation, $data->cost, 1, $v_grade) == 1){
                if($data->imei){

                    $stock = Stock_model::where('imei',$imei)->orWhere('serial_number', $imei)->where('status','!=',null)->first();
                    $stock_operation = new Stock_operations_model();
                    $stock_operation->new_operation($stock->id, null, null, null, $stock->variation_id, $stock->variation_id, "IMEI Changed from ".$data->imei);
                }
                $issue->delete();
            }

        }
        if(request('change_imei') == 1){
            $imei = request('imei');
            $serial_number = null;
            $imei = trim($imei);
            if(!ctype_digit($imei)){
                $serial_number = $imei;
                $imei = null;
            }
            $old_stock = Stock_model::where(['imei'=>$imei,'serial_number'=>$serial_number])->where('status','!=',null)->first();
            if(!$old_stock){

                session()->put('error', "IMEI not Found");
                return redirect()->back();
            }
            $data = json_decode($issue->data);
            $new_stock = Stock_model::find($data->stock_id);
            if(!$new_stock){

                session()->put('error', "Additional Item not added Properly");
                return redirect()->back();
            }
            $new_item = Order_item_model::find($new_stock->purchase_item->id);
            $new_item->order_id = $old_stock->order_id;
            $new_item->price = $old_stock->purchase_item->price;

            $new_stock->order_id = $old_stock->order_id;

            $stock_operation = new Stock_operations_model();
            $stock_operation->new_operation($new_stock->id, $new_item->id, null, null, $old_stock->variation_id, $new_stock->variation_id, "IMEI Changed from ".$old_stock->imei.$old_stock->serial_number);

            Order_item_model::where('stock_id',$old_stock->id)->update(['stock_id' => $new_stock->id]);
            Process_stock_model::where('stock_id',$old_stock->id)->update(['stock_id' => $new_stock->id]);
            Stock_operations_model::where('stock_id',$old_stock->id)->update(['stock_id' => $new_stock->id]);

            // $stock_operation = Stock_operations_model::create([
            //     'stock_id' => $new_stock->id,
            //     'old_variation_id' => $old_stock->variation_id,
            //     'new_variation_id' => $new_stock->variation_id,
            //     'description' => "IMEI Changed from ".$old_stock->imei.$old_stock->serial_number,
            //     'admin_id' => session('user_id'),
            // ]);

            $old_stock->purchase_item->delete();
            $old_stock->delete();

            $new_item->save();
            $new_stock->save();

            $issue->delete();

        }
        if(request('repurchase') == 1){
            $data = json_decode($issue->data);
            if($this->add_purchase_item($issue->order_id, $data->imei, $data->variation, $data->cost, 1) == 1){
                if($data->imei){
                    $stock = Stock_model::where('imei',$data->imei)->orWhere('serial_number', $data->imei)->where('status','!=',null)->first();
                    $stock_operation = new Stock_operations_model();
                    $stock_operation->new_operation($stock->id, null, null, null, $stock->variation_id, $stock->variation_id, "IMEI Repurchased");
                }
                $issue->delete();
            }

        }
        return redirect()->back();

    }
    public function export_invoice_new($orderId)
    {

        // Find the order
        $order = Order_model::with('customer', 'order_items')->find($orderId);
        $order_items = Order_item_model::where('order_id', $orderId);
        if($order_items->count() > 1){
            $order_items = $order_items->whereHas('stock', function($q) {
                $q->where('status', 2)->orWhere('status',null);
            })->get();
        }else{
            $order_items = $order_items->get();
        }

        // Generate PDF for the invoice content
        $data = [
            'order' => $order,
            'customer' => $order->customer,
            'orderItems' => $order_items,
        ];

        // Create a new TCPDF instance
        $pdf = new TCPDF();

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        // $pdf->SetTitle('Invoice');
        // $pdf->SetHeaderData('', 0, 'Invoice', '');

        // Add a page
        $pdf->AddPage();

        // Set font
        // $fontname = TCPDF_FONTS::addTTFfont(asset('assets/font/OpenSans_Condensed-Regular.ttf'), 'TrueTypeUnicode', '', 96);

        $pdf->SetFont('dejavusans', '', 12);


        // Additional content from your view
        $html = view('export.invoice', $data)->render();
        $pdf->writeHTML($html, true, false, true, false, '');

        // dd($pdfContent);
        // Send the invoice via email

        Mail::to($order->customer->email)->queue(new InvoiceMail($data));
        // if(session('user_id') == 1){

        // $recipientEmail = $order->customer->email;
        // $subject = 'Invoice for Your Recent Purchase';

        // app(GoogleController::class)->sendEmailInvoice($recipientEmail, $subject, new InvoiceMail($data));
        // die;
        // }
        // file_put_contents('invoice.pdf', $pdfContent);

        // Get the PDF content
        // $pdf->Output('', 'I');

        $pdfContent = $pdf->Output('', 'S');
        // Return a response or redirect

        // Pass the PDF content to the view
        return view('livewire.show_pdf')->with(['pdfContent'=> $pdfContent, 'delivery_note'=>$order->delivery_note_url]);
    }
    public function export_invoice($orderId)
    {

        // Find the order
        $order = Order_model::with('customer', 'order_items')->find($orderId);
        $order_items = Order_item_model::where('order_id', $orderId);
        if($order_items->count() > 1){
            $order_items = $order_items->whereHas('stock', function($q) {
                $q->where('status', 2)->orWhere('status',null);
            })->get();
        }else{
            $order_items = $order_items->get();
        }

        // Generate PDF for the invoice content
        $data = [
            'order' => $order,
            'customer' => $order->customer,
            'orderItems' => $order_items,
        ];

        // // Create a new TCPDF instance
        // $pdf = new TCPDF();

        // // Set document information
        // $pdf->SetCreator(PDF_CREATOR);
        // // $pdf->SetTitle('Invoice');
        // // $pdf->SetHeaderData('', 0, 'Invoice', '');

        // // Add a page
        // $pdf->AddPage();

        // // Set font
        // // $fontname = TCPDF_FONTS::addTTFfont(asset('assets/font/OpenSans_Condensed-Regular.ttf'), 'TrueTypeUnicode', '', 96);

        // $pdf->SetFont('dejavusans', '', 12);


        // // Additional content from your view
        // $html = view('export.invoice', $data)->render();
        // $pdf->writeHTML($html, true, false, true, false, '');

        // dd($pdfContent);
        // Send the invoice via email

        Mail::to($order->customer->email)->queue(new InvoiceMail($data));
        // if(session('user_id') == 1){

        // $recipientEmail = $order->customer->email;
        // $subject = 'Invoice for Your Recent Purchase';

        // app(GoogleController::class)->sendEmailInvoice($recipientEmail, $subject, new InvoiceMail($data));
        // die;
        // }
        // file_put_contents('invoice.pdf', $pdfContent);

        // Get the PDF content
        // $pdf->Output('', 'I');

        // $pdfContent = $pdf->Output('', 'S');
        // Return a response or redirect

        // Pass the PDF content to the view
        // return view('livewire.show_pdf')->with(['pdfContent'=> $pdfContent, 'delivery_note'=>$order->delivery_note_url]);
        return view('livewire.invoice_new')->with($data);
    }
    public function proxy_server(){

        $url = $_GET['url']; // The URL of the PDF you want to fetch

        // Validate URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            // Set the headers
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="document.pdf"');

            // Fetch and output the file
            readfile($url);
        } else {
            echo "Invalid URL.";
        }
    }
    public function export_refund_invoice($orderId)
    {

        // Find the order
        $order = Order_model::with('customer', 'order_items')->find($orderId);
        $order_items = Order_item_model::where('order_id', $orderId);
        if($order_items->count() > 1){
            $order_items = $order_items->whereHas('stock', function($q) {
                $q->where('status', 2)->orWhere('status',null);
            })->get();
        }else{
            $order_items = $order_items->get();
        }

        // Generate PDF for the invoice content
        $data = [
            'order' => $order,
            'customer' => $order->customer,
            'orderItems' => $order_items,
        ];

        // Create a new TCPDF instance
        $pdf = new TCPDF();

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        // $pdf->SetTitle('Invoice');
        // $pdf->SetHeaderData('', 0, 'Invoice', '');

        // Add a page
        $pdf->AddPage();

        // Set font
        // $fontname = TCPDF_FONTS::addTTFfont(asset('assets/font/OpenSans_Condensed-Regular.ttf'), 'TrueTypeUnicode', '', 96);

        $pdf->SetFont('dejavusans', '', 12);


        // Additional content from your view
        $html = view('export.refund_invoice', $data)->render();
        $pdf->writeHTML($html, true, false, true, false, '');


        $pdfContent = $pdf->Output('', 'S');
        // Return a response or redirect

        // Pass the PDF content to the view
        return view('livewire.show_pdf')->with(['pdfContent'=> $pdfContent]);
    }
    public function dispatch($id)
    {
        $order = Order_model::find($id);
        $bm = new BackMarketAPIController();
        // $orderObj = $bm->getOneOrder($order->reference_id);
        $orderObj = $this->updateBMOrder($order->reference_id, false, null, true);
        if(session('user_id') == 1){
            dd("Hello");
        }
        if($orderObj == null){

            session()->put('error', "Order Not Found");
            return redirect()->back();
        }
        $tester = request('tester');
        $sku = request('sku');
        $imeis = request('imei');

        // Initialize an empty result array
        $skus = [];
        if(count($sku) > 1 && count($imeis) > 1){

            // Loop through the numbers array
            foreach ($sku as $index => $number) {
                // If the value doesn't exist as a key in the skus array, create it
                if (!isset($skus[$number])) {
                    $skus[$number] = [];
                }
                // Add the current number to the skus array along with its index in the original array
                $skus[$number][$index] = $number;
            }

        }
        // print_r(request('imei'));
        if($orderObj->state == 3){
            foreach($imeis as $i => $imei){

                $variant = Variation_model::where('sku',$sku[$i])->first();
                if($variant->storage != null){
                    $storage2 = $variant->storage_id->name . " - ";
                }else{
                    $storage2 = null;
                }
                if($variant->color != null){
                    $color2 = $variant->color_id->name . " - ";
                }else{
                    $color2 = null;
                }

                $serial_number = null;
                $imei = trim($imei);
                if(!ctype_digit($imei)){
                    $serial_number = $imei;
                    $imei = null;

                }else{

                    if(strlen($imei) != 15){

                        session()->put('error', "IMEI invalid");
                        return redirect()->back();
                    }
                }

                $stock[$i] = Stock_model::where(['imei'=>$imei, 'serial_number'=>$serial_number])->first();

                if(!$stock[$i] || $stock[$i]->status == null){
                    session()->put('error', "Stock not Found");
                    return redirect()->back();

                }
                // if($stock[$i]->status != 1){

                    $last_item = $stock[$i]->last_item();
                    if($last_item == null){
                        $imei = new IMEI();
                        $imei->rearrange($stock[$i]->id);
                        $last_item = $stock[$i]->last_item();
                    }

                    // if(session('user_id') == 1){
                    //     dd($last_item);
                    // }
                    if(in_array($last_item->order->order_type_id,[1,4,6])){

                        if($stock[$i]->status == 2){
                            $stock[$i]->status = 1;
                            $stock[$i]->save();
                        }
                    }else{
                        if($stock[$i]->status == 1){
                            $stock[$i]->status = 2;
                            $stock[$i]->save();
                        }
                        session()->put('error', "Stock Already Sold");
                        return redirect()->back();
                    }
                // }
                if($stock[$i]->order->status < 3){
                    session()->put('error', "Stock List Awaiting Approval");
                    return redirect()->back();
                }
                if($stock[$i]->variation->grade == 17){
                    session()->put('error', "IMEI Flagged | Contact Admin");
                    return redirect()->back();
                }
                $stock_movement = Stock_movement_model::where(['stock_id'=>$stock[$i]->id, 'received_at'=>null])->first();
                // , 'admin_id' => session('user_id')

                if($stock_movement == null && !session('user')->hasPermission('skip_stock_exit')){
                    session()->put('error', "Missing Exit Entry");
                    return redirect()->back();
                }
                if($stock[$i]->variation->storage != null){
                    $storage = $stock[$i]->variation->storage_id->name . " - ";
                }else{
                    $storage = null;
                }
                if($stock[$i]->variation->color != null){
                    $color = $stock[$i]->variation->color_id->name . " - ";
                }else{
                    $color = null;
                }
                if(($stock[$i]->variation->product_id == $variant->product_id) || ($variant->product_id == 144 && $stock[$i]->variation->product_id == 229) || ($variant->product_id == 142 && $stock[$i]->variation->product_id == 143) || ($variant->product_id == 54 && $stock[$i]->variation->product_id == 55) || ($variant->product_id == 55 && $stock[$i]->variation->product_id == 54) || ($variant->product_id == 58 && $stock[$i]->variation->product_id == 59) || ($variant->product_id == 59 && $stock[$i]->variation->product_id == 58) || ($variant->product_id == 200 && $stock[$i]->variation->product_id == 160)){
                }else{
                    session()->put('error', "Product Model not matched");
                    return redirect()->back();
                }
                if(($stock[$i]->variation->storage == $variant->storage) || ($variant->storage == 5 && in_array($stock[$i]->variation->storage,[0,6]) && $variant->product->brand == 2) || (in_array($variant->product_id, [78,58,59]) && $variant->storage == 4 && in_array($stock[$i]->variation->storage,[0,5]))){
                }else{
                    session()->put('error', "Product Storage not matched");
                    return redirect()->back();
                }
                if($stock[$i]->variation->grade != $variant->grade){
                    session()->put('error', "Product Grade not matched");
                    return redirect()->back();

                }
                // if($stock[$i]->variation->color != $variant->color && session('user_id') == 36){
                //     session()->put('error', "Product Color not matched");
                //     return redirect()->back();
                // }

                if($stock[$i]->variation_id != $variant->id){
                    echo "<script>
                    if (confirm('System Model: " . $stock[$i]->variation->product->model . " - " . $storage . $color . $stock[$i]->variation->grade_id->name . "\\nRequired Model: " . $variant->product->model . " - " . $storage2 . $color2 . $variant->grade_id->name . "')) {
                        // User clicked OK, do nothing or perform any other action
                    } else {
                        // User clicked Cancel, redirect to the previous page
                        window.history.back();
                    }
                    </script>";

                    $stock_operation = Stock_operations_model::create([
                        'stock_id' => $stock[$i]->id,
                        'old_variation_id' => $stock[$i]->variation_id,
                        'new_variation_id' => $variant->id,
                        'description' => "Grade changed for Sell",
                        'admin_id' => session('user_id'),
                    ]);
                }
                $stock[$i]->variation_id = $variant->id;
                $stock[$i]->tester = $tester[$i];
                $stock[$i]->sale_order_id = $id;
                $stock[$i]->status = 2;
                $stock[$i]->save();

                // $orderObj = $this->updateBMOrder($order->reference_id, true, $tester[$i], true);
            }
            // $order = Order_model::find($order->id);
            $items = $order->order_items;
            if(count($items) > 1 || $items[0]->quantity > 1){
                $indexes = 0;
                foreach($skus as $each_sku){
                    if($indexes == 0 && count($each_sku) == 1){
                        $detail = $bm->shippingOrderlines($order->reference_id,$sku[0],trim($imeis[0]),$orderObj->tracking_number,$serial_number);
                    }elseif($indexes == 0 && count($each_sku) > 1){
                        // dd("Hello");
                        $detail = $bm->shippingOrderlines($order->reference_id,$sku[0],false,$orderObj->tracking_number,$serial_number);
                        if(count($each_sku) == 1){
                            $order_item = Order_item_model::where('order_id',$order->id)->whereHas('variation', function($q) use ($each_sku){
                                $q->whereIn('sku',$each_sku);
                            })->first();
                            $detail = $bm->orderlineIMEI($order_item->reference_id,trim($imeis[0]),$serial_number);
                        }
                    }elseif($indexes > 0 && count($each_sku) == 1){
                        $order_item = Order_item_model::where('order_id',$order->id)->whereHas('variation', function($q) use ($each_sku){
                            $q->whereIn('sku',$each_sku);
                        })->first();
                        $detail = $bm->orderlineIMEI($order_item->reference_id,trim($imeis[$indexes]),$serial_number);
                    }else{

                    }
                    $indexes++;
                }
            }else{
                $detail = $bm->shippingOrderlines($order->reference_id,$sku[0],trim($imeis[0]),$orderObj->tracking_number,$serial_number);
            }
            // print_r($detail);

            if(is_string($detail)){
                session()->put('error', $detail);
                return redirect()->back();
            }

            if(count($sku) == 1 && count($stock) == 1){
                $order_item = Order_item_model::where('order_id',$order->id)->whereHas('variation', function($q) use ($sku){
                    $q->where('sku',$sku[0]);
                })->first();
                $order_item->stock_id = $stock[0]->id;
                $order_item->linked_id = $stock[0]->last_item()->id;

                $order_item->save();
                if($stock_movement != null){

                $stock_movement->update([
                    'received_at' => Carbon::now(),
                ]);

                }
            }else{

                foreach ($skus as $each) {
                    $inde = 0;
                    foreach ($each as $idt => $s) {
                        $variation = Variation_model::where('sku',$s)->first();
                        $item = Order_item_model::where(['order_id'=>$id, 'variation_id'=>$variation->id])->first();
                        if ($inde != 0) {

                            $new_item = new Order_item_model();
                            $new_item->order_id = $id;
                            $new_item->variation_id = $item->variation_id;
                            $new_item->quantity = $item->quantity;
                            $new_item->status = $item->status;
                            $new_item->price = $item->price;
                        }else{
                            $new_item = $item;
                            $new_item->price = $item->price/count($each);
                        }
                        if($stock[$idt]){
                            $new_item->stock_id = $stock[$idt]->id;
                            $new_item->linked_id = $stock[$idt]->last_item()->id;


                            $stock_movement = Stock_movement_model::where(['stock_id'=>$stock[$idt]->id, 'received_at'=>null])->first();
                            if($stock_movement != null){
                                Stock_movement_model::where(['stock_id'=>$stock[$idt]->id, 'received_at'=>null])->update([
                                    'received_at' => Carbon::now(),
                                ]);
                            }
                        // $new_item->linked_id = Order_item_model::where(['order_id'=>$stock[$idt]->order_id,'stock_id'=>$stock[$idt]->id])->first()->id;
                        }
                        $new_item->save();
                        $inde ++;
                    }
                }
            }

            // print_r($d[6]);
        }

        $orderObj = $this->updateBMOrder($order->reference_id, true, null, false, $bm);
        $invoice_url = url('export_invoice').'/'.$id;
        // $order = Order_model::find($order->id);
        if(isset($detail->orderlines) && $detail->orderlines[0]->imei == null && $detail->orderlines[0]->serial_number  == null){
            $content = "Hi, here are the IMEIs/Serial numbers for this order. \n";
            foreach ($imeis as $im) {
                $content .= $im . "\n";
            }
            $content .= "Regards \n".session('fname');

            // JavaScript code to automatically copy content to clipboard
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    const el = document.createElement("textarea");
                    el.value = "'.$content.'";
                    document.body.appendChild(el);
                    el.select();
                    document.execCommand("copy");
                    document.body.removeChild(el);
                });

                window.open("https://backmarket.fr/bo_merchant/orders/all?orderId='.$order->reference_id.'", "_blank");
            </script>';
        }

        // JavaScript to open two tabs and print
        echo '<script>
        var newTab2 = window.open("'.$invoice_url.'", "_blank");

        </script>';
        if(request('sort') == 4 && !isset($detail)){
            echo "<script> window.close(); </script>";
        }
            echo "<script> window.location.href = document.referrer; </script>";



    }
    public function dispatch_allowed($id)
    {
        $order = Order_model::where('id',$id)->first();
        $bm = new BackMarketAPIController();

        // $orderObj = $bm->getOneOrder($order->reference_id);
        $orderObj = $this->updateBMOrder($order->reference_id, false, null, true);
        $tester = request('tester');
        $sku = request('sku');
        $imeis = request('imei');

        // Initialize an empty result array
        $skus = [];

        // Loop through the numbers array
        foreach ($sku as $index => $number) {
            // If the value doesn't exist as a key in the skus array, create it
            if (!isset($skus[$number])) {
                $skus[$number] = [];
            }
            // Add the current number to the skus array along with its index in the original array
            $skus[$number][$index] = $number;
        }
        // print_r(request('imei'));
        if($orderObj->state == 3){
            foreach(request('imei') as $i => $imei){

                $variant = Variation_model::where('sku',$sku[$i])->first();
                if($variant->storage != null){
                    $storage2 = $variant->storage_id->name . " - ";
                }else{
                    $storage2 = null;
                }
                if($variant->color != null){
                    $color2 = $variant->color_id->name . " - ";
                }else{
                    $color2 = null;
                }

                $serial_number = null;
                $imei = trim($imei);
                if(!ctype_digit($imei)){
                    $serial_number = $imei;
                    $imei = null;

                }else{

                    if(strlen($imei) != 15){

                        session()->put('error', "IMEI invalid");
                        return redirect()->back();
                    }
                }

                $stock[$i] = Stock_model::where(['imei'=>$imei, 'serial_number'=>$serial_number])->first();

                if(!$stock[$i] || $stock[$i]->status == null){
                    session()->put('error', "Stock not Found");
                    return redirect()->back();

                }
                // if($stock[$i]->status != 1){

                    $last_item = $stock[$i]->last_item();
                    // if(session('user_id') == 1){
                    //     dd($last_item);
                    // }
                    if(in_array($last_item->order->order_type_id,[1,4,6])){

                        if($stock[$i]->status == 2){
                            $stock[$i]->status = 1;
                            $stock[$i]->save();
                        }
                    }else{
                        if($stock[$i]->status == 1){
                            $stock[$i]->status = 2;
                            $stock[$i]->save();
                        }
                        session()->put('error', "Stock Already Sold");
                        return redirect()->back();
                    }
                // }
                if($stock[$i]->order->status < 3){
                    session()->put('error', "Stock List Awaiting Approval");
                    return redirect()->back();
                }
                if($stock[$i]->variation->grade == 17){
                    session()->put('error', "IMEI Flagged | Contact Admin");
                    return redirect()->back();
                }
                if($stock[$i]->variation->storage != null){
                    $storage = $stock[$i]->variation->storage_id->name . " - ";
                }else{
                    $storage = null;
                }
                if($stock[$i]->variation->color != null){
                    $color = $stock[$i]->variation->color_id->name . " - ";
                }else{
                    $color = null;
                }
                if(($stock[$i]->variation->product_id == $variant->product_id) || ($variant->product_id == 144 && $stock[$i]->variation->product_id == 229) || ($variant->product_id == 142 && $stock[$i]->variation->product_id == 143) || ($variant->product_id == 54 && $stock[$i]->variation->product_id == 55) || ($variant->product_id == 55 && $stock[$i]->variation->product_id == 54) || ($variant->product_id == 200 && $stock[$i]->variation->product_id == 160)){
                }else{
                    session()->put('error', "Product Model not matched");
                    // return redirect()->back();
                }
                if(($stock[$i]->variation->storage == $variant->storage) || ($variant->storage == 5 && in_array($stock[$i]->variation->storage,[0,6]) && $variant->product->brand == 2) || (in_array($variant->product_id, [78,58,59]) && $variant->storage == 4 && in_array($stock[$i]->variation->storage,[0,5]))){
                }else{
                    session()->put('error', "Product Storage not matched");
                    // return redirect()->back();
                }
                if($stock[$i]->variation_id != $variant->id){
                    echo "<script>
                    if (confirm('System Model: " . $stock[$i]->variation->product->model . " - " . $storage . $color . $stock[$i]->variation->grade_id->name . "\\nRequired Model: " . $variant->product->model . " - " . $storage2 . $color2 . $variant->grade_id->name . "')) {
                        // User clicked OK, do nothing or perform any other action
                    } else {
                        // User clicked Cancel, redirect to the previous page
                        window.history.back();
                    }
                    </script>";

                    $stock_operation = Stock_operations_model::create([
                        'stock_id' => $stock[$i]->id,
                        'old_variation_id' => $stock[$i]->variation_id,
                        'new_variation_id' => $variant->id,
                        'description' => "Grade changed for Sell",
                        'admin_id' => session('user_id'),
                    ]);
                }
                $stock[$i]->variation_id = $variant->id;
                $stock[$i]->tester = $tester[$i];
                $stock[$i]->sale_order_id = $id;
                $stock[$i]->status = 2;
                $stock[$i]->save();
                $stock_movement = Stock_movement_model::where(['stock_id'=>$stock[$i]->id, 'received_at'=>null])->first();
                if($stock_movement != null){
                    Stock_movement_model::where(['stock_id'=>$stock[$i]->id, 'received_at'=>null])->update([
                        'received_at' => Carbon::now(),
                    ]);
                }
                $orderObj = $this->updateBMOrder($order->reference_id, true, $tester[$i], true);
            }
            $order = Order_model::find($order->id);
            $items = $order->order_items;
            if(count($items) > 1 || $items[0]->quantity > 1){
                $indexes = 0;
                foreach($skus as $each_sku){
                    if($indexes == 0 && count($each_sku) == 1){
                        $detail = $bm->shippingOrderlines($order->reference_id,$sku[0],trim($imeis[0]),$orderObj->tracking_number,$serial_number);
                    }elseif($indexes == 0 && count($each_sku) > 1){
                        // dd("Hello");
                        $detail = $bm->shippingOrderlines($order->reference_id,$sku[0],false,$orderObj->tracking_number,$serial_number);
                    }elseif($indexes > 0 && count($each_sku) == 1){
                        $detail = $bm->orderlineIMEI($order->reference_id,trim($imeis[0]),$serial_number);
                    }else{

                    }
                    $indexes++;
                }
            }else{
                $detail = $bm->shippingOrderlines($order->reference_id,$sku[0],trim($imeis[0]),$orderObj->tracking_number,$serial_number);
            }
            // print_r($detail);

            if(is_string($detail)){
                session()->put('error', $detail);
                return redirect()->back();
            }


            foreach ($skus as $each) {
                $inde = 0;
                foreach ($each as $idt => $s) {
                    $variation = Variation_model::where('sku',$s)->first();
                    $item = Order_item_model::where(['order_id'=>$id, 'variation_id'=>$variation->id])->first();
                    if ($inde != 0) {

                        $new_item = new Order_item_model();
                        $new_item->order_id = $id;
                        $new_item->variation_id = $item->variation_id;
                        $new_item->quantity = $item->quantity;
                        $new_item->status = $item->status;
                        $new_item->price = $item->price;
                    }else{
                        $new_item = $item;
                        $new_item->price = $item->price/count($each);
                    }
                    if($stock[$idt]){
                    $new_item->stock_id = $stock[$idt]->id;
                    $new_item->linked_id = $stock[$idt]->last_item()->id;
                    // $new_item->linked_id = Order_item_model::where(['order_id'=>$stock[$idt]->order_id,'stock_id'=>$stock[$idt]->id])->first()->id;
                    }
                    $new_item->save();
                    $inde ++;
                }
            }

            // print_r($d[6]);
        }

        $orderObj = $this->updateBMOrder($order->reference_id, true);
        $order = Order_model::find($order->id);
        if(!isset($detail)){

            $invoice_url = url('export_invoice').'/'.$id;
            // JavaScript to open two tabs and print
            echo '<script>
            // var newTab1 = window.open("'.$order->delivery_note_url.'", "_blank");
            // var newTab2 = window.open("'.$invoice_url.'", "_blank");

            // newTab2.onload = function() {
            //     newTab2.print();
            // };
            // newTab1.onload = function() {
            //     newTab1.print();
            // };

            window.location.href = document.referrer;
            </script>';

        }
        if(!$detail->orderlines){
            dd($detail);
        }
        if($detail->orderlines[0]->imei == null && $detail->orderlines[0]->serial_number  == null){
            $content = "Hi, here are the IMEIs/Serial numbers for this order. \n";
            foreach ($imeis as $im) {
                $content .= $im . "\n";
            }
            $content .= "Regards \n".session('fname');

            // JavaScript code to automatically copy content to clipboard
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const el = document.createElement('textarea');
                    el.value = '$content';
                    document.body.appendChild(el);
                    el.select();
                    document.execCommand('copy');
                    document.body.removeChild(el);
                });
            </script>";


            // JavaScript to open two tabs and print
            echo '<script>
            window.open("https://backmarket.fr/bo_merchant/orders/all?orderId='.$order->reference_id.'", "_blank");
            window.location.href = document.referrer;
            </script>';
        }else{

            $invoice_url = url('export_invoice').'/'.$id;
            // JavaScript to open two tabs and print
            echo '<script>
            // var newTab1 = window.open("'.$order->delivery_note_url.'", "_blank");
            // var newTab2 = window.open("'.$invoice_url.'", "_blank");

            // newTab2.onload = function() {
            //     newTab2.print();
            // };
            // newTab1.onload = function() {
            //     newTab1.print();
            // };

            window.location.href = document.referrer;
            </script>';
        }


    }
    public function delete_item($id){
        Order_item_model::find($id)->delete();
        return redirect()->back();
    }
    public function delete_replacement_item($id){
        $item = Order_item_model::find($id);
        $item->stock->status = 1;
        $item->stock->save();
        $item->delete();
        return redirect()->back();
    }
    public function tracking(){
        $order = Order_model::find(request('tracking')['order_id']);
        if(session('user')->hasPermission('change_order_tracking')){

            $new_tracking = strtoupper(trim(request('tracking')['number']));

            if($order->tracking_number != $new_tracking){
                if(strlen($new_tracking) == 21 && strpos($new_tracking, 'JJ') == 0){
                    $new_tracking = substr($new_tracking, 1);
                }
                if(strlen($new_tracking) != 20){
                    session()->put('error', "Tracking number invalid".strlen($new_tracking));
                    return redirect()->back();
                }
            }
            $message = "Tracking number changed from " . $order->tracking_number . " to " . $new_tracking . " | " . request('tracking')['reason'];

            $order->tracking_number = $new_tracking;
            $order->reference = $message;
            $order->save();

        }
        return redirect()->back();
    }

    public function correction($override = false){
        $item = Order_item_model::find(request('correction')['item_id']);
        if(session('user')->hasPermission('correction')){
            if($item->quantity > 1 && $item->order->order_items->count() == 1){
                for($i=1; $i<=$item->quantity; $i++){

                    if ($i != 1) {

                        $new_item = new Order_item_model();
                        $new_item->order_id = $item->order_id;
                        $new_item->variation_id = $item->variation_id;
                        $new_item->quantity = $item->quantity;
                        $new_item->status = $item->status;
                        $new_item->price = $item->price;
                    }else{
                        $new_item = $item;
                        $new_item->price = $item->price/$item->quantity;
                    }
                    $new_item->save();
                }
            }
            $imei = request('correction')['imei'];

            $serial_number = null;
            if(!ctype_digit($imei)){
                $serial_number = $imei;
                $imei = null;
            }

            if(request('correction')['imei'] != ''){
                $stock = Stock_model::where(['imei'=>$imei, 'serial_number'=>$serial_number])->first();
                if(!$stock || $stock->status == null){
                    session()->put('error', 'Stock not found');
                    return redirect()->back();
                }
                if($stock->order->status != 3){
                    session()->put('error', 'Stock list awaiting approval');
                    return redirect()->back();
                }
                if($stock->variation->grade == 17){
                    session()->put('error', "IMEI Flagged | Contact Admin");
                    return redirect()->back();
                }
                if($stock->variation->storage != null){
                    $storage = $stock->variation->storage_id->name . " - ";
                }else{
                    $storage = null;
                }
                if($stock->variation->color != null){
                    $color = $stock->variation->color_id->name . " - ";
                }else{
                    $color = null;
                }
                $variant = $item->variation;
                if(($stock->variation->product_id == $variant->product_id) || ($variant->product_id == 144 && $stock->variation->product_id == 229) || ($variant->product_id == 142 && $stock->variation->product_id == 143) || ($variant->product_id == 54 && $stock->variation->product_id == 55) || ($variant->product_id == 55 && $stock->variation->product_id == 54) || ($variant->product_id == 58 && $stock->variation->product_id == 59) || ($variant->product_id == 59 && $stock->variation->product_id == 58) || ($variant->product_id == 200 && $stock->variation->product_id == 160)){}else{
                    session()->put('error', "Product Model not matched");
                    if(session('user')->hasPermission('correction_override') && $override){}else{
                        return redirect()->back();
                    }
                }
                if(($stock->variation->storage == $variant->storage) || ($variant->storage == 5 && in_array($stock->variation->storage,[0,6]) && $variant->product->brand == 2) || (in_array($variant->product_id, [78,58,59]) && $variant->storage == 4 && in_array($stock->variation->storage,[0,5]))){}else{
                    session()->put('error', "Product Storage not matched");
                    if(session('user')->hasPermission('correction_override') && $override){}else{
                        return redirect()->back();
                    }
                }
                if(!in_array($stock->variation->grade, [$variant->grade, 7, 9])){
                    session()->put('error', "Product Grade not matched");
                    if(session('user')->hasPermission('correction_override') && $override){}else{
                        return redirect()->back();
                    }
                }
                if($item->stock != null){
                    $previous =  " | Previous IMEI: " . $item->stock->imei . $item->stock->serial_number;
                }else{
                    $previous = null;
                }
                $stock->mark_sold($item->id, request('correction')['tester'], request('correction')['reason'].$previous, $override);
                // $stock->variation_id = $item->variation_id;
                // $stock->tester = request('correction')['tester'];
                // $stock->added_by = session('user_id');
                // if($stock->status == 1){
                //     $stock->status = 2;
                // }
                // $stock->save();
            }
            if($item->stock_id != null){
                if($item->stock->purchase_item){
                    $last_operation = Stock_operations_model::where('stock_id',$item->stock_id)->orderBy('id','desc')->first();
                    if($last_operation != null){
                        if($last_operation->new_variation_id == $item->stock->variation_id){
                            $last_variation_id = $last_operation->old_variation_id;
                        }else{
                            $last_variation_id = $last_operation->new_variation_id;
                        }
                    }else{
                        $last_variation_id = Order_item_model::where(['order_id'=>$item->stock->order_id,'stock_id'=>$item->stock_id])->first()->variation_id;
                    }
                    $item->stock->mark_available($item->id, $last_variation_id, request('correction')['reason']." ".$item->order->reference_id." ".$imei.$serial_number);
                    // $stock_operation = Stock_operations_model::create([
                    //     'stock_id' => $item->stock->id,
                    //     'order_item_id' => $item->id,
                    //     'old_variation_id' => $item->stock->variation_id,
                    //     'new_variation_id' => $last_variation_id,
                    //     'description' => request('correction')['reason']." ".$item->order->reference_id." ".$imei.$serial_number,
                    //     'admin_id' => session('user_id'),
                    // ]);
                    // $stock_operation->save();
                    // $item->stock->variation_id = $last_variation_id;
                    // if($item->stock->status == 2){
                    //     $item->stock->status = 1;
                    // }
                    // $item->stock->save();
                }

            }
            if(request('correction')['imei'] != ''){
                $item->stock_id = $stock->id;
                $item->linked_id = $stock->purchase_item->id;

                $stock_movement = Stock_movement_model::where(['stock_id'=>$stock->id, 'received_at'=>null])->first();
                if($stock_movement != null){
                    Stock_movement_model::where(['stock_id'=>$stock->id, 'received_at'=>null])->update([
                        'received_at' => Carbon::now(),
                    ]);
                }

                $message = "Hi, here is the correct IMEI/Serial number for this order. \n".$imei.$serial_number."\n Regards, \n" . session('fname');
                session()->put('success', $message);
                session()->put('copy', $message);
            }else{
                $item->stock_id = 0;
                $item->linked_id = null;
                session()->put('success', 'IMEI removed from Order');
            }
            $item->save();

        }else{
            session()->put('error', 'Permission Denied');
        }
        return redirect()->back();
    }

    public function replacement($london = 0, $allowed = 0){
        $item = Order_item_model::find(request('replacement')['item_id']);
        if(session('user')->hasPermission('replacement')){
            if(!$item->stock->order){
                session()->put('error', 'Stock not purchased');
                return redirect()->back();
            }
            $imei = request('replacement')['imei'];
            $serial_number = null;
            if(!ctype_digit($imei)){
                $serial_number = $imei;
                $imei = null;
            }

            $stock = Stock_model::where(['imei'=>$imei, 'serial_number'=>$serial_number])->first();
            if(!$stock){
                session()->put('error', 'Stock not found');
                return redirect()->back();
            }
            if($stock->id == $item->stock_id){
                session()->put('error', 'Stock same as previous');
                return redirect()->back();
            }
            if($stock->status != 1){
                session()->put('error', 'Stock already sold');
                return redirect()->back();
            }
            if($stock->order->status != 3){
                session()->put('error', 'Stock list awaiting approval');
                return redirect()->back();
            }
            if($stock->variation->storage != null){
                $storage = $stock->variation->storage_id->name . " - ";
            }else{
                $storage = null;
            }
            if($stock->variation->color != null){
                $color = $stock->variation->color_id->name . " - ";
            }else{
                $color = null;
            }
            if($item->variation->storage != null){
                $storage2 = $item->variation->storage_id->name . " - ";
            }else{
                $storage2 = null;
            }
            if($item->variation->color != null){
                $color2 = $item->variation->color_id->name . " - ";
            }else{
                $color2 = null;
            }
            if(($stock->variation->product_id == $item->variation->product_id) || ($item->variation->product_id == 144 && $stock->variation->product_id == 229) || ($item->variation->product_id == 142 && $stock->variation->product_id == 143) || ($item->variation->product_id == 54 && $stock->variation->product_id == 55) || ($item->variation->product_id == 55 && $stock->variation->product_id == 54) || ($item->variation->product_id == 58 && $stock->variation->product_id == 59) || ($item->variation->product_id == 59 && $stock->variation->product_id == 58) || ($item->variation->product_id == 200 && $stock->variation->product_id == 160)){
            }else{
                session()->put('error', "Product Model not matched");
                if($allowed == 0){
                    return redirect()->back();
                }
            }
            if(($stock->variation->storage == $item->variation->storage) || ($item->variation->storage == 5 && in_array($stock->variation->storage,[0,6]) && $item->variation->product->brand == 2) || (in_array($item->variation->product_id, [78,58,59]) && $item->variation->storage == 4 && in_array($stock->variation->storage,[0,5]))){
            }else{
                session()->put('error', "Product Storage not matched");
                if($allowed == 0){
                    return redirect()->back();
                }
            }

            if($london == 1){
                $return_order = Order_model::where(['reference_id'=>2999,'order_type_id'=>4])->first();
            }else{

                $return_order = Order_model::where(['order_type_id'=>4,'status'=>1])->first();
            }
            if(!$return_order){
                $return_order = Order_model::where(['order_type_id'=>4,'status'=>1])->first();
            }

            $check_return = Order_item_model::where(['linked_id'=>$item->id, 'reference_id'=>$item->order->reference_id])->first();
            if($check_return != null){
                $return_order = $check_return->order;
            }
            // if(in_array($item->stock->last_item()->order->order_type_id,[1,4])){
            //     $return_order = $item->stock->last_item()->order;
            // }
            if(!$return_order){
                session()->put('error', 'No Active Return Order Found');
                return redirect()->back();
            }

            $r_item = Order_item_model::where(['order_id'=>$return_order->id, 'stock_id' => $item->stock_id])->first();
            if($r_item){
                $grade = $r_item->variation->grade;

                $stock_operation = Stock_operations_model::where(['stock_id'=>$item->stock_id])->orderBy('id','desc')->first();
                $stock_operation->order_item_id = $r_item->id;
                $stock_operation->description = $stock_operation->description." | Order: ".$item->order->reference_id." | New IMEI: ".$imei.$serial_number;
                $stock_operation->save();
            }else{
                $grade = request('replacement')['grade'];
            }

            $variation = Variation_model::firstOrNew(['product_id' => $item->variation->product_id, 'storage' => $item->variation->storage, 'color' => $item->variation->color, 'grade' => $grade]);

            $variation->stock += 1;
            $variation->status = 1;
            $variation->save();


            // print_r($stock);
            if($r_item == null){
                $return_item = new Order_item_model();
                $return_item->order_id = $return_order->id;
                $return_item->reference_id = request('replacement')['id'];
                $return_item->variation_id = $variation->id;
                $return_item->stock_id = $item->stock_id;
                $return_item->quantity = 1;
                $return_item->currency = $item->order->currency;
                $return_item->price = $item->price;
                $return_item->status = 3;
                $return_item->linked_id = $item->id;
                $return_item->admin_id = session('user_id');
                $return_item->save();

                print_r($return_item);

                // session()->put('success','Item returned');

                $stock_operation = Stock_operations_model::create([
                    'stock_id' => $item->stock_id,
                    'order_item_id' => $return_item->id,
                    'old_variation_id' => $item->variation_id,
                    'new_variation_id' => $variation->id,
                    'description' => request('replacement')['reason']." | Order: ".$item->order->reference_id." | New IMEI: ".$imei.$serial_number,
                    'admin_id' => session('user_id'),
                ]);
                $item->stock->variation_id = $variation->id;
            }else{
                // session()->put('error','Item already returned');

            }
            $stock_operation_2 = Stock_operations_model::create([
                'stock_id' => $stock->id,
                'order_item_id' => $item->id,
                'old_variation_id' => $stock->variation_id,
                'new_variation_id' => $stock->variation_id,
                'description' => "Replacement | Order: ".$item->order->reference_id." | Old IMEI: ".$item->stock->imei.$item->stock->serial_number,
                'admin_id' => session('user_id'),
            ]);
            $item->stock->status = 1;
            $item->stock->save();

            // $stock->variation_id = $item->variation_id;
            $stock->tester = request('replacement')['tester'];
            $stock->added_by = session('user_id');
            if($stock->status == 1){
                $stock->status = 2;
            }
            $stock->save();

            $order_item = new Order_item_model();
            $order_item->order_id = Order_model::where(['reference_id'=>999,'order_type_id'=>5])->first()->id;
            $order_item->reference_id = $item->order->reference_id;
            $order_item->care_id = $item->id;
            if($allowed == 0){
                $order_item->variation_id = $item->variation_id;
            }else{
                $order_item->variation_id = $stock->variation_id;
            }
            $order_item->stock_id = $stock->id;
            $order_item->quantity = 1;
            $order_item->price = $item->price;
            $order_item->status = 3;
            $order_item->linked_id = $stock->last_item()->id;
            $order_item->admin_id = session('user_id');
            $order_item->save();

            $stock_movement = Stock_movement_model::where(['stock_id'=>$stock->id, 'received_at'=>null])->first();
            if($stock_movement != null){
                Stock_movement_model::where(['stock_id'=>$stock->id, 'received_at'=>null])->update([
                    'received_at' => Carbon::now(),
                ]);
            }


            $message = "Hi, here is the new IMEI/Serial number for this order. \n".$imei.$serial_number."\n Regards, \n" . session('fname');
            session()->put('success', $message);
            session()->put('copy', $message);
        }else{
            session()->put('error', 'Unauthorized');
        }
        return redirect()->back();
    }

    public function recheck($order_id, $refresh = false, $invoice = false, $tester = null, $data = false){

        $bm = new BackMarketAPIController();

        $order_model = new Order_model();
        $order_item_model = new Order_item_model();
        $currency_codes = Currency_model::pluck('id','code');
        $country_codes = Country_model::pluck('id','code');

        $orderObj = $bm->getOneOrder($order_id);
        if(!isset($orderObj->orderlines)){
            if($data == true){
                dd($orderObj);
            }

        }else{

            if($data == true){
                foreach($orderObj->orderlines as $orderline){
                    $item = Order_item_model::where('reference_id',$orderline->id)->first();
                    if($item->care_id != null){
                        dd($bm->getCare($item->care_id));
                    }
                }
                dd($orderObj);
            }


            $order_model->updateOrderInDB($orderObj, $invoice, $bm, $currency_codes, $country_codes);

            $order_item_model->updateOrderItemsInDB($orderObj, $tester, $bm);
            if($refresh == true){
                $order = Order_model::where('reference_id',$order_id)->first();

                $invoice_url = url('export_invoice').'/'.$order->id;
                // JavaScript to open two tabs and print
                echo '<script>
                var newTab2 = window.open("'.$invoice_url.'", "_blank");
                // var newTab1 = window.open("'.$order->delivery_note_url.'", "_blank");

                // newTab1.onload = function() {
                //     newTab1.print();
                // };

                newTab2.onload = function() {
                    newTab2.print();
                };

                window.close();
                </script>';
            }
        }
        // return redirect()->back();

    }
    public function import()
    {
        // $bm = new BackMarketAPIController();
        // // Replace 'your-excel-file.xlsx' with the actual path to your Excel file
        // $excelFilePath = storage_path(request('file'));

        // $data = Excel::toArray([], $excelFilePath)[0];
        // if(request('product') != null){
        //     foreach($data as $dr => $d){
        //         // $name = ;
        //     }
        // }else{

        //     // Print or use the resulting array
        //     // dd($data);
        //     $i = 0;
        //     foreach($data as $d){
        //         $orderObj = $bm->getOneOrder($d[1]);
        //         $this->updateBMOrder($d[1]);
        //         if($orderObj->state == 3){
        //             print_r($bm->shippingOrderlines($d[1],trim($d[6]),$orderObj->tracking_number));
        //             // $orderObj = $bm->getOneOrder($d[1]);
        //             // $this->updateBMOrder($d[1]);
        //             $i ++;
        //             print_r($orderObj);
        //             print_r($d[6]);
        //         }
        //         if($i == 100){break;}
        //     }
        // }

    }

    public function export()
    {
        // dd(request());
        // return Excel::download(new OrdersExport, 'your_export_file.xlsx');
        if(request('order') != null){
            $pdfExport = new OrdersExport();
            $pdfExport->generatePdf();
        }
            if(request('ordersheet') != null){
                return Excel::download(new OrdersheetExport, 'orders.xlsx');
            // echo "<script>window.close();</script>";
        }
        if(request('picklist') != null){
            $pdfExport = new PickListExport();
            $pdfExport->generatePdf();
        }
    }
    public function export_label()
    {
        // return Excel::download(new OrdersExport, 'your_export_file.xlsx');
        // dd(request('ids'));
        $pdfExport = new LabelsExport();
        $pdfExport->generatePdf();
    }
    public function export_note()
    {
        // return Excel::download(new OrdersExport, 'your_export_file.xlsx');

        $pdfExport = new DeliveryNotesExport();
        $pdfExport->generatePdf();
    }
    public function track_order($order_id){
        $order = Order_model::find($order_id);
        $orderObj = $this->updateBMOrder($order->reference_id, false, null, true);
        return redirect($orderObj->tracking_url);
    }
    public function getLabel($order_id, $data = false, $update = false)
    {
        $bm = new BackMarketAPIController();
        $this->updateBMOrder($order_id);
        $datas = $bm->getOrderLabel($order_id);
        if($update == true){
            // dd($datas);
            if($datas == null || $datas->results == []){
                // print_r($datas);
                echo 'Hello';
            }elseif($datas->results[0]->hubScanned == true){
                $order = Order_model::where('reference_id',$order_id)->first();
                $order->scanned = 1;
                if($order->delivered_at == null){
                    $order->delivered_at = Carbon::parse($datas->results[0]->dateDelivery);
                    // return $order->delivered_at;
                }
                $order->save();
            }
        }
        if($data == true){
            return $datas;
        }else{
            return redirect()->back();
        }
    }
    public function getapiorders($page = null)
    {

        if($page == 1){
            for($i = 1; $i <= 10; $i++){
                $j = $i*20;
                echo $url = url('refresh_order').'/'.$j;
                echo '<script>
                var newTab1 = window.open("'.$url.'", "_blank");
                </script>';
            }
            $this->updateBMOrdersAll($page);
        }else if($page){
            $this->updateBMOrdersAll($page);

        }else{
            $this->updateBMOrdersAll();

        }



            echo '<script>window.close();</script>';



    }

    public function updateBMOrdersNew($return = false)
    {
        // exec('nohup php artisan refresh:new > /dev/null &');
        // die;
        // return redirect()->back();
        $bm = new BackMarketAPIController();
        $resArray = $bm->getNewOrders();
        $orders = [];
        if ($resArray !== null) {
            foreach ($resArray as $orderObj) {
                if (!empty($orderObj)) {
                    foreach($orderObj->orderlines as $orderline){
                        $this->validateOrderlines($orderObj->order_id, $orderline->listing);
                    }
                    $orders[] = $orderObj->order_id;
                }
            }
            foreach($orders as $or){
                $this->updateBMOrder($or);
            }

        } else {
            echo 'No new orders (in state 0 or 1) exist!';
        }
        $orders2 = Order_model::whereIn('status',[0,1])->where('order_type_id',3)->get();
        foreach($orders2 as $order){
            $this->updateBMOrder($order->reference_id);
        }


        $last_id = Order_item_model::where('care_id','!=',null)->orderBy('reference_id','desc')->first()->care_id;
        $care = $bm->getAllCare(false, ['last_id'=>$last_id,'page-size'=>50]);
        // $care = $bm->getAllCare(false, ['page-size'=>50]);
        // print_r($care);
        $care_line = collect($care)->pluck('id','orderline')->toArray();
        $care_keys = array_keys($care_line);


        // Assuming $care_line is already defined from the previous code
        $careLineKeys = array_keys($care_line);

        // Construct the raw SQL expression for the CASE statement
        // $caseExpression = "CASE ";
        foreach ($care_line as $id => $care) {
            // $caseExpression .= "WHEN reference_id = $id THEN $care ";
            Order_item_model::where('reference_id',$id)->update(['care_id' => $care]);
        }

        if($return = true){
            session()->put('success',count($orders).' Orders Loaded Successfull');
            return redirect()->back();
        }


    }
    public function updateBMOrder($order_id = null, $invoice = false, $tester = null, $data = false, $bm = null, $care = false){
        if(request('reference_id')){
            $order_id = request('reference_id');
        }
        if($bm == null){
            $bm = new BackMarketAPIController();
        }

        $order_model = new Order_model();
        $order_item_model = new Order_item_model();
        $currency_codes = Currency_model::pluck('id','code');
        $country_codes = Country_model::pluck('id','code');

        $orderObj = $bm->getOneOrder($order_id);
        if(isset($orderObj->delivery_note)){

            if($orderObj->delivery_note == null){
                $orderObj = $bm->getOneOrder($order_id);
            }

            $order_model->updateOrderInDB($orderObj, $invoice, $bm, $currency_codes, $country_codes);

            $order_item_model->updateOrderItemsInDB($orderObj, $tester, $bm, $care);
        }else{
            session()->put('error','Order not Found');
        }
        if($data == true){
            return $orderObj;
        }else{
            return redirect()->back();
        }



    }
    public function updateBMOrdersAll($page = 1)
    {

        $bm = new BackMarketAPIController();

        $order_model = new Order_model();
        $order_item_model = new Order_item_model();
        $currency_codes = Currency_model::pluck('id','code');
        $country_codes = Country_model::pluck('id','code');



        $resArray = $bm->getAllOrders($page, ['page-size'=>50]);
        if ($resArray !== null) {
            // print_r($resArray);
            foreach ($resArray as $orderObj) {
                if (!empty($orderObj)) {
                // print_r($orderObj);
                $order_model->updateOrderInDB($orderObj, false, $bm, $currency_codes, $country_codes);
                $order_item_model->updateOrderItemsInDB($orderObj,null,$bm);
                // $this->updateOrderItemsInDB($orderObj);
                }
                // print_r($orderObj);
                // if($i == 0){ break; } else { $i++; }
            }
        } else {
            echo 'No orders have been modified in 3 months!';
        }
    }

    private function validateOrderlines($order_id, $sku, $validated = true)
    {
        $bm = new BackMarketAPIController();
        $end_point = 'orders/' . $order_id;
        $new_state = 2;

        // construct the request body
        $request = ['order_id' => $order_id, 'new_state' => $new_state, 'sku' => $sku];
        $request_JSON = json_encode($request);

        $result = $bm->apiPost($end_point, $request_JSON);

        return $result;
    }


}
