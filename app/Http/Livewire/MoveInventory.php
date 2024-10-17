<?php

namespace App\Http\Livewire;

use App\Models\Admin_model;
use App\Models\Color_model;
use Livewire\Component;
use App\Models\Stock_model;
use App\Models\Grade_model;
use App\Models\Order_model;
use App\Models\Products_model;
use App\Models\Stock_operations_model;
use App\Models\Storage_model;
use App\Models\Variation_model;
use Carbon\Carbon;


class MoveInventory extends Component
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

        $data['title_page'] = "Move Inventory";

        $data['admins'] = Admin_model::where('id','!=',1)->get();
        $data['products'] = Products_model::orderBy('model','asc')->get();
        $data['colors'] = Color_model::pluck('name','id');
        $data['storages'] = Storage_model::pluck('name','id');
        $data['grades'] = Grade_model::all();

        $start_date = Carbon::now()->startOfDay();
        $end_date = date('Y-m-d 23:59:59');
        if (request('start_date') != NULL && request('end_date') != NULL) {
            $start_date = request('start_date') . " 00:00:00";
            $end_date = request('end_date') . " 23:59:59";
        }

        if(request('grade')){
            session()->put('grade',request('grade'));
            session()->put('success',request('grade'));

        }
        $grade = session('grade');
        if(request('description')){
            session()->put('description',request('description'));
        }
        $per_page = 50;
        if(request('per_page')){
            $per_page = request('per_page');
        }


        $stocks = Stock_operations_model::when(request('search') != '', function ($q) {
                return $q->where('description', 'LIKE', '%' . request('search') . '%');
            })
            ->when((request('search') == '' || request('start_date') != NULL || request('end_date') != NULL), function ($q) use ($start_date,$end_date) {
                return $q->where('created_at','>=',$start_date)->where('created_at','<=',$end_date);
            })

            ->when(request('adm') != '', function ($q) {
                return $q->where('admin_id', request('adm'));
            })
            ->orderBy('id','desc');

        if(request('search') != ''){
            $stocks = $stocks->get();
        }else{
            $stocks = $stocks->where('description','!=','Grade changed for Sell')
            ->distinct('stock_id')
            ->paginate($per_page)
            ->onEachSide(5)
            ->appends(request()->except('page'));
        }


        $data['stocks'] = $stocks;
        $data['grade'] = Grade_model::find($grade);

        return view('livewire.move_inventory', $data); // Return the Blade view instance with data
    }

    public function change_grade(){
        $description = request('description');
        if(request('grade')){
            session()->put('grade',request('grade'));
        }
        session()->put('description',request('description'));


        if (request('imei')) {
            $imeis = explode(' ',request('imei'));
            foreach($imeis as $imei){

                if (ctype_digit($imei)) {
                    $i = $imei;
                    $s = null;
                } else {
                    $i = null;
                    $s = $imei;
                }

                $stock = Stock_model::where(['imei' => $i, 'serial_number' => $s])->first();
                if ($imei == '' || !$stock) {
                    session()->put('error', 'IMEI Invalid / Not Available');
                    // return redirect()->back();
                    continue;
                }
                if ($stock->order_id == null) {
                    session()->put('error', 'Stock Not Purchased');
                    // return redirect()->back();
                    continue;
                }
                $stock_id = $stock->id;

                $product_id = $stock->variation->product_id;
                $storage = $stock->variation->storage;
                $color = $stock->variation->color;
                $grade = $stock->variation->grade;
                if(session('user')->hasPermission('change_variation')){
                    if(request('product') != ''){
                        $product_id = request('product');
                    }
                    if(request('storage') != ''){
                        $storage = request('storage');
                    }
                    if(request('color') != ''){
                        $color = request('color');
                    }
                    if(request('price') != ''){
                        $price = request('price');
                        $p_order = $stock->purchase_item;

                        $description .= "Price changed from ".$p_order->price;
                        $p_order->price = $price;
                        $p_order->save();

                        // dd($p_order);
                    }
                }

                    if(request('grade') != ''){
                        $grade = request('grade');
                    }
                $new_variation = Variation_model::firstOrNew([
                    'product_id' => $product_id,
                    'storage' => $storage,
                    'color' => $color,
                    'grade' => $grade,
                ]);
                $new_variation->status = 1;
                if($new_variation->id && $stock->variation_id == $new_variation->id && request('price') == null){
                    session()->put('error', 'Stock already exist in this variation');
                    // return redirect()->back();
                    continue;

                }
                $new_variation->save();
                $stock_operation = Stock_operations_model::create([
                    'stock_id' => $stock_id,
                    'old_variation_id' => $stock->variation_id,
                    'new_variation_id' => $new_variation->id,
                    'description' => $description,
                    'admin_id' => session('user_id'),
                ]);
                $stock->variation_id = $new_variation->id;
                $stock->save();
            }

            // session()->put('added_imeis['.$grade.'][]', $stock_id);
            // dd($orders);
        }


        session()->put('success', 'Stock Sent Successfully');
        return redirect()->back();

    }
    public function delete_move(){
        $id = request('id');
        if(request('grade')){
            session()->put('grade',request('grade'));
        }
        if(request('description')){
        session()->put('description',request('description'));
        }

        if ($id != null) {
            $stock_operation = Stock_operations_model::find($id);
            $stock = $stock_operation->stock;
            $stock->variation_id = $stock_operation->old_variation_id;
            $stock->save();
            $stock_operation->delete();
        }


        session()->put('success', 'Stock Sent Back Successfully');
        return redirect()->back();

    }
    public function delete_multiple_moves(){
        $ids = request('ids');
        if(request('grade')){
            session()->put('grade',request('grade'));
        }
        session()->put('description',request('description'));


        if ($ids != null) {
            foreach($ids as $id){
                $stock_operation = Stock_operations_model::find($id);
                $stock = $stock_operation->stock;
                $stock->variation_id = $stock_operation->old_variation_id;
                $stock->save();
                $stock_operation->delete();
            }
        }


        session()->put('success', 'Stock Sent Back Successfully');
        return redirect()->back();

    }



}
