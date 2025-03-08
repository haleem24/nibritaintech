<?php

namespace App\Http\Livewire;

use App\Exports\PacksheetExport;
use App\Mail\BulksaleInvoiceMail;
use App\Models\Account_transaction_model;
use App\Models\Brand_model;
use App\Models\Category_model;
use Livewire\Component;
use App\Models\Variation_model;
use App\Models\Products_model;
use App\Models\Stock_model;
use App\Models\Order_model;
use App\Models\Order_item_model;
use App\Models\Order_status_model;
use App\Models\Customer_model;
use App\Models\Currency_model;
use App\Models\Color_model;
use App\Models\ExchangeRate;
use App\Models\Grade_model;
use App\Models\Order_issue_model;
use App\Models\Stock_operations_model;
use App\Models\Storage_model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use TCPDF;

class Wholesale extends Component
{

    public $imei;
    public $price;

    public function mount()
    {

    }
    public function render()
    {

        $user_id = session('user_id');
        $data['vendors'] = Customer_model::whereNotNull('is_vendor')->pluck('company','id');


        $data['title_page'] = "BulkSales";
        session()->put('page_title', $data['title_page']);
        $data['latest_reference'] = Order_model::where('order_type_id',5)->orderBy('reference_id','DESC')->first()->reference_id ?? 998;
        $data['order_statuses'] = Order_status_model::get();
            if(request('per_page') != null){
                $per_page = request('per_page');
            }else{
                $per_page = 10;
            }
            $data['orders'] = Order_model::withCount('order_items')->withSum('order_items','price')
            // select(
            //     'orders.id',
            //     'orders.reference_id',
            //     'orders.customer_id',
            //     'orders.currency',
            //     // DB::raw('SUM(order_items.price) as total_price'),
            //     // DB::raw('COUNT(order_items.id) as total_quantity'),
            //     'orders.created_at')
            ->where('orders.order_type_id',5)
            // ->join('order_items', 'orders.id', '=', 'order_items.order_id')

            ->when(request('start_date') != '', function ($q) {
                return $q->where('orders.created_at', '>=', request('start_date', 0));
            })
            ->when(request('end_date') != '', function ($q) {
                return $q->where('orders.created_at', '<=', request('end_date', 0) . " 23:59:59");
            })
            ->when(request('order_id') != '', function ($q) {
                return $q->where('orders.reference_id', 'LIKE', request('order_id') . '%');
            })
            ->when(request('customer_id') != '', function ($q) {
                return $q->where('orders.customer_id', request('customer_id'));
            })
            ->when(request('status') != '', function ($q) {
                return $q->where('orders.status', request('status'));
            })
            // ->groupBy('orders.id', 'orders.reference_id', 'orders.customer_id', 'orders.currency', 'orders.created_at')
            ->orderBy('orders.reference_id', 'desc') // Secondary order by reference_id
            // ->select('orders.*')
            ->paginate($per_page)
            ->onEachSide(5)
            ->appends(request()->except('page'));



        // dd($data['orders']);
        return view('livewire.wholesale')->with($data);
    }
    public function delete_order($order_id){

        $items = Order_item_model::where('order_id',$order_id)->get();
        foreach($items as $orderItem){
            if($orderItem->stock){
                // Access the variation through orderItem->stock->variation
                $variation = $orderItem->stock->variation;

                $variation->stock += 1;
                Stock_model::find($orderItem->stock_id)->update([
                    'status' => 1
                ]);
            }
            $orderItem->delete();
        }
        Order_model::where('id',$order_id)->delete();
        Order_issue_model::where('order_id',$order_id)->delete();
        session()->put('success', 'Order deleted successfully');
        return redirect(url('wholesale'));
    }
    public function delete_order_item($item_id){
        // dd($item_id);
        $orderItem = Order_item_model::find($item_id);

        // Access the variation through orderItem->stock->variation
        $variation = $orderItem->stock->variation;

        $variation->stock += 1;
        $variation->save();

        // No variation record found or product_id and sku are both null, delete the order item

        // $orderItem->stock->delete();
        Stock_model::find($orderItem->stock_id)->update(['status'=>1]);
        $orderItem->delete();
        // $orderItem->forceDelete();

        session()->put('success', 'Stock deleted successfully');

        return redirect()->back();
    }
    public function wholesale_approve($order_id){
        $order = Order_model::find($order_id);
        $currency = Currency_model::where('code',request('currency'))->first();
        if($currency != null && $currency->id != 4){
            $order->currency = $currency->id;
            $order->exchange_rate = request('rate');
        }
        $order->customer_id = request('customer_id');
        $order->reference = request('reference');
        $order->tracking_number = request('tracking_number');

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
            $transaction->transaction_type_id = 2;
            $transaction->status = 1;
            $transaction->description = $order->reference;
            $transaction->reference_id = $order->reference_id;
            $transaction->created_by = session('user_id');
            // $transaction->created_at = $order->created_at;

            $transaction->save();
        }

        if(request('approve') == 1){
            return redirect()->back();
        }else{
            return "Updated";
        }

    }
    public function wholesale_revert_status($order_id){
        $order = Order_model::find($order_id);
        $order->status -= 1;
        $order->save();
        return redirect()->back();
    }
    public function wholesale_detail($order_id){

        if(str_contains(url()->previous(),url('wholesale')) && !str_contains(url()->previous(),'detail')){
            session()->put('previous', url()->previous());
        }
        $data['title_page'] = "BulkSale Detail";
        session()->put('page_title', $data['title_page']);

        DB::statement("SET SESSION group_concat_max_len = 1000000;");
        $data['vendors1'] = Customer_model::whereNotNull('is_vendor')->pluck('company','id');
        $data['vendors'] = Customer_model::whereNotNull('is_vendor')->pluck('company','id');
        // $data['imeis'] = Stock_model::whereIn('status',[1,3])->orderBy('serial_number','asc')->orderBy('imei','asc')->get();
        $data['exchange_rates'] = ExchangeRate::pluck('rate','target_currency');
        $data['storages'] = Storage_model::pluck('name','id');
        $data['products'] = Products_model::pluck('model','id');
        $data['grades'] = Grade_model::pluck('name','id');
        $data['colors'] = Color_model::pluck('name','id');

        $variations = Variation_model::
        // whereHas('stocks', function ($query) use ($order_id) {
        //     $query->
        whereHas('order_items', function ($query) use ($order_id) {
            $query->where('order_id', $order_id);
            // });
        })
        // ->with([
        //     'stocks' => function ($query) use ($order_id) {
        //         $query->whereHas('order_item', function ($query) use ($order_id) {
        //             $query->where('order_id', $order_id);
        //         });
        //     },
        //     'stocks.order.customer'
        // ])
        ->
        orderBy('product_id', 'desc')
        ->get();
        // die;

        // Group by product_id and storage
        if(request('hide') != 'all'){

            $variations = $variations->groupBy(['product_id', 'storage']);
            $data['variations'] = $variations;
        }

        $order_items = Order_item_model::with(['stock','stock.order'])->where('order_id',$order_id)->get();
        $data['order_items'] = $order_items;
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

        $last_ten = Order_item_model::where('order_id',$order_id)->with(['variation','stock','stock.order.customer'])->orderBy('id','desc')->limit(10)->get();
        $data['last_ten'] = $last_ten;
        $data['all_variations'] = Variation_model::where('grade',9)->get();
        $data['order'] = Order_model::find($order_id);
        $data['order_id'] = $order_id;
        $data['currency'] = $data['order']->currency_id->sign;

            // die;
        // echo "<pre>";
        // // print_r($items->stocks);
        // print_r($items);

        // echo "</pre>";
        // dd($data['variations']);
        return view('livewire.wholesale_detail')->with($data);

    }

    public function update_prices(){
        // print_r(request('item_ids'));
        // echo request('unit_price');


        if(request('unit_price') > 0){
            foreach(request('item_ids') as $item_id){
                $item = Order_item_model::find($item_id);
                if($item->stock == null && $item->quantity > 1){
                    $item->price = request('unit_price') * $item->quantity;
                }else{
                    $item->price = request('unit_price');
                }
                $item->save();
            }
        }

        return redirect()->back();
    }

    public function add_wholesale(){
        // dd(request('wholesale'));
        $wholesale = (object) request('wholesale');
        $error = "";


        $customer = Customer_model::firstOrNew(['company' => $wholesale->vendor, ['is_vendor','!=',null] ]);
        if($customer->id == null){
            $customer->is_vendor = 2;
            $customer->type = 2;
        }
        $customer->save();

        $order = Order_model::firstOrNew(['reference_id' => $wholesale->reference_id, 'order_type_id' => 5 ]);
        $order->customer_id = $customer->id;
        $order->status = 2;
        $order->currency = 4;
        $order->order_type_id = 5;
        $order->processed_by = session('user_id');
        $order->save();

        // Delete the temporary file
        // Storage::delete($filePath);
        if($error != ""){

            session()->put('error', $error);
        }
        return redirect()->back();
    }

    public function check_wholesale_item($order_id, $imei = null, $variation_id = null, $back = null){
        $issue = [];
        if(request('imei')){
            $imei = request('imei');
        }
        if(ctype_digit($imei)){
            $i = $imei;
            $s = null;
        }else{
            $i = null;
            $s = $imei;
        }
        $stock = Stock_model::where(['imei' => $i, 'serial_number' => $s])->first();

        if($stock == null){
            session()->put('error', 'Stock Not Found');
            if($back != 1){
                return redirect()->back();
            }else{
                return 1;
            }
        }
        if(request('variation')){
            $variation_id = request('variation');
        }
        if($variation_id > 0){}else{
            $variation_id = $stock->variation_id;
        }
        $variation = Variation_model::find($variation_id);

        if($variation == null){
            session()->put('error', 'Variation Not Found');
            if($back != 1){
                return redirect()->back();
            }else{
                return 1;
            }
        }

        if($imei == '' || !$stock || $stock->status == null){
            session()->put('error', 'IMEI Invalid / Not Found');
            if($back != 1){
                return redirect()->back();
            }else{
                return 1;
            }

        }

        if($stock->variation->grade == 17){
            session()->put('error', "IMEI Flagged | Contact Admin");
            if($back != 1){
                return redirect()->back();
            }else{
                return 1;
            }
        }

        if($stock->status != 1){
            session()->put('error', "Stock Already Sold");
            if($back != 1){
                return redirect()->back();
            }else{
                return 1;
            }
        }
        if($stock->order->status == 2){
            session()->put('error', "Stock List Awaiting Approval");
            if($back != 1){
                return redirect()->back();
            }else{
                return 1;
            }
        }
        if(request('exclude_vendors') != null){
            if(in_array($stock->order->customer_id, request('exclude_vendors'))){
                session()->put('error', 'Stock belongs to the Excluded Vendor');
                if($back != 1){
                    return redirect()->back();
                }else{
                    return 1;
                }
            }
            session()->put('exclude_vendors', request('exclude_vendors'));
        }else{
            session()->forget('exclude_vendors');
        }
        $variation = Variation_model::where(['id' => $stock->variation_id])->first();
        if($stock->status != 1){
            session()->put('error', 'Stock already sold');
            if($back != 1){
                return redirect()->back();
            }else{
                return 1;
            }
        }

        if(request('bypass_check') == 1){

            $this->add_wholesale_item($order_id, $imei, $variation_id, $back);
            session()->put('bypass_check', 1);
            request()->merge(['bypass_check'=> 1]);
            if($back != 1){
                return redirect()->back();
            }else{
                return 1;
            }
        }else{
            session()->forget('bypass_check');
            // request()->merge(['bypass_check' => null]);
            if($variation->grade != 11){
                echo "<p>This IMEI does not belong to Wholesale. Do you want to continue?</p>";
                echo "<form id='continueForm' action='" . url('add_wholesale_item') . "/" . $order_id . "' method='POST'>";
                echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
                echo "<input type='hidden' name='order_id' value='" . $order_id . "'>";
                echo "<input type='hidden' name='imei' value='" . $imei . "'>";
                echo "</form>";
                echo "<a href='javascript:history.back()'>Cancel</a> ";
                echo "<button onclick='submitForm()'>Continue</button>";
                echo "<script>
                    function submitForm() {
                        document.getElementById('continueForm').submit();
                    }
                </script>";
                exit;
            }else{
                $this->add_wholesale_item($order_id, $imei, $variation_id, $back);
                if($back != 1){
                    return redirect()->back();
                }else{
                    return 1;
                }

            }
        }

    }
    public function add_wholesale_item($order_id, $imei = null, $variation_id = null, $back = null){
        if(request('imei')){
            $imei = request('imei');
        }
        if(request('variation')){
            $variation_id = request('variation');
        }
        if(!request('bypass_check')){
            session()->forget('bypass_check');
        }
        if(ctype_digit($imei)){
            $i = $imei;
            $s = null;
        }else{
            $i = null;
            $s = $imei;
        }

        $stock = Stock_model::where(['imei' => $i, 'serial_number' => $s])->first();

        $variation = Variation_model::where(['id' => $stock->variation_id])->first();

        if($stock->variation->grade != 11 && $stock->variation->grade > 6){
            $new_variation = Variation_model::firstOrNew(['product_id'=>$variation->product_id, 'storage'=>$variation->storage, 'color'=>$variation->color, 'grade'=>11]);
            $new_variation->status = 1;
            $new_variation->save();

            $stock_operation = Stock_operations_model::create([
                'stock_id' => $stock->id,
                'old_variation_id' => $stock->variation_id,
                'new_variation_id' => $new_variation->id,
                'description' => "Grade changed for Bulksale",
                'admin_id' => session('user_id'),
            ]);
            $stock->variation_id = $new_variation->id;
        }
        $stock->status = 2;
        $stock->save();

        $variation->stock -= 1;
        $variation->save();

        $order_item = new Order_item_model();
        $order_item->order_id = $order_id;
        $order_item->variation_id = $stock->variation_id;
        $order_item->stock_id = $stock->id;
        $order_item->quantity = 1;
        $order_item->price = $stock->purchase_item->price;
        $order_item->status = 3;
        $order_item->save();


        session()->put('success', 'Stock added successfully');


        // echo "<script>

        //     window.history.back();

        // </script>";
        // Delete the temporary file
        // Storage::delete($filePath);

        if($back != 1){
            return redirect(url('wholesale/detail').'/'.$order_id);
        }else{
            return 1;
        }
        // return redirect()->back();
    }
    public function remove_issues(){
        // dd(request('ids'));
        $ids = request('ids');
        $issues = Order_issue_model::whereIn('id',$ids)->get();
        if(request('remove_entries') == 1){
            $issues->delete();
        }
        if(request('insert_variation') == 1){
            $variation = request('variation');
            foreach($issues as $issue){
                $data = json_decode($issue->data);
                // echo $variation." ".$data->imei." ".$data->cost;
            session()->put('bypass_check', 1);
            request()->merge(['bypass_check'=> 1]);

                if($this->check_wholesale_item($issue->order_id, $data->imei, $variation, 1) == 1){
                    $issue->delete();
                }

            }
        }
        return redirect()->back();

    }
    public function add_wholesale_sheet($order_id){
        $issue = [];
        $storages = Storage_model::pluck('name','id')->toArray();

        $products = Products_model::pluck('model','id')->toArray();
        request()->validate([
            'sheet' => 'required|file|mimes:xlsx,xls',
        ]);

        // Store the uploaded file in a temporary location
        $filePath = request()->file('sheet')->store('temp');

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
        // Search for the lowercase version of the search value in the lowercase array
        $name = array_search('name', $arrayLower);
        if(!$name){
            print_r($dh);
            session()->put('error', "Heading not Found(name, imei)");
            return redirect()->back();
            // die;
        }
        // echo $name;
        $imei = array_search('imei', $arrayLower);
        // echo $imei;


        foreach($data as $dr => $d){
            // $name = ;
            // echo $dr." ";
            // print_r($d);
            $n = trim($d[$name]);
            if(ctype_digit($d[$imei])){
                $i = $d[$imei];
                $s = null;
            }else{
                $i = null;
                $s = $d[$imei];
            }
            if(trim($d[$imei]) == ''){
                continue;
            }
            if(trim($n) == ''){
                continue;
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
            // $last2 = end($names);
            // if($last2 == "5G"){
            //     array_pop($names);
            //     $n = implode(" ", $names);
            // }


            if(in_array(strtolower($n), array_map('strtolower',$products)) && ($i != null || $s != null)){
                $product = array_search(strtolower($n), array_map('strtolower',$products));
                $storage = $gb;

                // echo $product." ".$grade." ".$storage." | ";


                $stock = Stock_model::where(['imei' => $i, 'serial_number' => $s])->first();
                if($stock == null ){
                    if(isset($storages[$gb])){$st = $storages[$gb];}else{$st = null;}
                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    $issue[$dr]['data']['imei'] = $i.$s;
                    $issue[$dr]['message'] = 'Stock Not Found';

                    continue;


                }

                if($stock->variation->grade == 17){
                    if(isset($storages[$gb])){$st = $storages[$gb];}else{$st = null;}
                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    $issue[$dr]['data']['imei'] = $i.$s;
                    $issue[$dr]['message'] = 'IMEI Flagged | COntact Admin';

                    continue;
                }
                if($stock->order->status == 2){
                    if(isset($storages[$gb])){$st = $storages[$gb];}else{$st = null;}
                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    $issue[$dr]['data']['imei'] = $i.$s;
                    $issue[$dr]['message'] = 'Stock Awaiting Approval';

                    continue;
                }
                $variation = Variation_model::where(['id' => $stock->variation_id])->first();

                $variation_2 = Variation_model::where(['product_id' => $product, 'storage' => $storage])->pluck('id')->toArray();
                if(isset($storages[$gb])){$st = $storages[$gb];}else{$st = null;}
                if($variation_2 == []){

                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    $issue[$dr]['data']['imei'] = $i.$s;
                    $issue[$dr]['message'] = 'Product name not found';
                }elseif(!in_array($variation->id, $variation_2)){

                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    $issue[$dr]['data']['imei'] = $i.$s;
                    $issue[$dr]['message'] = 'Variation not matched';

                }elseif($stock->id != null && $stock->status == 2){
                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    $issue[$dr]['data']['imei'] = $i.$s;
                    if($stock->order_id == $order_id){
                        $issue[$dr]['message'] = 'Item Already Sold in this order';
                    }else{
                        $issue[$dr]['message'] = 'Item Already Sold';
                    }


                }elseif($stock->id == null ){
                    if(isset($storages[$gb])){$st = $storages[$gb];}else{$st = null;}
                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    $issue[$dr]['data']['imei'] = $i.$s;
                    $issue[$dr]['message'] = 'Stock Not Found';



                }else{
                    $stock->status = 2;
                    $stock->save();

                    $variation->stock -= 1;
                    $variation->save();

                    $order_item = Order_item_model::firstOrNew(['order_id' => $order_id, 'variation_id' => $variation->id, 'stock_id' => $stock->id]);
                    $order_item->quantity = 1;
                    $order_item->price = $stock->purchase_item->price;
                    $order_item->status = 3;
                    $order_item->save();



                }

            }else{
                if(isset($storages[$gb])){$st = $storages[$gb];}else{$st = null;}
                if($n != null){
                    $issue[$dr]['data']['row'] = $dr;
                    $issue[$dr]['data']['name'] = $n;
                    $issue[$dr]['data']['storage'] = $st;
                    $issue[$dr]['data']['imei'] = $i.$s;
                    if($i == null && $s == null){
                        $issue[$dr]['message'] = 'IMEI/Serial Not Found';
                    }else{
                        $issue[$dr]['message'] = 'Product Name Not Found';
                    }

                }
            }

        }


        if($issue != []){
            foreach($issue as $row => $datas){
                Order_issue_model::create([
                    'order_id' => $order_id,
                    'data' => json_encode($datas['data']),
                    'message' => $datas['message'],
                ]);
            }
        }

        return redirect()->back();
    }

    public function export_bulksale_invoice($order_id, $invoice = null)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');


        // Find the order
        $order = Order_model::with('customer', 'order_items')->find($order_id);

        if(request('packlist') != 1){
        $order_items = Order_item_model::
            join('variation', 'order_items.variation_id', '=', 'variation.id')
            ->join('products', 'variation.product_id', '=', 'products.id')
            ->select(
                // 'variation.id as variation_id',
                'products.model',
                // 'variation.color',
                'variation.storage',
                // 'variation.grade',
                DB::raw('AVG(order_items.price) as average_price'),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.price) as total_price')
            )
            ->where('order_items.order_id',$order_id)
            ->where('order_items.deleted_at',null)
            ->where('variation.deleted_at',null)
            ->where('products.deleted_at',null)
            ->groupBy('products.model', 'variation.storage')
            ->orderBy('products.model', 'ASC')
            ->get();
        }else{
            $order_items = [];
        }
        $order_items_2 = Order_item_model::
            join('variation', 'order_items.variation_id', '=', 'variation.id')
            ->join('products', 'variation.product_id', '=', 'products.id')
            ->select(
                'variation.id as variation_id',
                'products.model',
                'variation.color',
                'variation.storage',
                'variation.grade',
                DB::raw('AVG(order_items.price) as average_price'),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.price) as total_price')
            )
            ->where('order_items.order_id',$order_id)
            ->where('order_items.deleted_at',null)
            ->where('variation.deleted_at',null)
            ->where('products.deleted_at',null)
            ->groupBy('products.model', 'variation.id', 'variation.storage', 'variation.color', 'variation.grade')
            ->orderBy('products.model', 'ASC')
            ->get();

            // dd($order);
        // Generate PDF for the invoice content
        $data = [
            'order' => $order,
            'customer' => $order->customer,
            'order_items' =>$order_items,
            'order_items_2' =>$order_items_2,
            'invoice' => $invoice
        ];
        $data['storages'] = Storage_model::pluck('name','id');
        $data['grades'] = Grade_model::pluck('name','id');
        $data['colors'] = Color_model::pluck('name','id');

        // Create a new TCPDF instance
        $pdf = new TCPDF();

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        // $pdf->SetHeaderData('', 0, 'Invoice', '');

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('times', '', 12);

        // Additional content from your view
        if(request('packlist') == 1){
            $pdf->SetTitle('Bulksale Packlist '.$order->reference_id.' '.$order->customer->company.' '.$order_items_2->sum('total_quantity').' pcs');
            $filename = 'Bulksale Packlist '.$order->reference_id.' '.$order->customer->company.' '.$order_items_2->sum('total_quantity').' pcs.pdf';

            $html = view('export.bulksale_packlist', $data)->render();
        }elseif(request('packlist') == 2){

            return Excel::download(new PacksheetExport, 'BulkSale Packsheet '.$order->reference_id.' '.$order->customer->company.' '.$order_items_2->sum('total_quantity').' pcs.xlsx');


        }else{
            $pdf->SetTitle('Bulksale Invoice '.$order->reference_id.' '.$order->customer->company.' '.$order_items_2->sum('total_quantity').' pcs');
            $filename = 'Bulksale Invoice '.$order->reference_id.' '.$order->customer->company.' '.$order_items_2->sum('total_quantity').' pcs.pdf';
            $html = view('export.bulksale_invoice', $data)->render();
        }

        $pdf->writeHTML($html, true, false, true, false, '');

        // dd($pdfContent);
        // Send the invoice via email
        // Mail::to($order->customer->email)->send(new InvoiceMail($data));

        // Optionally, save the PDF locally
        // file_put_contents('invoice.pdf', $pdfContent);

        // Get the PDF content
        $pdf->Output($filename, 'I');

        // $pdfContent = $pdf->Output('', 'S');
        // Return a response or redirect

        // Pass the PDF content to the view
        // return view('livewire.show_pdf')->with(['pdfContent'=> $pdfContent, 'delivery_note'=>$order->delivery_note_url]);
    }

    public function bulksale_email($order_id){

        // Find the order
        $order = Order_model::with('customer', 'order_items')->find($order_id);

        $order_items = Order_item_model::
            join('variation', 'order_items.variation_id', '=', 'variation.id')
            ->join('products', 'variation.product_id', '=', 'products.id')
            ->select(
                'variation.id as variation_id',
                'products.model',
                'variation.color',
                'variation.storage',
                'variation.grade',
                DB::raw('AVG(order_items.price) as average_price'),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.price) as total_price')
            )
            ->where('order_items.order_id',$order_id)
            ->groupBy('variation.id','products.model', 'variation.color', 'variation.storage', 'variation.grade')
            ->orderBy('products.model', 'ASC')
            ->get();

        $order_items_2 = Order_item_model::
            join('variation', 'order_items.variation_id', '=', 'variation.id')
            ->join('products', 'variation.product_id', '=', 'products.id')
            ->select(
                'variation.id as variation_id',
                'products.model',
                'variation.color',
                'variation.storage',
                'variation.grade',
                DB::raw('AVG(order_items.price) as average_price'),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.price) as total_price')
            )
            ->where('order_items.order_id',$order_id)
            ->where('order_items.deleted_at',null)
            ->where('variation.deleted_at',null)
            ->where('products.deleted_at',null)
            ->groupBy('products.model', 'variation.id', 'variation.storage', 'variation.color', 'variation.grade')
            ->orderBy('products.model', 'ASC')
            ->get();

            // dd($order);
        // Generate PDF for the invoice content
        $data = [
            'order' => $order,
            'customer' => $order->customer,
            'order_items' =>$order_items,
            'order_items_2' =>$order_items_2,
            'invoice' => 1
        ];
        $data['storages'] = Storage_model::pluck('name','id');
        $data['grades'] = Grade_model::pluck('name','id');
        $data['colors'] = Color_model::pluck('name','id');


        Mail::to('accounts@nibritaintech.com')->send(new BulksaleInvoiceMail($data));
    }

    public function pos(){
        $data['title_page'] = "Point of Sale";
        session()->put('page_title', $data['title_page']);

        $data['categories'] = Category_model::orderBy('name')->pluck('name','id');
        $data['brands'] = Brand_model::orderBy('name')->pluck('name','id');
        $data['currencies'] = Currency_model::all();
        // $data['products'] = Products_model::orderBy('model')->pluck('model','id');
        // $data['storages'] = Storage_model::pluck('name','id');
        // $data['colors'] = Color_model::orderBy('name')->pluck('name','id');
        $data['grades'] = Grade_model::pluck('name','id');

        $data['cart'] = session()->get('cart', []);

        if(request('order_id') != null){
            $order = Order_model::find(request('order_id'));
            if(in_array($order->order_type_id,[1,5])){
                foreach($order->order_items as $item){
                    $variation = $item->variation;

                    $cartKey = $variation->product_id . '-' . $variation->storage . '-' . $variation->color . '-' . $variation->grade;

                    $data['cart'][$cartKey] = [
                        'product_name' => $variation->product->model,
                        'product_id' => $variation->product_id,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'storage' => $variation->storage,
                        'storage_name' => $variation->storage_id->name ?? null,
                        'color' => $variation->color,
                        'color_name' => $variation->color_id->name ?? null,
                        'grade' => $variation->grade,
                        'grade_name' => $variation->grade_id->name ?? null
                    ];
                }
                session()->put('cart', $data['cart']);
            }
            $data['order'] = $order;
        }

        return view('livewire.pos')->with($data);
    }

    public function get_products(){


        // $products = Products_model::withCount(['order_items' => function ($q) {
        //     $q->whereHas('order', function ($q) {
        //         $q->whereIn('order_type_id', [3,5])->where('created_at', '>=', now()->subDays(7));
        //     });
        // }])->orderBy('order_items_count', 'desc')->limit(20)->get();

        $products = Products_model::when(ctype_digit(request('category')) && request('category') > 0, function ($q) {
            return $q->where('products.category', request('category'));
        })
        ->when(ctype_digit(request('brand')) && request('brand') > 0, function ($q) {
            return $q->where('products.brand', request('brand'));
        })
        ->when(request('search') != 'null', function ($q) {
            return $q->where('products.model', 'LIKE', '%' . request('search') . '%');
        })
        ->limit(100)
        ->get();
        // ->paginate(100)
        // ->onEachSide(5)
        // ->appends(request()->except('page'));
        // dd($products);

        return response()->json($products);
    }

    public function get_product_variations($product_id){
        $variations = Variation_model::where('product_id',$product_id)
        ->when((request('storage') != '' && request('trigger') != 'storage'), function ($q) {
            return $q->where('storage', request('storage'));
        })
        ->when((request('color') != '' && request('trigger') != 'color'), function ($q) {
            return $q->where('color', request('color'));
        })
        ->when((request('grade') != '' && request('trigger') != 'grade'), function ($q) {
            return $q->where('grade', request('grade'));
        })
        ->get();

        $available_storages = Storage_model::whereIn('id',$variations->pluck('storage'))->pluck('name','id');
        $available_colors = Color_model::whereIn('id',$variations->pluck('color'))->pluck('name','id');
        $available_grades = Grade_model::whereIn('id',$variations->pluck('grade'))->pluck('name','id');

        $all_variations = Variation_model::where('product_id',$product_id)->get();

        $storages = Storage_model::whereIn('id',$all_variations->pluck('storage'))->pluck('name','id');
        $colors = Color_model::whereIn('id',$all_variations->pluck('color'))->pluck('name','id');
        $grades = Grade_model::whereIn('id',$all_variations->pluck('grade'))->pluck('name','id');

        $selected_storage = request('storage') ?? null;
        $selected_color = request('color') ?? null;
        $selected_grade = request('grade') ?? null;

        return response()->json(['variations'=>$variations, 'storages'=>$storages, 'colors'=>$colors, 'grades'=>$grades, 'available_storages'=>$available_storages, 'available_colors'=>$available_colors, 'available_grades'=>$available_grades, 'selected_storage'=>$selected_storage, 'selected_color'=>$selected_color, 'selected_grade'=>$selected_grade]);
    }


    // Add product to cart with additional details like storage, color, and grade
    public function add(Request $request)
    {
        $productName = $request->input('product_name');
        $productId = $request->input('product_id');
        $price = $request->input('price');
        $quantity = $request->input('quantity');
        $storage = $request->input('storage'); // Added storage option
        $storageName = $request->input('storage_name'); // Added storage option
        $color = $request->input('color');     // Added color option
        $colorName = $request->input('color_name');     // Added color option
        $grade = $request->input('grade');     // Added grade option
        $gradeName = $request->input('grade_name');     // Added grade option

        // Fetch the cart from the session (or create a new one if it doesn't exist)
        $cart = session()->get('cart', []);

        // Define a unique identifier for this product combination
        $cartKey = $productId . '-' . $storage . '-' . $color . '-' . $grade;

        // If product with the same storage, color, and grade is already in the cart, update its quantity
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            // Add new product to the cart
            $cart[$cartKey] = [
                'product_name' => $productName,
                'product_id' => $productId,
                'price' => $price,
                'quantity' => $quantity,
                'storage' => $storage,
                'storage_name' => $storageName,
                'color' => $color,
                'color_name' => $colorName,
                'grade' => $grade,
                'grade_name' => $gradeName
            ];
        }

        // Store the updated cart in the session
        session()->put('cart', $cart);

        return response()->json(['success' => true, 'message' => 'Product added to cart!', 'cart' => $cart]);
    }
    // Update product quantity and price with discount
    public function update(Request $request)
    {
        $request->validate([
            'cart_key' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $cartKey = $request->input('cart_key');
        $quantity = $request->input('quantity');
        $price = $request->input('price');
        $discount = $request->input('discount', 0); // Default discount is 0

        $cart = session()->get('cart', []);

        if (isset($cart[$cartKey])) {

            // Update the cart item
            $cart[$cartKey]['quantity'] = $quantity;
            $cart[$cartKey]['price'] = $price;
            if ($discount <= $cart[$cartKey]['price']) {
                $cart[$cartKey]['discount'] = $discount;
            }elseif($discount > $cart[$cartKey]['price']){
                $cart[$cartKey]['discount'] = $cart[$cartKey]['price'];
            }

            session()->put('cart', $cart);


            return response()->json([
                'success' => true,
                'message' => 'Cart updated!',
                'cart' => $cart
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Cart item not found.'
        ], 404);
    }


    // Remove product from cart
    public function remove(Request $request)
    {
        $cartKey = $request->input('cart_key');

        $cart = session()->get('cart', []);

        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
        }

        session()->put('cart', $cart);

        return response()->json(['success' => true, 'message' => 'Product removed from cart!', 'cart' => $cart]);
    }

    // View the cart
    public function view()
    {
        $cart = session()->get('cart', []);

        return response()->json(['cart' => $cart]);
    }

    // Checkout the cart
    public function checkout()
    {
        // return response()->json(['success' => true, 'message' => 'Checkout successful!', 'data' => request()->all()]);
        // $cart = request()->input('cart', []);
        $cart = session()->get('cart', []);
        $reference_id = request()->input('reference_id');
        $order_type = request()->input('mode');
        $customer_id = request()->input('customer_id');
        $currency = request()->input('currency');

        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'Cart is empty!']);
        }

        // Process checkout logic here (e.g., create an order)

        // Create a new order
        $order = Order_model::firstOrNew(['reference_id' => $reference_id, 'order_type_id' => $order_type]);
        $order->customer_id = $customer_id;
        $order->status = 1;
        $order->currency = $currency;
        $order->processed_by = session('user_id');
        $order->save();

        $item_ids = [];
        // Add items to the order
        foreach ($cart as $item) {
            $variation = Variation_model::firstOrNew(['product_id' => $item['product_id'], 'storage' => $item['storage'], 'color' => $item['color'], 'grade' => $item['grade']]);
            $variation->save();

            $order_item = Order_item_model::firstOrNew(['order_id' => $order->id, 'variation_id' => $variation->id]);
            $order_item->quantity = $item['quantity'];
            $order_item->price = $item['price'] * $item['quantity'];
            $order_item->discount = $item['discount'] ?? 0;
            $order_item->status = 1;
            $order_item->save();


            $item_ids[] = $order_item->id;
        }

        Order_item_model::where('order_id', $order->id)->whereNotIn('id', $item_ids)->delete();


        // Clear the cart after checkout
        // session()->forget('cart');

        return response()->json(['success' => true, 'message' => 'Checkout successful!', 'cart' => $cart, 'order' => $order, ]);
    }
}
