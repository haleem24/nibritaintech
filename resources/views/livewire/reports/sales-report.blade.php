
        <div class="card">
            <div class="card-header mb-0 pb-0 d-flex justify-content-between">
                <div class="mb-0">
                    <h4 class="card-title mb-0">Sales & Returns</h4>
                    <form class="form-inline" method="POST" target="print_popup" action="{{url('report')}}/pnl" onsubmit="window.open('about:blank','print_popup','width=1600,height=800');">
                        @csrf
                        <input type="hidden" name="start_date" value="{{$start_date}}">
                        <input type="hidden" name="end_date" value="{{$end_date}}">
                        <input type="hidden" name="product" value="{{ Request::get('product') }}">
                        <input type="hidden" name="vendor" value="{{ Request::get('vendor') }}">
                        <input type="hidden" name="batch" value="{{ Request::get('batch') }}">
                        <input type="hidden" name="storage" value="{{ Request::get('storage') }}">
                        <input type="hidden" name="color" value="{{ Request::get('color') }}">
                        <input type="hidden" name="grade" value="{{ Request::get('grade') }}">
                        <input type="hidden" name="category" value="{{ Request::get('category') }}">
                        <input type="hidden" name="brand" value="{{ Request::get('brand') }}">
                        <button class="btn btn-link" type="submit" name="bp" value="1">Profit & Loss by Products</button>
                        <button class="btn btn-link" type="submit" name="bc" value="1">Profit & Loss by Customers</button>
                        <button class="btn btn-link" type="submit" name="bv" value="1">Profit & Loss by Vendors</button>
                    </form>
                </div>
                <a href="" onclick="window.open('{{ url('report/ecommerce_report') }}','print_popup','width=1600,height=800');" class="btn btn-link"><i class="fe fe-shopping-cart"></i> E-Commerce Report</a>
                <div class="mb-0">

                    <form action="" method="GET" id="index" class="mb-0">
                        <div class="row">
                            <div class="col-xl-5 col-lg-5 col-md-5 col-xs-5">
                                <div class="form-floating">
                                    <input class="form-control" id="datetimepicker" type="date" id="start" name="start_date" value="{{$start_date}}">
                                    <label for="start">{{ __('locale.Start Date') }}</label>
                                </div>
                            </div>
                            <div class="col-xl-5 col-lg-5 col-md-5 col-xs-5">
                                <div class="form-floating">
                                    <input class="form-control" id="datetimepicker" type="date" id="end" name="end_date" value="{{$end_date}}">
                                    <label for="end">{{ __('locale.End Date') }}</label>
                                </div>
                            </div>
                            <div class="col-xl-2 col-lg-2 col-md-2 col-xs-2">
                                <button type="submit" class="btn btn-icon  btn-success me-1"><i class="fe fe-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body mt-0 pt-0">
                <form method="POST" id="stock_report" target="print_popup" action="{{ url('report/stock_report')}}" onsubmit="window.open('about:blank','print_popup','width=1600,height=800');">
                    @csrf
                    <input type="hidden" name="start_date" value="{{$start_date}}">
                    <input type="hidden" name="end_date" value="{{$end_date}}">
                </form>
                <table class="table table-bordered table-hover text-md-nowrap">
                    <thead>
                        <tr>
                            <th><small><b>No</b></small></th>
                            <th><small><b>Categories</b></small></th>
                            <th><small><b>Qty</b></small></th>
                            @if (session('user')->hasPermission('view_cost'))
                                <th title=""><small><b>Cost</b></small></th>
                                <th title=""><small><b>Repair</b></small></th>
                                <th title=""><small><b>Fee</b></small></th>
                            @endif
                            @if (session('user')->hasPermission('view_price'))
                            <th title=""><small><b>EUR Price</b></small></th>
                            <th title=""><small><b>GBP Price</b></small></th>
                            @endif
                            <th title=""><small><b>Profit</b></small></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $total_sale_orders = 0;
                            $total_approved_sale_orders = 0;
                            $total_sale_eur_items = 0;
                            $total_approved_sale_eur_items = 0;
                            $total_sale_gbp_items = 0;
                            $total_approved_sale_gbp_items = 0;
                            $total_sale_cost = 0;
                            $total_fee = 0;
                            $total_repair_cost = 0;
                            $total_eur_profit = 0;
                        @endphp
                        <tr>
                            <td colspan="9" align="center"><b>Sales</b></td>
                        </tr>
                        @foreach ($aggregated_sales as $s => $sales)
                            @php
                                $total_sale_orders += $sales->orders_qty;
                                // $total_approved_sale_orders += $sales->approved_orders_qty;
                                $total_sale_eur_items += $sales->eur_items_sum;
                                // $total_approved_sale_eur_items += $sales->eur_approved_items_sum;
                                $total_sale_gbp_items += $sales->gbp_items_sum;
                                // $total_approved_sale_gbp_items += $sales->gbp_approved_items_sum;
                                $total_sale_cost += $aggregated_sales_cost[$sales->category_id];
                                $total_repair_cost += $sales->items_repair_sum;
                                $total_fee += $sales->charges;
                                $total_eur_profit += $sales->eur_items_sum - $aggregated_sales_cost[$sales->category_id] - $sales->charges - $sales->items_repair_sum;
                            @endphp
                            <tr>
                                <td>{{ $s+1 }}</td>
                                <td>{{ $categories[$sales->category_id] }}</td>
                                <td><button class="btn btn-link py-0" form="stock_report" type="submit" name="stock_ids" value="{{$sales->stock_ids}}">{{ $sales->orders_qty }}</button></td>
                                @if (session('user')->hasPermission('view_cost'))
                                    <td title="{{count(explode(',',$sales->stock_ids))}}">€{{ amount_formatter($aggregated_sales_cost[$sales->category_id],2) }}</td>
                                    <td>€{{ amount_formatter($sales->items_repair_sum,2) }}</td>
                                    <td>{{ amount_formatter($sales->charges,2) }}</td>
                                @endif
                                @if (session('user')->hasPermission('view_price'))
                                    <td>€{{ amount_formatter($sales->eur_items_sum,2) }}</td>
                                    <td>£{{ amount_formatter($sales->gbp_items_sum,2) }}</td>
                                @endif
                                <td>€{{ amount_formatter($sales->eur_items_sum - $aggregated_sales_cost[$sales->category_id] - $sales->charges - $sales->items_repair_sum,2) }} + £{{ amount_formatter($sales->gbp_items_sum,2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2"><strong>Profit</strong></td>
                            <td><strong>{{ $total_sale_orders." (".$total_approved_sale_orders.")" }}</strong></td>
                            @if (session('user')->hasPermission('view_cost'))
                                <td title=""><strong>€{{ amount_formatter($total_sale_cost,2) }}</strong></td>
                                <td><strong>€{{ amount_formatter($total_repair_cost,2) }}</strong></td>
                                <td><strong>{{ amount_formatter($total_fee,2) }}</strong></td>
                            @endif
                            @if (session('user')->hasPermission('view_price'))
                                <td><strong>€{{ amount_formatter($total_sale_eur_items,2)." (€".amount_formatter($total_approved_sale_eur_items,2).")" }}</strong></td>
                                <td><strong>£{{ amount_formatter($total_sale_gbp_items,2)." (£".amount_formatter($total_approved_sale_gbp_items,2).")" }}</strong></td>
                            @endif
                            <td><strong>€{{ amount_formatter($total_eur_profit) }} + £{{ amount_formatter($total_sale_gbp_items,2) }}</strong></td>
                        </tr>

                        <tr>
                            <td colspan="9" align="center"><b>Returns</b></td>
                        </tr>
                        @php
                        $total_return_orders = 0;
                        $total_approved_return_orders = 0;
                        $total_return_eur_items = 0;
                        $total_approved_return_eur_items = 0;
                        $total_return_gbp_items = 0;
                        $total_approved_return_gbp_items = 0;
                        $total_return_cost = 0;
                        $total_repair_return_cost = 0;
                        $total_eur_loss = 0;
                        @endphp
                        @foreach ($aggregated_returns as $s => $returns)
                            @php
                                $total_return_orders += $returns->orders_qty;
                                $total_approved_return_orders += $returns->approved_orders_qty;
                                $total_return_eur_items += $returns->eur_items_sum;
                                // $total_approved_return_eur_items += $returns->eur_approved_items_sum;
                                $total_return_gbp_items += $returns->gbp_items_sum;
                                // $total_approved_return_gbp_items += $returns->gbp_approved_items_sum;
                                $total_return_cost += $aggregated_return_cost[$returns->category_id];
                                $total_repair_return_cost += $returns->items_repair_sum;
                                $total_eur_loss += $returns->eur_items_sum - $aggregated_return_cost[$returns->category_id] - $returns->items_repair_sum;
                            @endphp
                            <tr>
                                <td>{{ $s+1 }}</td>
                                <td>{{ $categories[$returns->category_id] }}</td>
                                <td>{{ $returns->orders_qty }}</td>
                                @if (session('user')->hasPermission('view_cost'))
                                    <td title="{{count(explode(',',$returns->stock_ids))}}">€{{ amount_formatter($aggregated_return_cost[$returns->category_id],2) }}</td>
                                    <td>€{{ amount_formatter($returns->items_repair_sum,2) }}</td>
                                    <td>{{ amount_formatter(0,2) }}</td>
                                @endif
                                @if (session('user')->hasPermission('view_price'))
                                    <td>€{{ amount_formatter($returns->eur_items_sum,2) }}</td>
                                    <td>£{{ amount_formatter($returns->gbp_items_sum,2) }}</td>
                                @endif
                                <td>€{{ amount_formatter(-$returns->eur_items_sum + $aggregated_return_cost[$returns->category_id] + $returns->items_repair_sum,2) }} + £{{ amount_formatter($returns->gbp_items_sum,2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2"><strong>Loss</strong></td>
                            <td><strong>{{ $total_return_orders }}</strong></td>
                            @if (session('user')->hasPermission('view_cost'))
                                <td title=""><strong>€{{ amount_formatter($total_return_cost,2) }}</strong></td>
                                <td><strong>€{{ amount_formatter($total_repair_return_cost,2) }}</strong></td>
                                <td><strong>{{ amount_formatter(0,2) }}</strong></td>
                            @endif
                            @if (session('user')->hasPermission('view_price'))
                                <td><strong>€{{ amount_formatter($total_return_eur_items,2) }}</strong></td>
                                <td><strong>£{{ amount_formatter($total_return_gbp_items,2) }}</strong></td>
                            @endif
                            <td><strong>€{{ amount_formatter($total_eur_loss) }} + £{{ amount_formatter($total_return_gbp_items,2) }}</strong></td>
                        </tr>
                        <tr>
                            <td colspan="2"><strong>Net</strong></td>
                            <td><strong>{{ $total_sale_orders-$total_return_orders }}</strong></td>
                            @if (session('user')->hasPermission('view_cost'))
                                <td title=""><strong>€{{ amount_formatter($total_sale_cost-$total_return_cost,2) }}</strong></td>
                                <td><strong>€{{ amount_formatter($total_repair_cost-$total_repair_return_cost,2) }}</strong></td>
                                <td><strong>{{ amount_formatter($total_fee,2) }}</strong></td>
                            @endif
                            @if (session('user')->hasPermission('view_price'))
                                <td><strong>€{{ amount_formatter($total_sale_eur_items-$total_return_eur_items,2) }}</strong></td>
                                <td><strong>£{{ amount_formatter($total_sale_gbp_items-$total_return_gbp_items,2) }}</strong></td>
                            @endif
                            <td><strong>€{{ amount_formatter($total_eur_profit-$total_eur_loss) }} + £{{ amount_formatter($total_sale_gbp_items-$total_return_gbp_items,2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

