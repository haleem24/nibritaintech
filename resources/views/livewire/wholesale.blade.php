@extends('layouts.app')

    @section('styles')
        <style>
            .rows{
                border: 1px solid #016a5949;
            }
            .columns{
                background-color:#016a5949;
                padding-top:5px
            }
            .childs{
                padding-top:5px
            }
        </style>
    @endsection
<br>
    @section('content')

        <!-- breadcrumb -->
            <div class="breadcrumb-header justify-content-between">
                <div class="left-content">
                {{-- <span class="main-content-title mg-b-0 mg-b-lg-1">BulkSale</span> --}}
                <a href="javascript:void(0);" class="btn btn-success float-right" data-bs-target="#modaldemo"
                data-bs-toggle="modal"><i class="mdi mdi-plus"></i> Add BulkSale </a>
                </div>
                <div class="justify-content-center mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item tx-15"><a href="/">Dashboards</a></li>
                        <li class="breadcrumb-item active" aria-current="page">BulkSale</li>
                    </ol>
                </div>
            </div>
        <!-- /breadcrumb -->
        <div class="row">
            <div class="col-md-12" style="border-bottom: 1px solid rgb(216, 212, 212);">
                <center><h4>Search</h4></center>
            </div>
        </div>
        <br>
        <form action="" method="GET" id="search">
            <div class="row">
                <div class="col-lg-3 col-xl-3 col-md-3 col-sm-6">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Order ID</h4>
                    </div>
                    <input type="text" class="form-control" name="order_id" placeholder="Enter ID" value="@isset($_GET['order_id']){{$_GET['order_id']}}@endisset">
                </div>
                <div class="col-lg-3 col-xl-3 col-md-3 col-sm-6">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Purchaser</h4>
                    </div>
                    <select name="customer_id" class="form-control form-select" data-bs-placeholder="Select Purchaser">
                        <option value="">Purchaser</option>
                        @foreach ($vendors as $id => $vendor)
                            <option value="{{$id}}" @if(isset($_GET['customer_id']) && $id == $_GET['customer_id']) {{'selected'}}@endif>{{$vendor}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-xl-3 col-md-3 col-sm-6">
                    <div class="card-header">
                        <h4 class="card-title mb-1">{{ __('locale.Start Date') }}</h4>
                    </div>
                    <input class="form-control" name="start_date" id="datetimepicker" type="date" value="@isset($_GET['start_date']){{$_GET['start_date']}}@endisset">
                </div>
                <div class="col-lg-3 col-xl-3 col-md-3 col-sm-6">
                    <div class="card-header">
                        <h4 class="card-title mb-1">{{ __('locale.End Date') }}</h4>
                    </div>
                    <input class="form-control" name="end_date" id="datetimepicker" type="date" value="@isset($_GET['end_date']){{$_GET['end_date']}}@endisset">
                </div>
            </div>
            <div class=" p-2">
                <button class="btn btn-primary pd-x-20" type="submit">{{ __('locale.Search') }}</button>
                <a href="{{url('order')}}?per_page=10" class="btn btn-default pd-x-20">Reset</a>
            </div>

            <input type="hidden" name="page" value="{{ Request::get('page') }}">
            <input type="hidden" name="per_page" value="{{ Request::get('per_page') }}">
            <input type="hidden" name="sort" value="{{ Request::get('sort') }}">
        </form>
        <br>
        <div class="row">
            <div class="col-md-12" style="border-bottom: 1px solid rgb(216, 212, 212);">
                <center><h4>BulkSale</h4></center>
            </div>
        </div>
        <br>

        <div class="d-flex justify-content-between">
            <div>
                <a href="{{url('wholesale')}}?status=2" class="btn btn-link @if (request('status') == 2) bg-white @endif ">Pending</a>
                <a href="{{url('wholesale')}}?status=3" class="btn btn-link @if (request('status') == 3) bg-white @endif ">Shipped</a>
                <a href="{{url('wholesale')}}" class="btn btn-link @if (!request('status')) bg-white @endif ">All</a>
            </div>
            <div class="">
            </div>
        </div>
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
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h4 class="card-title mg-b-0"></h4>
                            <h5 class="card-title mg-b-0">{{ __('locale.From') }} {{$orders->firstItem()}} {{ __('locale.To') }} {{$orders->lastItem()}} {{ __('locale.Out Of') }} {{$orders->total()}} </h5>

                            <div class=" mg-b-0">
                                <form method="get" action="" class="row form-inline">
                                    <label for="perPage" class="card-title inline">per page:</label>
                                    <select name="per_page" class="form-select form-select-sm" id="perPage" onchange="this.form.submit()">
                                        <option value="10" {{ Request::get('per_page') == 10 ? 'selected' : '' }}>10</option>
                                        <option value="20" {{ Request::get('per_page') == 20 ? 'selected' : '' }}>20</option>
                                        <option value="50" {{ Request::get('per_page') == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ Request::get('per_page') == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                    {{-- <button type="submit">Apply</button> --}}
                                    <input type="hidden" name="start_date" value="{{ Request::get('start_date') }}">
                                    <input type="hidden" name="end_date" value="{{ Request::get('end_date') }}">
                                    <input type="hidden" name="status" value="{{ Request::get('status') }}">
                                    <input type="hidden" name="order_id" value="{{ Request::get('order_id') }}">
                                    <input type="hidden" name="customer_id" value="{{ Request::get('customer_id') }}">
                                    <input type="hidden" name="sku" value="{{ Request::get('sku') }}">
                                    <input type="hidden" name="imei" value="{{ Request::get('imei') }}">
                                    <input type="hidden" name="page" value="{{ Request::get('page') }}">
                                    <input type="hidden" name="sort" value="{{ Request::get('sort') }}">
                                </form>
                            </div>

                        </div>
                    </div>
                    <div class="card-body"><div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                                <thead>
                                    <tr>
                                        <th><small><b>No</b></small></th>
                                        <th><small><b>Order ID</b></small></th>
                                        <th><small><b>Purchaser</b></small></th>
                                        @if (session('user')->hasPermission('view_price'))
                                        <th><small><b>Cost</b></small></th>
                                        @endif
                                        <th><small><b>Qty</b></small></th>
                                        <th><small><b>Creation Date</b></small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = $orders->firstItem() - 1;
                                        $id = [];
                                    @endphp
                                    @foreach ($orders as $index => $order)
                                        @php
                                            if(in_array($order->id,$id)){
                                                continue;
                                            }else {
                                                $id[] = $order->id;
                                            }
                                            $j = 0;

                                            $price = $order->order_items_sum_price;

                                            // if($order->exchange_rate != null){
                                            //     $price = $price * $order->exchange_rate;
                                            // }
                                            // print_r($order);
                                        @endphp

                                        {{-- @foreach ($items as $itemIndex => $item) --}}
                                            <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td><a href="{{url('wholesale/detail/'.$order->id)}}">{{ $order->reference_id }}</a></td>
                                                <td>{{ $vendors[$order->customer_id] ?? "Walk In Customer" }}</td>
                                                @if (session('user')->hasPermission('view_price'))
                                                <td>€{{ amount_formatter($price,2) }}</td>
                                                @endif
                                                <td>{{ $order->order_items_count }}</td>
                                                <td style="width:220px">{{ $order->created_at }}</td>
                                                <td>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fe fe-more-vertical  tx-18"></i></a>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="{{url('delete_wholesale') . "/" . $order->id }}"><i class="fe fe-arrows-rotate me-2 "></i>Delete</a>
                                                        {{-- <a class="dropdown-item" href="{{ $order->delivery_note_url }}" target="_blank"><i class="fe fe-arrows-rotate me-2 "></i>Delivery Note</a>
                                                        <a class="dropdown-item" href="https://backmarket.fr/bo_merchant/orders/all?orderId={{ $order->reference_id }}&see-order-details={{ $order->reference_id }}" target="_blank"><i class="fe fe-caret me-2"></i>View in Backmarket</a> --}}
                                                        {{-- <a class="dropdown-item" href="javascript:void(0);"><i class="fe fe-trash-2 me-2"></i>Delete</a> --}}
                                                    </div>
                                                </td>
                                            </tr>
                                            {{-- @php
                                                $j++;
                                            @endphp
                                        @endforeach --}}
                                        @php
                                            $i ++;
                                        @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        <br>
                        {{ $orders->onEachSide(1)->links() }} {{ __('locale.From') }} {{$orders->firstItem()}} {{ __('locale.To') }} {{$orders->lastItem()}} {{ __('locale.Out Of') }} {{$orders->total()}}
                    </div>

                    </div>
                </div>
            </div>
        </div>

    <div class="modal" id="modaldemo">
        <div class="modal-dialog wd-xl-400" role="document">
            <div class="modal-content">
                <div class="modal-body pd-sm-40">
                    <button aria-label="Close" class="close pos-absolute t-15 r-20 tx-26" data-bs-dismiss="modal"
                        type="button"><span aria-hidden="true">&times;</span></button>
                    <h5 class="modal-title mg-b-5">Add BulkSale Record</h5>
                    <hr>
                    <form action="{{ url('add_wholesale') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="wholesale[type]" id="" value="5">
                        <div class="form-group">
                            <label for="">Reference ID</label>
                            <input class="form-control" placeholder="input Reference No" name="wholesale[reference_id]" type="text" value="{{$latest_reference+1}}" readonly required>
                        </div>
                        <div class="form-group">
                            <label for="">Purchaser</label>
                            <input type="text" list="vendors" class="form-control" placeholder="Input Vendor" name="wholesale[vendor]" required>
                            <datalist id="vendors">
                                <option>Select Vendor</option>
                                @foreach ($vendors as $id=>$vendor)
                                    <option value="{{ $vendor }}">{{ $vendor }}</option>

                                @endforeach
                            </datalist>
                        </div>
                        <input type="hidden" name="wholesale[status]" value="2">

                        <button class="btn btn-primary btn-block">{{ __('locale.Submit') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endsection

    @section('scripts')
		<!--Internal Sparkline js -->
		<script src="{{asset('assets/plugins/jquery-sparkline/jquery.sparkline.min.js')}}"></script>

		<!-- Internal Piety js -->
		<script src="{{asset('assets/plugins/peity/jquery.peity.min.js')}}"></script>

		<!-- Internal Chart js -->
		<script src="{{asset('assets/plugins/chartjs/Chart.bundle.min.js')}}"></script>

    @endsection
