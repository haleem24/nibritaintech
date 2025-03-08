<?php

namespace App\Console\Commands;

use App\Http\Controllers\BackMarketAPIController;
use App\Http\Livewire\Order;
use App\Models\Api_request_model;
use App\Models\Color_model;
use App\Models\Country_model;
use App\Models\Listing_model;
use App\Models\Order_model;
use App\Models\Order_item_model;
use App\Models\Product_storage_sort_model;
use App\Models\Stock_model;
use App\Models\Variation_model;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class PriceHandler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'price:handler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {

        ini_set('max_execution_time', 1200);
        $error = '';
        $bm = new BackMarketAPIController();
        $listings = Listing_model::where('handler_status', 1)->get();
        $variation_ids = $listings->pluck('variation_id');
        $variations = Variation_model::whereIn('id', $variation_ids)->get();

        foreach ($variations as $variation) {
            echo "Hello";

            $responses = $bm->getListingCompetitors($variation->reference_uuid);

            foreach($responses as $list){
                if(is_string($list) || is_int($list)){
                    $error .= $list;
                    continue;
                }
                if(is_array($list)){
                    $error .= json_encode($list);
                    continue;
                }
                $country = Country_model::where('code',$list->market)->first();
                $listing = Listing_model::firstOrNew(['variation_id'=>$variation->id, 'country'=>$country->id]);
                $listing->reference_uuid = $list->product_id;
                if($list->price != null){
                    $listing->price = $list->price->amount;
                }
                if($list->min_price != null){
                    $listing->min_price = $list->min_price->amount;
                }
                $listing->buybox = $list->is_winning;
                $listing->buybox_price = $list->price_to_win->amount;
                $listing->buybox_winner_price = $list->winner_price->amount;
                $listing->save();

                if($listing->handler_status == 1 && $listing->bybox !== 1 && $listing->buybox_price > $listing->min_price_limit && ($listing->buybox_price < $listing->price_limit || $listing->price_limit == 0)){
                    $new_min_price = $listing->buybox_price - 2;
                    if($new_min_price < $listing->min_price_limit){
                        $new_min_price = $listing->min_price_limit;
                    }

                    if($new_min_price > $listing->price || $new_min_price < $listing->price*0.85){
                        $new_price = $new_min_price / 0.75;
                    }else{
                        $new_price = $listing->price;
                    }
                    $response = $bm->updateOneListing($listing->variation->reference_id,json_encode(['min_price'=>$new_min_price, 'price'=>$new_price]), $listing->country_id->market_code);
                    // echo $response;
                    $listing->price = $new_price;
                    $listing->min_price = $new_min_price;
                }elseif($listing->handler_status == 1 && $listing->bybox !== 1 && ($listing->buybox_price < $listing->min_price_limit || $listing->buybox_price > $listing->price_limit)){
                    $listing->handler_status = 2;
                }
                $listing->save();
            }
        }
        if($error != ''){
            $this->info($error);
        }

    }
}
