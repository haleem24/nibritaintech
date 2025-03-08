<?php

namespace App\Console\Commands;

use App\Jobs\UpdateOrderInDB;

use App\Http\Controllers\BackMarketAPIController;

use App\Models\Order_model;
use App\Models\Order_item_model;
use App\Models\Currency_model;
use App\Models\Country_model;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class RefreshOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Refresh:orders';

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
        $bm = new BackMarketAPIController();
        $order_model = new Order_model();
        $order_item_model = new Order_item_model();

        $currency_codes = Currency_model::pluck('id','code');
        $country_codes = Country_model::pluck('id','code');
        echo 1;
        $resArray1 = $bm->getNewOrders();
        if ($resArray1 !== null) {
            foreach ($resArray1 as $orderObj) {
                if (!empty($orderObj)) {
                    foreach($orderObj->orderlines as $orderline){
                        $this->validateOrderlines($orderObj->order_id, $orderline->listing);
                    }
                }
            }
        }
        echo 2;

            $modification = false;
        $resArray = $bm->getAllOrders(1, ['page-size'=>50], $modification);
        if ($resArray !== null) {
            // print_r($resArray);
            foreach ($resArray as $orderObj) {
                if (!empty($orderObj)) {
                // print_r($orderObj);
                $order_model->updateOrderInDB($orderObj, false, $bm, $currency_codes, $country_codes);
                $order_item_model->updateOrderItemsInDB($orderObj,null,$bm);
                echo 3;
                // Dispatch the job to update or insert data into the database
                // Serialize the payload data to compare
                // $serializedPayload = serialize([$orderObj]);

                // // Query the database to check if a job with the same payload already exists
                // if (!Job_model::where('payload', $serializedPayload)->exists()) {
                //     // Dispatch the job if it doesn't already exist
                //     UpdateOrderInDB::dispatch($orderObj);
                // }

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
