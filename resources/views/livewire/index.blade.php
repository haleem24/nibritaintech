@extends('layouts.app')

    @section('styles')

		<!-- INTERNAL Select2 css -->
		<link href="{{asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet" />

		<!-- INTERNAL Data table css -->
		<link href="{{asset('assets/plugins/datatable/css/dataTables.bootstrap5.css')}}" rel="stylesheet" />
		<link href="{{asset('assets/plugins/datatable/css/buttons.bootstrap5.min.css')}}"  rel="stylesheet">
		<link href="{{asset('assets/plugins/datatable/responsive.bootstrap5.css')}}" rel="stylesheet" />
        <style>
            /* Tooltip container */
            .tooltip {
              position: relative;
              display: inline-block;
              border-bottom: 1px dotted black; /* If you want dots under the hoverable text */
            }

            /* Tooltip text */
            .tooltip .tooltiptext {
              visibility: hidden;
              width: 120px;
              background-color: black;
              color: #fff;
              text-align: center;
              padding: 5px 0;
              border-radius: 6px;

              /* Position the tooltip text - see examples below! */
              position: absolute;
              z-index: 1;
            }

            /* Show the tooltip text when you mouse over the tooltip container */
            .tooltip:hover .tooltiptext {
              visibility: visible;
            }
            </style>
    @endsection

    @section('content')
					<!-- breadcrumb -->
					<div class="breadcrumb-header justify-content-between">
						<div class="left-content">
						<span class="main-content-title mg-b-0 mg-b-lg-1">{{ __('locale.Dashboards') }}</span>
						</div>
                        @if (session('user')->hasPermission('available_stock_cost_summery'))
                            <a href="{{ url('index/stock_cost_summery') }}" target="_blank" class="btn btn-sm btn-primary">Stock Cost Summery</a>

                        @endif
						<div class="justify-content-center mt-2">
							<ol class="breadcrumb">
								<li class="breadcrumb-item active" aria-current="page">{{ __('locale.Dashboards') }}</li>
							</ol>
						</div>
					</div>
					<!-- /breadcrumb -->

        @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <span class="alert-inner--icon"><i class="fe fe-thumbs-up"></i></span>
            <span class="alert-inner--text"><strong>{{session('success')}}</strong></span>
            <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"><span aria-hidden="true">&times;</span></button>
        </div>
        <br>
        @php
        session()->forget('success');
        @endphp
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <span class="alert-inner--icon"><i class="fe fe-thumbs-down"></i></span>
                <span class="alert-inner--text"><strong>{{session('error')}}</strong></span>
                <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"><span aria-hidden="true">&times;</span></button>
            </div>
            <script>
                alert("{{session('error')}}");
            </script>
        <br>
        @php
        session()->forget('error');
        @endphp
        @endif
                    @if (session('user')->hasPermission('dashboard_search_options'))

                        <div class="row mb-3">

                            <div class="col-md">
                                <select name="category" class="form-control form-select" form="index">
                                    <option value="">Category</option>
                                    @foreach ($categories as $id=>$name)
                                        <option value="{{ $id }}" @if(isset($_GET['category']) && $id == $_GET['category']) {{'selected'}}@endif>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md">
                                <select name="brand" class="form-control form-select" form="index">
                                    <option value="">Brand</option>
                                    @foreach ($brands as $id=>$name)
                                        <option value="{{ $id }}" @if(isset($_GET['brand']) && $id == $_GET['brand']) {{'selected'}}@endif>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md">
                                <div class="form-floating">
                                    <input type="text" name="product" value="{{ Request::get('product') }}" class="form-control" data-bs-placeholder="Select Model" list="product-menu" form="index">
                                    <label for="product">Product</label>
                                </div>
                                <datalist id="product-menu">
                                    <option value="">Products</option>
                                    @foreach ($products as $id => $product)
                                        <option value="{{ $id }}" @if(isset($_GET['product']) && $id == $_GET['product']) {{'selected'}}@endif>{{ $product }}</option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md">
                                <div class="form-floating">
                                    <input type="text" name="sku" value="{{ Request::get('sku') }}" class="form-control" data-bs-placeholder="Select Model" form="index">
                                    <label for="sku">SKU</label>
                                </div>
                            </div>
                            <div class="col-md">
                                <select name="storage" class="form-control form-select" form="index">
                                    <option value="">Storage</option>
                                    @foreach ($storages as $id=>$name)
                                        <option value="{{ $id }}" @if(isset($_GET['storage']) && $id == $_GET['storage']) {{'selected'}}@endif>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md">
                                {{-- <div class="card-header">
                                    <h4 class="card-title mb-1">Storage</h4>
                                </div> --}}
                                <select name="color" class="form-control form-select" form="index">
                                    <option value="">Color</option>
                                    @foreach ($colors as $id=>$name)
                                        <option value="{{ $id }}" @if(isset($_GET['color']) && $id == $_GET['color']) {{'selected'}}@endif>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md">
                                {{-- <div class="card-header">
                                    <h4 class="card-title mb-1">Grade</h4>
                                </div> --}}
                                <select name="grade" class="form-control form-select" form="index">
                                    <option value="">Grade</option>
                                    @foreach ($grades as $id=>$name)
                                        <option value="{{ $id }}" @if(isset($_GET['grade']) && $id == $_GET['grade']) {{'selected'}}@endif>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                    @endif
                    @if (count($variations) > 0 && session('user')->hasPermission('update_variation'))

                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-header pb-0">
                                    <div class="d-flex justify-content-between">
                                        <h4 class="card-title mg-b-0">
                                            New Added Variations
                                        </h4>
                                    </div>
                                </div>
                                <div class="card-body"><div class="table-responsive">
                                        <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                                            <thead>
                                                <tr>
                                                    <th><small><b>No</b></small></th>
                                                    <th><small><b>Product</b></small></th>
                                                    <th><small><b>Name</b></small></th>
                                                    <th><small><b>SKU</b></small></th>
                                                    <th><small><b>Color</b></small></th>
                                                    <th><small><b>Storage</b></small></th>
                                                    <th><small><b>Grade</b></small></th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $i = 0;
                                                @endphp
                                                @foreach ($variations as $index => $product)
                                                    <form method="post" action="{{url('variation/update_product')}}/{{ $product->id }}" class="row form-inline">
                                                        @csrf
                                                    <tr>
                                                        <td>{{ $i + 1 }}</td>
                                                        <td>
                                                            <input type="text" name="update[product_id]" list="models" class="form-select form-select-sm" required>
                                                            <datalist id="models">
                                                                <option value="">None</option>
                                                                @foreach ($products as $id => $prod)
                                                                    <option value="{{ $id }}" {{ $product->product_id == $id ? 'selected' : '' }}>{{ $prod }}</option>
                                                                @endforeach
                                                            </datalist>
                                                        </td>
                                                        <td>{{ $product->name }}</td>
                                                        <td>{{ $product->sku }}</td>
                                                        <td>
                                                            <select name="update[color]" class="form-select form-select-sm">
                                                                <option value="">None</option>
                                                                @foreach ($colors as $id => $name)
                                                                    <option value="{{ $id }}" {{ $product->color == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="update[storage]" class="form-select form-select-sm">
                                                                <option value="">None</option>
                                                                @foreach ($storages as $id => $name)
                                                                    <option value="{{ $id }}" {{ $product->storage == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="update[grade]" class="form-select form-select-sm">
                                                                <option value="">None</option>
                                                                @foreach ($grades as  $id => $name)
                                                                    <option value="{{ $id }}" {{ $product->grade == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="submit" value="Update" class="btn btn-success">
                                                        </td>
                                                    </tr>
                                                    </form>

                                                    @php
                                                        $i ++;
                                                    @endphp
                                                @endforeach
                                            </tbody>
                                        </table>
                                    <br>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @endif
					<!-- row -->
					<div class="row">
						<div class="col-xl-5 col-lg-12 col-md-12 col-sm-12">

                            <div class="row me-0">
                                <div class="col-md">
                                    <div class="form-floating">
                                        <input class="form-control" id="datetimepicker" type="date" id="start" name="start_date" value="{{$start_date}}" form="index">
                                        <label for="start">{{ __('locale.Start Date') }}</label>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="form-floating">
                                        <input class="form-control" id="datetimepicker" type="date" id="end" name="end_date" value="{{$end_date}}" form="index">
                                        <label for="end">{{ __('locale.End Date') }}</label>
                                    </div>
                                </div>
                                <input type="hidden" name="per_page" value="{{ Request::get('per_page') }}">
                                    <button type="submit" class="btn btn-icon  btn-success me-1" name="data" value="1" form="index"><i class="fe fe-search"></i></button>
                                    <a href="{{ url('/') }}" class="btn btn-icon btn-danger me-1" form="index"><i class="fe fe-x"></i></a>
                            </div>
                            <form action="" method="GET" id="index">
                            </form>
                            @if (isset($add_ip) && $add_ip == 1)
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <h6>The IP you are logged in with is not known by the system. Will this be used by the team?</h6>
                                            <a href="{{ url('index/add_ip') }}" class="btn btn-primary">Yes</a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if (session('user')->hasPermission('dashboard_top_selling_products'))

                                <div class="card">
                                    <div class="card-header pb-0">
                                        <div class="d-flex justify-content-between">
                                            <h4 class="card-title ">Top Selling Products</h4>

                                            <form method="get" action="" class="row form-inline">
                                                <label for="perPage" class="card-title inline">Show:</label>
                                                <select name="per_page" class="form-select form-select-sm" id="perPage" onchange="this.form.submit()">
                                                    <option value="10" {{ Request::get('per_page') == 10 ? 'selected' : '' }}>10</option>
                                                    <option value="20" {{ Request::get('per_page') == 20 ? 'selected' : '' }}>20</option>
                                                    <option value="50" {{ Request::get('per_page') == 50 ? 'selected' : '' }}>50</option>
                                                    <option value="100" {{ Request::get('per_page') == 100 ? 'selected' : '' }}>100</option>
                                                </select>
                                                {{-- <button type="submit">Apply</button> --}}
                                                <input type="hidden" name="start_date" value="{{ $start_date }}">
                                                <input type="hidden" name="end_date" value="{{ $end_date }}">
                                                <input type="hidden" name="product" value="{{ Request::get('product') }}">
                                                <input type="hidden" name="sku" value="{{ Request::get('sku') }}">
                                                <input type="hidden" name="storage" value="{{ Request::get('storage') }}">
                                                <input type="hidden" name="color" value="{{ Request::get('color') }}">
                                                <input type="hidden" name="grade" value="{{ Request::get('grade') }}">
                                                <input type="hidden" name="category" value="{{ Request::get('category') }}">
                                                <input type="hidden" name="brand" value="{{ Request::get('brand') }}">
                                                <input type="hidden" name="data" value="{{ Request::get('data') }}">
                                            </form>
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <table class="table table-bordered table-hover text-md-nowrap">
                                            <thead>
                                                <tr>
                                                    <th><small><b>No</b></small></th>
                                                    <th><small><b>Product</b></small></th>
                                                    <th><small><b>Qty</b></small></th>
                                                    @if (session('user')->hasPermission('view_price'))
                                                        <th title="Only Shows average price for selected ranged EU orders"><small><b>Avg</b></small></th>
                                                    @endif
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $total = $top_products->sum('total_quantity_sold');
                                                    $weighted_average = 0;
                                                @endphp
                                                @foreach ($top_products as $top => $product)
                                                    @php
                                                        $weighted_average += $product->total_quantity_sold / $total * $product->average_price;
                                                        $variation = $product->variation;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $top+1 }}</td>
                                                        <td>{{ $products[$variation->product_id] ?? null }} - {{ $storages[$variation->storage] ?? null }} - {{ $colors[$variation->color] ?? null }} - {{ $grades[$variation->grade] ?? null }} - {{ $variation->sku ?? null }}</td>
                                                        <td>{{ $product->total_quantity_sold }}</td>
                                                        @if (session('user')->hasPermission('view_price'))
                                                        <td>€{{ amount_formatter($product->average_price,2) }}</td>
                                                        @endif

                                                        <td>
                                                            <a href="javascript:void(0);" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fe fe-more-vertical  tx-18"></i></a>
                                                            <div class="dropdown-menu">
                                                                {{-- <a class="dropdown-item" href="{{url('order')}}/refresh/{{ $order->reference_id }}"><i class="fe fe-arrows-rotate me-2 "></i>Refresh</a> --}}
                                                                {{-- <a class="dropdown-item" href="{{ $order->delivery_note_url }}" target="_blank"><i class="fe fe-arrows-rotate me-2 "></i>Delivery Note</a> --}}
                                                                <a class="dropdown-item" href="https://backmarket.fr/bo_merchant/listings/active?sku={{ $variation->sku }}" target="_blank"><i class="fe fe-caret me-2"></i>View Listing in BackMarket</a>
                                                                <a class="dropdown-item" href="{{url('order')}}?sku={{ $variation->sku }}&start_date={{ $start_date }}&end_date={{ $end_date }}" target="_blank"><i class="fe fe-caret me-2"></i>View Orders</a>
                                                                <a class="dropdown-item" href="https://backmarket.fr/bo_merchant/orders/all?sku={{ $variation->sku }}&startDate={{ $start_date }}&endDate={{ $end_date }}" target="_blank"><i class="fe fe-caret me-2"></i>View Orders in BackMarket</a>
                                                                {{-- <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-trash-2 me-2"></i>Delete</a> --}}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="2"><strong>Total:</strong></td>
                                                    <td title="Total"><strong>{{ $total }}</strong></td>
                                                    @if (session('user')->hasPermission('view_price'))
                                                    <td title="Weighted Average"><strong>€{{ amount_formatter($weighted_average,2) }}</strong></td>
                                                    @endif
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            @if (session('user')->hasPermission('dashboard_required_restock'))
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Restock Required based on 30 day sales</h4>
                                        <div class="d-flex justify-content-between">

                                            <div class="form-floating">
                                                <input class="form-control" type="number" id="days" name="days" value="{{ Request::get('days') ?? 30 }}" onchange="get_restock_data()">
                                                <label for="days">Days</label>
                                            </div>
                                            <div class="form-floating">
                                                <input class="form-control" type="number" id="difference" name="difference" value="{{ Request::get('difference') ?? 20 }}" onchange="get_restock_data()">
                                                <label for="difference">Difference %</label>
                                            </div>
                                            <div class="form-floating">
                                                <input class="form-control" type="number" id="min_sales" name="min_sales" value="{{ Request::get('min_sales') ?? 100 }}" onchange="get_restock_data()">
                                                <label for="min_sales">Min Sales</label>
                                            </div>
                                            <div class="form-floating">
                                                <input class="form-control" type="number" id="max_stock" name="max_stock" value="{{ Request::get('max_stock') ?? 100 }}" onchange="get_restock_data()">
                                                <label for="max_stock">Max Stock</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-bordered table-hover text-md-nowrap">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Product</th>
                                                    <th>Sales</th>
                                                    <th>Avg</th>
                                                    <th>Stock</th>
                                                </tr>
                                            </thead>
                                            <tbody id="required_restock">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
						</div>
						<div class="col-xl-7 col-lg-12 col-md-12 col-sm-12">
                            @if (session('user')->hasPermission('dashboard_view_testing_batches'))

                                <h5>Testing Batches:
                                    <span id="testing_batches" class="fa-sm">

                                    </span>
                                </h5>

                            @endif
                            <div class="row">
                                @if (session('user')->hasPermission('dashboard_view_total_orders'))

                                    <div class="col-md col-xs-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h4 class="card-title mb-1">Orders</h4>
                                            </div>
                                            <div class="card-body py-2" id="orders_data">
                                                <table class="w-100">
                                                    <tr>
                                                        <td>Total:</td>
                                                        <td class="tx-right"><a href="{{url('order')}}?start_date={{ $start_date }}&end_date={{ $end_date }}" title="EUR Average: {{ amount_formatter($ttl_average,2) }} | EUR: {{ amount_formatter($ttl_eur,2) }} | GBP: {{ amount_formatter($ttl_gbp,2) }} | Go to orders page">{{ $total_orders }}</a></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Pending:</td>
                                                        <td class="tx-right"><a href="{{url('order')}}?status=2&start_date={{ $start_date }}&end_date={{ $end_date }}" title="Go to orders page">{{ $pending_orders }}</a></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Conversation:</td>
                                                        <td class="tx-right"><a href="{{url('order')}}?care=1&start_date={{ $start_date }}&end_date={{ $end_date }}" title="Go to orders page">{{ $total_conversations }}</a></td>
                                                    </tr>
                                                    <tr>
                                                            {{-- alert(`
                                                            @foreach ($invoiced_orders_by_hour as $hours)
                                                                {{ \Carbon\Carbon::createFromFormat('H', $hours->hour)->format('h A') }}: {{ $hours->total }} | {{ $admins[$hours->processed_by] ?? 'Unknown' }}
                                                            @endforeach


                                                             `) --}}
                                                        <td>
                                                            <a href="#" data-bs-toggle="modal" data-bs-target="#invoiceByHour" title="Invoiced Orders by Hour">Invoiced:</a>
                                                        </td>
                                                        <td class="tx-right"><a href="{{url('order')}}?status=3&start_date={{ $start_date }}&end_date={{ $end_date }}" title="{{ $invoiced_items }} Total Items | {{ $missing_imei }} Dispatched without Device | Go to orders page">{{ $invoiced_orders }}</a></td>
                                                    </tr>
                                                    @if (session('user')->hasPermission('dashboard_view_totals'))
                                                    <tr>
                                                        <td title="Average Price">Average:</td>
                                                        <td class="tx-right"><a href="{{url('order')}}?status=3&start_date={{ $start_date }}&end_date={{ $end_date }}" title="Go to orders page">{{ amount_formatter($average,2) }}</a></td>
                                                    </tr>
                                                    <tr>
                                                        <td title="Total EUR Price">Total EUR:</td>
                                                        <td class="tx-right"><a href="{{url('order')}}?status=3&start_date={{ $start_date }}&end_date={{ $end_date }}" title="Go to orders page">{{ amount_formatter($total_eur,2) }}</a></td>
                                                    </tr>
                                                    <tr>
                                                        <td title="Total GBP Price">Total GBP:</td>
                                                        <td class="tx-right"><a href="{{url('order')}}?status=3&start_date={{ $start_date }}&end_date={{ $end_date }}" title="Go to orders page">{{ amount_formatter($total_gbp,2) }}</a></td>
                                                    </tr>
                                                    @endif
                                                </table>

                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if (session('user')->hasPermission('dashboard_view_testing'))

                                    <div class="col-md col-xs-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h4 class="card-title mb-1">Testing Count</h4>
                                            </div>
                                            <div class="card-body py-2">
                                                <table class="w-100">
                                                    @foreach ($testing_count as $testing)
                                                        @if ($testing->stock_operations_count > 0)

                                                        <tr>
                                                            <td>{{ $testing->first_name}}:</td>
                                                            <td class="tx-right"><a href="{{url('move_inventory')}}?start_date={{ $start_date }}&end_date={{ $end_date }}&adm={{ $testing->id }}" title="Go to Move Inventory page">{{ $testing->stock_operations_count }}</a></td>
                                                        </tr>
                                                        @endif
                                                    @endforeach
                                                </table>

                                            </div>
                                        </div>
                                    </div>

                                @endif

                                @if (session('user')->hasPermission('dashboard_view_aftersale_inventory'))

                                {{-- Date search section --}}
                                <div class="col-md col-xs-6">
                                    <div class="card">
                                        <div class="card-header border-bottom-0">
                                                <h3 class="card-title mb-0">Aftersale Inventory</h3> <span class="d-block tx-12 mb-0 text-muted"></span>
                                        </div>
                                        <div class="card-body py-2">
                                            <table class="w-100">
                                            @foreach ($aftersale_inventory as $inv)
                                                <tr>
                                                    <td>{{ $inv->grade }}:</td>
                                                    <td class="tx-right"><a href="{{url('inventory')}}?grade[]={{ $inv->grade_id }}&status={{ $inv->status_id }}&stock_status={{ $inv->stock_status }}" title="Go to orders page">{{ $inv->quantity }}</a></td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td title="Waiting for Approval">Returns:</td>
                                                <td class="tx-right"><a href="{{url('return')}}" title="Returns in Progress">{{$returns_in_progress}}</a></td>
                                            </tr>
                                            <tr>
                                                <td>RMA:</td>
                                                <td class="tx-right"><a href="{{url('inventory')}}?rma=1" title="Not Returned RMA">{{$rma}}</a></td>
                                            </tr>
                                            <tr>
                                                <td title="Awaiting Replacements">Replacements:</td>
                                                <td class="tx-right"><a href="{{url('inventory')}}?stock_status=1&replacement=1" title="Pending Replacements">{{$awaiting_replacement}}</a></td>
                                            </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif

                            </div>
								{{-- Welcome Box end --}}
								{{-- Date search section --}}
                            @if (session('user')->hasPermission('dashboard_view_inventory'))
                            <div class="card custom-card">
                                <div class="card-header border-bottom-0 d-flex justify-content-between">
                                    <h3 class="card-title mb-2 ">Available Inventory by Grade</h3>
                                    @if (session('user')->hasPermission('dashboard_view_listing_total'))
                                        <h3 class="card-title mb-2 ">Total Listed Inventory: {{ $listed_inventory }}</h3>
                                    @endif
                                </div>
                                <div class="card-body row">
                                    @foreach ($graded_inventory as $inv)
                                        <div class="col-lg-3 col-md-4"><h6><a href="{{url('inventory')}}?grade[]={{ $inv->grade_id }}&status={{ $inv->status_id }}" title="Go to orders page">{{ $inv->grade.": ".$inv->quantity." ".$purchase_status[$inv->status_id] }}</a></h6></div>
                                    @endforeach
                                </div>
                                @if (session('user')->hasPermission('dashboard_view_pending_orders'))
                                    <h6 class="tx-right mb-3">
                                        Pending Orders:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        @foreach ($pending_orders_count as $pending)
                                            <span title="Value: {{$pending->price}}">{{ $pending->order_type->name.": ".$pending->count }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                                        @endforeach
                                    </h6>
                                @endif
                            </div>
                            @endif
                            @if (session('user')->hasPermission('monthly_sales_chart'))
                                <div class="card custom-card overflow-hidden">
                                    <div class="card-header border-bottom-0">
                                        <div class="d-flex justify-content-between">
                                            <h3 class="card-title mb-2 ">Daily Orders for this month</h3>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="statistics2"></div>
                                    </div>
                                </div>
                            @endif
                            @if (session('user')->hasPermission('10_day_sales_chart'))

							<div class="card custom-card overflow-hidden">
								<div class="card-header border-bottom-0">
									<div class="d-flex justify-content-between">
										<h3 class="card-title mb-2 ">Sales for past 3 months</h3>
                                        <a href="{{url('index/refresh_sales_chart')}}">Refresh</a>
									</div>
								</div>
								<div class="card-body">
									<div id="statistics1"></div>
								</div>
							</div>
                            @endif
                            @if (session('user')->hasPermission('10_day_sales_chart'))

							<div class="card custom-card overflow-hidden">
								<div class="card-header border-bottom-0">
									<div class="d-flex justify-content-between">
										<h3 class="card-title mb-2 ">Sales for 7 Days</h3>
                                        <a href="{{url('index/refresh_7_days_chart')}}">Refresh</a>
									</div>
								</div>
								<div class="card-body">
									<div id="statistics4"></div>
								</div>
							</div>
                            @endif
                            @if (session('user')->hasPermission('7_day_progressive_sales_chart'))

							<div class="card custom-card overflow-hidden">
								<div class="card-header border-bottom-0">
									<div class="d-flex justify-content-between">
										<h3 class="card-title mb-2 ">Sales for 7 Days Progress</h3>
                                        <a href="{{url('index/refresh_7_days_progressive_chart')}}">Refresh</a>
									</div>
								</div>
								<div class="card-body">
									<div id="statistics5"></div>
								</div>
							</div>
                            @endif
						</div>
						<!-- </div> -->
					</div>
					<!-- row closed -->


        <div class="modal fade" id="invoiceByHour" tabindex="-1" aria-labelledby="invoiceByHourLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                    <h5 class="modal-title" id="invoiceByHourLabel">Invoiced Orders by Hour</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered table-hover text-md-nowrap">
                            <thead>
                                <tr>
                                    <th>Hour</th>
                                    <th>Orders</th>
                                    <th>Processed By</th>
                                </tr>
                            </thead>
                            <tbody id="invoiceByHourBody">
                                @foreach ($invoiced_orders_by_hour as $hours)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::createFromFormat('H', $hours->hour)->format('h A') }}</td>
                                        <td>{{ $hours->total }}</td>
                                        <td>{{ $admins[$hours->processed_by] ?? 'Unknown' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>


    @endsection

    @section('scripts')
		<!-- Internal Chart.Bundle js-->

        <script>
            function load_data(url){
                let data = [];
                $.ajax({
                    url: url,
                    type: 'GET',
                    async: false,
                    success: function(response){
                        data = response;
                    }
                });
                return data;
            }
        @if (session('user')->hasPermission('dashboard_view_total_orders'))

            function get_orders_data(){
                let orders_data = $('#orders_data');
                let params = {
                    start_date: "{{ $start_date }}",
                    end_date: "{{ $end_date }}",
                    category: "{{ Request::get('category') }}",
                    brand: "{{ Request::get('brand') }}",
                    product: "{{ Request::get('product') }}",
                    sku: "{{ Request::get('sku') }}",
                    storage: "{{ Request::get('storage') }}",
                    color: "{{ Request::get('color') }}",
                    grade: "{{ Request::get('grade') }}",
                    data: "{{ Request::get('data') }}",
                }
                let queryString = $.param(params);
                let data = load_data("{{ url('index/get_orders_data') }}"+'?'+queryString);
                // orders_data.html(data);
                console.log(data);

                let new_data = `
                    <table class="w-100">
                        <tr>
                            <td>Total:</td>
                            <td class="tx-right"><a href="{{url('order')}}?start_date={{ $start_date }}&end_date={{ $end_date }}" title="EUR Average: {{ amount_formatter($ttl_average,2) }} | EUR: {{ amount_formatter($ttl_eur,2) }} | GBP: {{ amount_formatter($ttl_gbp,2) }} | Go to orders page">{{ ${data.total_orders} }</a></td>
                        </tr>
                        <tr>
                            <td>Pending:</td>
                            <td class="tx-right"><a href="{{url('order')}}?status=2&start_date={{ $start_date }}&end_date={{ $end_date }}" title="Go to orders page">{{ ${data.pending_orders} }</a></td>
                        </tr>
                        <tr>
                            <td>Conversation:</td>
                            <td class="tx-right"><a href="{{url('order')}}?care=1&start_date={{ $start_date }}&end_date={{ $end_date }}" title="Go to orders page">{{ ${data.total_conversations} }</a></td>
                        </tr>
                        <tr>
                            <td>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#invoiceByHour" title="Invoiced Orders by Hour">Invoiced:</a>
                            </td>
                            <td class="tx-right"><a href="{{url('order')}}?status=3&start_date={{ $start_date }}&end_date={{ $end_date }}" title="{{ ${data.invoiced_items} }} Total Items | {{ ${data.missing_imei} }} Dispatched without Device | Go to orders page">{{ ${data.invoiced_orders} }</a></td>
                        </tr>
                        <tr>
                            <td title="Average Price">Average:</td>
                            <td class="tx-right"><a href="{{url('order')}}?status=3&start_date={{ $start_date }}&end_date={{ $end_date }}" title="Go to orders page">{{ amount_formatter(${data.average},2) }}</a></td>
                        </tr>
                        <tr>
                            <td title="Total EUR Price">Total EUR:</td>
                            <td class="tx-right"><a href="{{url('order')}}?status=3&start_date={{ $start_date }}&end_date={{ $end_date }}" title="Go to orders page">{{ amount_formatter(${data.total_eur},2) }}</a></td>
                        </tr>
                        <tr>
                            <td title="Total GBP Price">Total GBP:</td>
                            <td class="tx-right"><a href="{{url('order')}}?status=3&start_date={{ $start_date }}&end_date={{ $end_date }}" title="Go to orders page">{{ amount_formatter(${data.total_gbp},2) }}</a></td>
                        </tr>
                    </table>
                `;
                orders_data.html(new_data);
            }

            $(document).ready(function(){
                get_orders_data();
            });

        @endif

        @if (session('user')->hasPermission('dashboard_view_testing_batches'))
            function get_testing_batches(){
                let testing_batches = $('#testing_batches');
                let params = {
                    start_date: "{{ $start_date }}",
                    end_date: "{{ $end_date }}",
                }
                let queryString = $.param(params);
                let data = load_data("{{ url('index/get_testing_batches') }}"+'?'+queryString);
                testing_batches.html(data);
            }

            $(document).ready(function(){
                get_testing_batches();
            });

        @endif
        @if (session('user')->hasPermission('dashboard_required_restock'))
            function get_restock_data(){
                let params = {
                    days: $('#days').val(),
                    difference: $('#difference').val(),
                    min_sales: $('#min_sales').val(),
                    max_stock: $('#max_stock').val(),
                    category: "{{ Request::get('category') }}",
                    brand: "{{ Request::get('brand') }}",
                    product: "{{ Request::get('product') }}",
                    sku: "{{ Request::get('sku') }}",
                    storage: "{{ Request::get('storage') }}",
                    color: "{{ Request::get('color') }}",
                    grade: "{{ Request::get('grade') }}",
                    data: "{{ Request::get('data') }}",
                }
                let queryString = $.param(params);

                let restock = $('#required_restock');
                let i = 0;
                let new_data = ``;
                let data = load_data("{{ url('index/get_required_restock') }}"+ '?' + queryString);
                data.sort((a, b) => a.total_quantity_stocked - b.total_quantity_stocked || b.total_quantity_sold - a.total_quantity_sold);
                data.forEach(element => {
                    new_data += `
                        <tr>
                            <td>${i += 1}</td>
                            <td><a href="{{url('listing')}}?product=${element.product_id}&storage=${element.storage}&color=${element.color}&grade[]=${element.grade}" target="_blank">${element.variation}</a></td>
                            <td><a href="{{url('order')}}?sku=${element.sku}&start_date=${element.start_date}" target="_blank">${element.total_quantity_sold}</a></td>
                            <td>${element.average_price}</td>
                            <td><a href="{{url('inventory')}}?product=${element.product_id}&storage=${element.storage}&color=${element.color}&grade[]=${element.grade}" target="_blank">${element.total_quantity_stocked}</a></td>
                        </tr>
                    `;
                });
                restock.html(new_data);
            }
            $(document).ready(function(){
                get_restock_data();
            });
        @endif


        </script>
		<script src="{{asset('assets/plugins/chartjs/Chart.bundle.min.js')}}"></script>

		<!-- Moment js -->
		<script src="{{asset('assets/plugins/raphael/raphael.min.js')}}"></script>

		<!-- INTERNAL Apexchart js -->
		<script src="{{asset('assets/js/apexcharts.js')}}"></script>
		<script src="{{asset('assets/js/apexcharts.js')}}"></script>

		<!--Internal Sparkline js -->
		<script src="{{asset('assets/plugins/jquery-sparkline/jquery.sparkline.min.js')}}"></script>

		<!--Internal  index js -->
		<script src="{{asset('assets/js/index.js')}}"></script>

        <!-- Chart-circle js -->
		<script src="{{asset('assets/js/chart-circle.js')}}"></script>

		<!-- Internal Data tables -->
		<script src="{{asset('assets/plugins/datatable/js/jquery.dataTables.min.js')}}"></script>
		<script src="{{asset('assets/plugins/datatable/js/dataTables.bootstrap5.js')}}"></script>
		<script src="{{asset('assets/plugins/datatable/dataTables.responsive.min.js')}}"></script>
		<script src="{{asset('assets/plugins/datatable/responsive.bootstrap5.min.js')}}"></script>

		<!-- INTERNAL Select2 js -->
		<script src="{{asset('assets/plugins/select2/js/select2.full.min.js')}}"></script>
		<script src="{{asset('assets/js/select2.js')}}"></script>
    @endsection
