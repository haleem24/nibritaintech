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
                {{-- <a href="{{url('add_repair')}}" class="btn btn-success float-right"><i class="mdi mdi-plus"></i> Add Repair </a> --}}
                <a href="javascript:void(0);" class="btn btn-success float-right" data-bs-target="#modaldemo"
                data-bs-toggle="modal"><i class="mdi mdi-plus"></i> Add Repair </a>
                <a href="{{url('external_repair_receive')}}" onclick="window.open('{{ url('external_repair_receive') }}','print_popup','width=800,height=800');" class="btn btn-link">External Repair Receive</a>
                </div>
                <div class="justify-content-center mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item tx-15"><a href="/">Dashboards</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Repair</li>
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
                <div class="col-lg-4 col-xl-4 col-md-4 col-sm-6">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Reference ID</h4>
                    </div>
                    <input type="text" class="form-control" name="reference_id" placeholder="Enter Reference ID" value="@isset($_GET['reference_id']){{$_GET['reference_id']}}@endisset">
                </div>
                <div class="col-lg-4 col-xl-4 col-md-4 col-sm-6">
                    <div class="card-header">
                        <h4 class="card-title mb-1">{{ __('locale.Start Date') }}</h4>
                    </div>
                    <input class="form-control" name="start_date" id="datetimepicker" type="date" value="@isset($_GET['start_date']){{$_GET['start_date']}}@endisset">
                </div>
                <div class="col-lg-4 col-xl-4 col-md-4 col-sm-6">
                    <div class="card-header">
                        <h4 class="card-title mb-1">{{ __('locale.End Date') }}</h4>
                    </div>
                    <input class="form-control" name="end_date" id="datetimepicker" type="date" value="@isset($_GET['end_date']){{$_GET['end_date']}}@endisset">
                </div>
            </div>
            <div class=" p-2">
                <button class="btn btn-primary pd-x-20" type="submit">{{ __('locale.Search') }}</button>
                <a href="{{url('repair')}}" class="btn btn-default pd-x-20">Reset</a>
            </div>

            <input type="hidden" name="page" value="{{ Request::get('page') }}">
            <input type="hidden" name="per_page" value="{{ Request::get('per_page') }}">
            <input type="hidden" name="sort" value="{{ Request::get('sort') }}">
        </form>
        <br>
        <div class="row">
            <div class="col-md-12" style="border-bottom: 1px solid rgb(216, 212, 212);">
                <center><h4>Repair</h4></center>
            </div>
        </div>
        <br>

        <div class="d-flex justify-content-between">
            <div>
                <a href="{{url('repair')}}?status=1" class="btn btn-link @if (request('status') == 1) bg-white @endif ">Pending</a>
                <a href="{{url('repair')}}?status=2" class="btn btn-link @if (request('status') == 2) bg-white @endif ">Shipped</a>
                <a href="{{url('repair')}}?status=3" class="btn btn-link @if (request('status') == 3) bg-white @endif ">Closed</a>
                <a href="{{url('repair')}}" class="btn btn-link @if (!request('status')) bg-white @endif ">All</a>
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
                            <h5 class="card-title mg-b-0">{{ __('locale.From') }} {{$repairs->firstItem()}} {{ __('locale.To') }} {{$repairs->lastItem()}} {{ __('locale.Out Of') }} {{$repairs->total()}} </h5>

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
                                    <input type="hidden" name="reference_id" value="{{ Request::get('reference_id') }}">
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
                                        <th><small><b>Repairer</b></small></th>
                                        @if ((!request('status') || request('status') == 3) && session('user')->hasPermission('view_cost'))
                                        <th><small><b>Cost</b></small></th>
                                        @endif
                                        <th><small><b>Remaining Qty</b></small></th>
                                        <th><small><b>Creation Date</b></small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = $repairs->firstItem() - 1;
                                        $id = [];
                                    @endphp
                                    @foreach ($repairs as $index => $order)
                                        @php
                                            if(in_array($order->id,$id)){
                                                continue;
                                            }else {
                                                $id[] = $order->id;
                                            }
                                            $items = $order->process_stocks;
                                            $j = 0;
                                            // print_r($order);
                                        @endphp

                                        {{-- @foreach ($items as $itemIndex => $item) --}}
                                            <tr>
                                                    <td>{{ $i + 1 }}</td>
                                                    <td><a href="{{url('repair/detail/'.$order->id)}}">{{ $order->reference_id }}</a></td>
                                                    <td>{{ $repairers[$order->customer_id] ?? null }}</td>
                                                @if ((!request('status') || request('status') == 3) && session('user')->hasPermission('view_cost'))
                                                <td>Є{{ amount_formatter($order->process_stocks->sum('price'),2) }}</td>
                                                @endif
                                                <td>{{ $items->where('status',1)->count()."/".$items->count() }}@if ($order->status == 2)
                                                    (Pending)
                                                @endif</td>
                                                <td style="width:220px">{{ $order->created_at." ".$order->updated_at }}</td>
                                                <td>
                                                    <a href="javascript:void(0);" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fe fe-more-vertical  tx-18"></i></a>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="{{url('delete_repair') . "/" . $order->id }}"><i class="fe fe-arrows-rotate me-2 "></i>Delete</a>
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
                        {{ $repairs->onEachSide(1)->links() }} {{ __('locale.From') }} {{$repairs->firstItem()}} {{ __('locale.To') }} {{$repairs->lastItem()}} {{ __('locale.Out Of') }} {{$repairs->total()}}
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
                    <h5 class="modal-title mg-b-5">Add Repair Record</h5>
                    <hr>
                    <form action="{{ url('add_repair') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="repair[type]" id="" value="1">
                        <div class="form-group">
                            <label for="">Reference ID</label>

                            <input class="form-control" placeholder="input Reference No" name="repair[reference_id]" value="{{ $latest_reference + 1}}" type="text" required readonly>
                        </div>
                        <div class="form-group">
                            <label for="">Repairer</label>
                            <select class="form-select" placeholder="Input Repairer" name="repair[repairer]" required>
                                <option>Select Repairer</option>
                                @foreach ($repairers as $id=>$repairer)
                                    <option value="{{ $id }}">{{ $repairer }}</option>

                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="repair[status]" value="1">

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
