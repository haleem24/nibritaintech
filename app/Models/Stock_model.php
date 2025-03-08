<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Stock_model extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'stock';
    protected $primaryKey = 'id';
    // public $timestamps = FALSE;
    protected $fillable = [
        // other fields...
        // 'reference_id',
        'imei',
        'serial_number',
        'status'
    ];
    public function variation()
    {
        return $this->belongsTo(Variation_model::class, 'variation_id', 'id');
    }
    public function product_storage_sort()
    {
        return $this->belongsToMany(Product_storage_sort_model::class);
    }

    public function admin()
    {
        return $this->hasOne(Admin_model::class, 'id', 'added_by');
    }
    public function all_orders()
    {
        return $this->hasManyThrough(Order_model::class, Order_item_model::class, 'stock_id', 'id', 'id', 'order_id');
    }


    public function latest_return()
    {
        return $this->hasOne(Order_item_model::class, 'stock_id', 'id')->whereHas('order', function ($q) {
            $q->whereIn('order_type_id', [4,6]);
        })->orderByDesc('id');
    }

    // public function latest_return()
    // {
    //     return $this->return_items()->orderBy('id', 'desc')->first();
    // }
    public function order_item()
    {
        return $this->hasMany(Order_item_model::class, 'stock_id', 'id');
    }
    public function order_items()
    {
        return $this->hasMany(Order_item_model::class, 'stock_id', 'id');
    }
    public function process_stocks()
    {
        return $this->hasMany(Process_stock_model::class, 'stock_id', 'id');
    }
    public function stock()
    {
        return $this->belongsTo(Stock_model::class, 'stock_id', 'id');
    }
    public function stock_verifications()
    {
        return $this->hasMany(Process_stock_model::class, 'stock_id', 'id')->whereHas('process', function ($q) {
            $q->where('process_type_id', 20);
        });
    }
    public function stock_repairs()
    {
        return $this->hasMany(Process_stock_model::class, 'stock_id', 'id')->whereHas('process', function ($q) {
            $q->where('process_type_id', 9);
        });
    }
    public function previous_repair()
    {
        return $this->hasOne(Process_stock_model::class, 'stock_id', 'id')->whereHas('process', function ($q) {
            $q->where('process_type_id', 9);
        })->orderByDesc('id')->skip(1)->take(1);

    }
    public function latest_repair()
    {
        return $this->hasOne(Process_stock_model::class, 'stock_id', 'id')->whereHas('process', function ($q) {
            $q->where('process_type_id', 9);
        })->orderByDesc('id');
    }
    public function latest_verification()
    {
        return $this->hasOne(Process_stock_model::class, 'stock_id', 'id')->whereHas('process', function ($q) {
            $q->where('process_type_id', 20);
        })->where('status',1)->orderByDesc('id');
    }
    public function process_stock($process_id)
    {
        // Define a custom method to retrieve only one order item
        return $this->hasOne(Process_stock_model::class, 'stock_id', 'id')->where('process_id', $process_id)->orderBy('id','desc')->first();
    }
    public function multi_process_stocks($process_ids)
    {
        return $this->hasMany(Process_stock_model::class, 'stock_id', 'id')->whereIn('process_id', $process_ids);
    }
    public function stock_operations()
    {
        return $this->hasMany(Stock_operations_model::class, 'stock_id', 'id');
    }
    public function latest_operation()
    {
        return $this->hasOne(Stock_operations_model::class, 'stock_id', 'id')
        // ->where('new_variation_id', $this->variation_id)
        ->orderByDesc('id');
    }
    public function stock_operation($process_id)
    {
        // Define a custom method to retrieve only one order item
        return $this->hasOne(Stock_operations_model::class, 'stock_id', 'id')->where('process_id', $process_id)->orderBy('id','desc')->first();
    }
    public function order()
    {
        return $this->hasOne(Order_model::class, 'id', 'order_id');
    }
    public function sale_order()
    {
        return $this->hasOne(Order_model::class, 'id', 'sale_order_id');
    }
    public function active_order()
    {
        return $this->hasOne(Order_model::class, 'id', 'order_id')->where('status',3);
    }
    public function first_item()
    {
        // Define a custom method to retrieve only one order item
        return $this->hasOne(Order_item_model::class, 'stock_id', 'id')->whereNull('linked_id');
    }
    public function purchase_item()
    {
        // Define a custom method to retrieve only one order item
        return $this->hasOne(Order_item_model::class, 'stock_id', 'id')->where('order_id', $this->order_id);
    }
    public function purchase_item_2()
    {
        // Define a custom method to retrieve only one order item
        return $this->hasOne(Order_item_model::class, 'stock_id', 'id')->where('order_id', $this->order_id)->first();
    }

    public function last_item(){

        $last_item = $this->purchase_item;
        if($last_item != null){

            while(Order_item_model::where(['linked_id'=>$last_item->id, 'stock_id'=>$this->id])->first()){
                $last_item = Order_item_model::where(['linked_id'=>$last_item->id, 'stock_id'=>$this->id])->first();
                // print_r($last_item);
            }
        }
        return $last_item;
    }
    public function sale_item($order_id)
    {
        // Define a custom method to retrieve only one order item
        return $this->hasOne(Order_item_model::class, 'stock_id', 'id')->where('order_id', $order_id)->orderByDesc('id')->first();
    }
    public function mark_sold($order_item_id, $tester = null, $message = null, $override = false)
    {
        $stock = $this;
        $order_item = Order_item_model::find($order_item_id);
        $order_id = $order_item->order_id;

        $stock->status = 2;
        $stock->sale_order_id = $order_id;

        if($tester != null){
            $stock->tester = $tester;
        }
        if($message == null){
            $message = 'Grade changed for Sell';
        }
        if($stock->variation_id != $order_item->variation_id){
            $operations = new Stock_operations_model();
            if($override){
                $operations->new_operation($stock->id, $order_item_id, null, null, $stock->variation_id, $stock->variation_id, $message);
            }else{
                $operations->new_operation($stock->id, $order_item_id, null, null, $stock->variation_id, $order_item->variation_id, $message);
                $stock->variation_id = $order_item->variation_id;
            }
        }
        $stock->save();
    }
    public function mark_available($item_id = null, $new_variation_id = null, $message = null)
    {
        $stock = $this;
        $stock->status = 1;
        $stock->sale_order_id = null;
        if($message == null){
            $message = 'Grade changed for Available';
        }
        if($new_variation_id != null){

            if($stock->variation_id != $new_variation_id){
                $operations = new Stock_operations_model();
                $operations->new_operation($stock->id, $item_id, null, null, $stock->variation_id, $new_variation_id, $message);
                $stock->variation_id = $new_variation_id;
            }
        }
        $stock->save();
    }

    public function availability(){
        $stock = $this;
        $last_item = $stock->last_item();

        $items2 = Order_item_model::where(['stock_id'=>$stock->id,'linked_id'=>null])->whereHas('order', function ($query) {
            $query->where('order_type_id', 1)->where('reference_id','<=',10009);
        })->orderBy('id','asc')->get();
        if($items2->count() > 1){
            $i = 0;
            foreach($items2 as $item2){
                $i ++;
                if($i == 1){
                    $stock->order_id = $item2->order_id;
                    $stock->save();
                }else{
                    $item2->delete();
                }
            }
            $last_item = $stock->last_item();
        }
        if($stock->purchase_item){

            $items3 = Order_item_model::where(['stock_id'=>$stock->id, 'linked_id' => $stock->purchase_item->id])->whereHas('order', function ($query) {
                $query->whereIn('order_type_id', [5,3]);
            })->orderBy('id','asc')->get();
            if($items3->count() > 1){
                $i = 0;
                foreach($items3 as $item3){
                    $i ++;
                    if($i == 1){
                    }else{
                    $item3->linked_id = null;
                        $item3->save();
                    }
                }
            }

        }
        $items4 = Order_item_model::where(['stock_id'=>$stock->id])->whereHas('order', function ($query) {
            $query->whereIn('order_type_id', [5,3]);
        })->orderBy('id','asc')->get();
        if($items4->count() == 1){
            foreach($items4 as $item4){
                if($stock->purchase_item){
                    if($item4->linked_id != $stock->purchase_item->id && $item4->linked_id != null){
                        $item4->linked_id = $stock->purchase_item->id;
                        $item4->save();
                    }
                }
            }
        }
        $items5 = Order_item_model::where(['stock_id'=>$stock->id,'linked_id'=>null])->whereHas('order', function ($query) {
            $query->whereIn('order_type_id', [2,3,4,5,6]);
        })->orderBy('id','asc')->get();
        if($items5->count() == 1){
            foreach($items5 as $item5){
                if($stock->last_item()){

                    $last_item = $stock->last_item();
                    $item5->linked_id = $last_item->id;
                    $item5->save();
                }
            }
                $last_item = $stock->last_item();
        }
            // if(session('user_id') == 1){
            //     dd($last_item);
            // }

            $stock_id = $stock->id;

        $process_stocks = Process_stock_model::where('stock_id', $stock_id)->whereHas('process', function ($query) {
            $query->where('process_type_id', 9);
        })->orderBy('id','desc')->get();
        $data['process_stocks'] = $process_stocks;

        if($last_item){

            if(in_array($last_item->order->order_type_id,[1,4,6])){
                $message = 'IMEI is Available';
                $type = 'success';
                // if($stock->status == 2){
                    if($process_stocks->where('status',1)->count() == 0){
                        $stock->status = 1;
                        $stock->save();
                    }else{
                        $stock->status = 2;
                        $stock->save();

                        $message = "IMEI sent for repair";
                        $type = 'warning';
                    }
                // }else{

                // }
            }else{
                $message = "IMEI Sold";
                $type = 'error';
                if($stock->status == 1){
                    $stock->status = 2;
                    $stock->save();
                }
            }
            if($stock->status == null){
                $message = "IMEI missing status";
                $type = 'error';
            }
                session()->put($type, $message);
        }
    }

}
