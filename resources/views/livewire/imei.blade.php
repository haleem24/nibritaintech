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
                {{-- <span class="main-content-title mg-b-0 mg-b-lg-1">Search Serial</span> --}}
                </div>
                <div class="justify-content-center mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item tx-15"><a href="/">Dashboards</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Search Serial</li>
                    </ol>
                </div>
            </div>
        <!-- /breadcrumb -->
        <div class="row">
            <div class="col-md-12" style="border-bottom: 1px solid rgb(216, 212, 212);">
                <center><h4>Search Serial</h4></center>
            </div>
        </div>
        <br>

        <div class="d-flex justify-content-between">

            <div class="p-2">
                <form action="{{ url('imei')}}" method="GET" id="search" class="form-inline">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="imei" placeholder="Enter IMEI" value="@isset($_GET['imei']){{$_GET['imei']}}@endisset" id="imeiInput" onload="this.focus()" autofocus>
                        <label for="">IMEI</label>
                    </div>
                        <button class="btn btn-primary pd-x-20" type="submit">{{ __('locale.Search') }}</button>
                </form>
            <script>

                window.onload = function() {
                    document.getElementById('imeiInput').focus();
                    document.getElementById('imeiInput').click();
                    setTimeout(function(){ document.getElementById('imeiInput').focus();$('#imeiInput').focus(); }, 500);
                };
                document.addEventListener('DOMContentLoaded', function() {
                    var input = document.getElementById('imeiInput');
                    input.focus();
                    input.select();
                    document.getElementById('imeiInput').click();
                    setTimeout(function(){ document.getElementById('imeiInput').focus();$('#imeiInput').focus(); }, 500);
                });

            </script>
            </div>
            @if(isset($stock))
            <div class="p-2 d-flex justify-content-between">
                <a href="{{ url('imei/print_label').'?stock_id='.$stock->id}}" target="_blank" class="btn btn-secondary"><i class="fa fa-print"></i></a>
                @if (session('user')->hasPermission('rearrange_imei_order'))
                    <a href="{{ url('imei/rearrange').'/'.$stock->id}}" class="btn btn-secondary mx-1">Rearrange</a>
                @endif
                @if (session('user')->hasPermission('refund_imei') && isset($stock) && $stock->status == 2 && $stock->last_item()->order->order_type_id != 2)
                    <form action="{{ url('imei/refund').'/'.$stock->id}}" method="POST" id="refund" class="form-inline">
                        @csrf
                        <div class="form-floating">
                            <input type="text" class="form-control" name="description" placeholder="Enter Reason" id="description" required>
                            <label for="description">Reason</label>
                        </div>
                            <button class="btn btn-primary" type="submit">Refund</button>
                    </form>
                    &nbsp;&nbsp;
                @endif
                @if(session('user')->hasPermission('change_po_all') || (session('user')->hasPermission('change_po_old') && $stock->created_at->diffInDays() < 7 && $stock->added_by == session('user_id') && in_array($stock->order_id,[4739, 1, 5, 8, 9, 12, 13, 14, 185, 263, 8441])))

                    <form action="{{ url('imei/change_po').'/'.$stock->id}}" method="POST" id="change_po" class="form-inline" onsubmit="if (confirm('Are you sure you want to change the Purchase Vendor for this Stock?')){return true;}else{event.stopPropagation(); event.preventDefault();}">
                        @csrf
                        <select type="text" id="order" name="order_id" class="form-select wd-150" required>
                            <option value="">Vendor</option>
                            <option value="4739">Sunstrike</option>
                            <option value="1">Mobi</option>
                            <option value="5">Mudassir</option>
                            <option value="8">PCS Wireless</option>
                            <option value="9">PCS Wireless UAE</option>
                            <option value="12">PCS Wireless UK</option>
                            <option value="13">Cenwood</option>
                            <option value="14">US Mobile</option>
                            <option value="185">Waqas</option>
                            <option value="263">Wize</option>
                            <option value="8441">Others</option>
                            <option value="74291">10136</option>
                        </select>
                        <button class="btn btn-primary" type="submit">Change PO</button>
                    </form>
                @endif

            </div>
            @endif
        </div>
        @if (isset($stock))
            <h5 class="mb-0"> <small>Current Variation:&nbsp;&nbsp;</small> {{ $stock->variation->product->model ?? "Variation Issue"}}{{" - " . (isset($stock->variation->storage_id)?$stock->variation->storage_id->name . " - " : null) . (isset($stock->variation->color_id)?$stock->variation->color_id->name. " - ":null)}} <strong><u>{{ $stock->variation->grade_id->name ?? null }} {{ (isset($stock->variation->sub_grade_id)?$stock->variation->sub_grade_id->name:null)}}</u></strong></h5>
        @endif
        <div style="border-bottom: 1px solid rgb(216, 212, 212);">
        </div>
        <br>
        @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <span class="alert-inner--icon"><i class="fe fe-thumbs-up"></i></span>
            <span class="alert-inner--text"><strong>{{session('success')}}</strong></span>
            <button aria-label="Close" class="btn-close" data-bs-dismiss="alert" type="button"><span aria-hidden="true">&times;</span></button>
        </div>
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
        @php
        session()->forget('error');
        @endphp
        @endif
        @if (isset($stock))

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h4 class="card-title mg-b-0">
                                External Movement
                            </h4>

                            <div class=" mg-b-0">

                                @if (session('user')->hasPermission('imei_grade_correction'))
                                    <form action="{{ url('move_inventory/change_grade/true')}}" method="POST" class="form-inline">
                                        @csrf
                                        <select name="grade" class="form-select wd-150">
                                            <option value="">Move to</option>
                                            @foreach ($grades as $id => $grade)
                                                <option value="{{ $id }}">{{ $grade }}</option>
                                            @endforeach
                                        </select>
                                        <select name="sub_grade" class="form-select wd-150">
                                            <option value="">Sub Grade</option>
                                            @foreach ($grades as $id => $grade)
                                                <option value="{{ $id }}">{{ $grade }}</option>
                                            @endforeach
                                        </select>

                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="description" placeholder="Reason" style="width: 270px;">
                                            {{-- <input type="text" class="form-control" name="repair[imei]" placeholder="Enter IMEI" value="@isset($_GET['imei']){{$_GET['imei']}}@endisset"> --}}
                                            <label for="">Reason</label>
                                        </div>
                                        <input type="hidden" name="imei" value="{{ request('imei') }}">
                                        <button class="btn btn-secondary" type="submit">Move</button>
                                    </form>
                                @endif
                            </div>

                        </div>
                    </div>
                    <div class="card-body"><div class="table-responsive">
                        @if (isset($orders))

                            <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                                <thead>
                                    <tr>
                                        <th><small><b>No</b></small></th>
                                        <th width="100px"><small><b>Order ID</b></small></th>
                                        <th><small><b>Type</b></small></th>
                                        <th><small><b>Customer / Vendor</b></small></th>
                                        <th><small><b>Product</b></small></th>
                                        <th><small><b>Qty</b></small></th>
                                        <th><small><b>Price</b></small></th>
                                        <th><small><b>IMEI</b></small></th>
                                        <th><small><b>Creation Date | TN</b></small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 0;
                                        $id = [];
                                    @endphp
                                    @foreach ($orders as $index => $item)
                                        @php
                                            $order = $item->order;
                                            $j = 0;
                                        @endphp

                                        <tr>
                                            <td title="{{ $item->id }}">{{ $i + 1 }}</td>
                                            @if ($order->order_type_id == 1)
                                                <td><a href="{{url('purchase/detail/'.$order->id)}}?status=1">{{ $order->reference_id."\n\r".$vendor_grades[$item->reference_id ?? 0] }}</a></td>
                                            @elseif ($order->order_type_id == 2)
                                                <td><a href="{{url('rma/detail/'.$order->id)}}">{{ $order->reference_id."\n\r".$item->reference_id }}</a></td>
                                            @elseif ($order->order_type_id == 5 && $order->reference_id != 999)
                                                <td><a href="{{url('wholesale/detail/'.$order->id)}}">{{ $order->reference_id."\n\r".$item->reference_id }}</a></td>
                                            @elseif ($order->order_type_id == 5 && $order->reference_id == 999)
                                                <td><a href="https://www.backmarket.fr/bo_merchant/orders/all?orderId={{ $item->reference_id }}" target="_blank">Replacement <br> {{ $item->reference_id }}</a></td>
                                            @elseif ($order->order_type_id == 4)
                                                <td><a href="{{url('return/detail/'.$order->id)}}">{{ $order->reference_id."\n\r".$item->reference_id }}</a></td>
                                            @elseif ($order->order_type_id == 6)
                                                <td><a href="{{url('wholesale_return/detail/'.$order->id)}}">{{ $order->reference_id."\n\r".$item->reference_id }}</a></td>
                                            @elseif ($order->order_type_id == 3)
                                                <td><a href="https://www.backmarket.fr/bo_merchant/orders/all?orderId={{ $order->reference_id }}" target="_blank">{{ $order->reference_id."\n\r".$item->reference_id }}</a></td>
                                            @endif
                                            @if ($order->order_type_id == 3)
                                                <td>
                                                    <a href="{{ url('order').'?order_id='.$order->reference_id }}">{{ $order->order_type->name }}</a>
                                                </td>
                                            @else
                                                <td>{{ $order->order_type->name }}</td>
                                            @endif
                                            <td>@if ($order->customer)
                                                {{ $order->customer->first_name." ".$order->customer->last_name }}
                                            @endif</td>
                                            <td>
                                                @if ($item->variation ?? false)
                                                    <strong>{{ $item->variation->sku }}</strong>{{ " - " . $item->variation->product->model . " - " . (isset($item->variation->storage_id)?$item->variation->storage_id->name . " - " : null) . (isset($item->variation->color_id)?$item->variation->color_id->name. " - ":null)}} <strong><u>{{ $item->variation->grade_id->name ?? "Missing Grade" }} {{ $item->variation->sub_grade_id->name ?? "" }}</u></strong>
                                                @endif
                                                @if ($item->care_id != null && $order->order_type_id == 3)
                                                    <a class="" href="https://backmarket.fr/bo_merchant/customer-request/{{ $item->care_id }}" target="_blank"><strong class="text-danger">Conversation</strong></a>
                                                @endif
                                            </td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>
                                            @if ($order->order_type_id == 1 && session('user')->hasPermission('view_cost'))
                                                {{ $order->currency_id->sign.amount_formatter($item->price,2) }}
                                            @elseif (session('user')->hasPermission('view_price'))
                                                {{ $order->currency_id->sign.amount_formatter($item->price,2) }}
                                            @endif
                                            </td>
                                            @if ($order->status == 3)
                                            <td style="width:240px" class="text-success text-uppercase" title="{{ $item->stock_id }}" id="copy_imei_{{ $order->id }}">
                                                @isset($item->stock->imei) {{ $item->stock->imei }}&nbsp; @endisset
                                                @isset($item->stock->serial_number) {{ $item->stock->serial_number }}&nbsp; @endisset
                                                @isset($item->admin_id) | {{ $item->admin->first_name }} |
                                                @else
                                                @isset($order->processed_by) | {{ $order->admin->first_name }} | @endisset
                                                @endisset
                                                @isset($item->stock->tester) ({{ $item->stock->tester }}) @endisset
                                            </td>

                                            @endif
                                            @if ($order->status != 3)
                                            <td style="width:240px" title="{{ $item->stock_id }}">
                                                    <strong class="text-danger">{{ $order->order_status->name }}</strong>
                                                @isset($item->stock->imei) {{ $item->stock->imei }}&nbsp; @endisset
                                                @isset($item->stock->serial_number) {{ $item->stock->serial_number }}&nbsp; @endisset
                                                @isset($item->admin_id) | {{ $item->admin->first_name }} |
                                                @else
                                                @isset($order->processed_by) | {{ $order->admin->first_name }} | @endisset
                                                @endisset
                                                @isset($item->stock->tester) ({{ $item->stock->tester }}) @endisset
                                            </td>
                                            @endif
                                            <td style="width:220px">{{ $item->created_at}} <br> {{ $order->processed_at." ".$order->tracking_number }}</td>

                                            @if (session('user')->hasPermission('imei_delete_order_item'))
                                                <td>
                                                    <a href="{{url('imei/delete_order_item').'/'.$item->id}}" class="btn btn-link"><i class="fa fa-trash"></i></a>
                                                </td>
                                            @endif
                                        </tr>
                                        @php
                                            $i ++;
                                        @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                        <br>
                    </div>

                    </div>
                </div>
            </div>
        </div>

        @endif

        @if (isset($process_stocks) && $process_stocks->count() > 0)

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h4 class="card-title mg-b-0">
                                Repair History
                            </h4>

                            <div class=" mg-b-0">
                            </div>

                        </div>
                    </div>
                    <div class="card-body"><div class="table-responsive">

                        <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                            <thead>
                                <tr>
                                    <th><small><b>No</b></small></th>
                                    <th><small><b>Reference ID</b></small></th>
                                    <th><small><b>Repairer</b></small></th>
                                    <th><small><b>Price</b></small></th>
                                    <th><small><b>IMEI</b></small></th>
                                    <th><small><b>Status</b></small></th>
                                    <th><small><b>Creation Date | TN</b></small></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 0;
                                    $id = [];
                                @endphp
                                @foreach ($process_stocks as $index => $p_stock)
                                    @php
                                        $process = $p_stock->process;
                                        $j = 0;
                                    @endphp

                                        <tr>
                                            <td title="{{ $p_stock->id }}">{{ $i + 1 }}</td>
                                            <td><a href="{{url('repair/detail/'.$process->id)}}?status=1">{{ $process->reference_id }}</a></td>
                                            <td>@if ($process->customer)
                                                {{ $process->customer->first_name." ".$process->customer->last_name }}
                                            @endif</td>
                                            <td>
                                                {{ $process->currency_id->sign.amount_formatter($p_stock->price,2) }}
                                            </td>
                                            <td style="width:240px" class="text-success text-uppercase" title="{{ $p_stock->stock_id }}" id="copy_imei_{{ $process->id }}">
                                                @isset($p_stock->stock->imei) {{ $p_stock->stock->imei }}&nbsp; @endisset
                                                @isset($p_stock->stock->serial_number) {{ $p_stock->stock->serial_number }}&nbsp; @endisset
                                                @isset($p_stock->admin_id) | {{ $p_stock->admin->first_name }} |
                                                @else
                                                @isset($process->processed_by) | {{ $process->admin->first_name }} | @endisset
                                                @endisset
                                            </td>
                                            <td>@if ($p_stock->status == 1)
                                                Sent
                                                @else
                                                Received
                                            @endif</td>
                                            <td style="width:220px">{{ $p_stock->created_at}} <br> {{ $process->tracking_number }}</td>
                                        </tr>
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

        @if (isset($inventory_verifications) && $inventory_verifications->count() > 0)

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h4 class="card-title mg-b-0">
                                Inventory Verification History
                            </h4>

                            <div class=" mg-b-0">
                            </div>

                        </div>
                    </div>
                    <div class="card-body"><div class="table-responsive">

                        <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                            <thead>
                                <tr>
                                    <th><small><b>No</b></small></th>
                                    <th><small><b>Reference ID</b></small></th>
                                    <th><small><b>IMEI</b></small></th>
                                    {{-- <th><small><b>Status</b></small></th> --}}
                                    <th><small><b>Creation Date | TN</b></small></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 0;
                                    $id = [];
                                @endphp
                                @foreach ($inventory_verifications as $index => $p_stock)
                                    @php
                                        $process = $p_stock->process;
                                        $j = 0;
                                    @endphp

                                        <tr>
                                            <td title="{{ $p_stock->id }}">{{ $i + 1 }}</td>
                                            <td><a href="{{url('repair/detail/'.$process->id)}}?status=1">{{ $process->reference_id }}</a></td>
                                            {{-- <td>@if ($process->customer)
                                                {{ $process->customer->first_name." ".$process->customer->last_name }}
                                            @endif</td> --}}
                                            {{-- <td>
                                                {{ $process->currency_id->sign.amount_formatter($p_stock->price,2) }}
                                            </td> --}}
                                            <td style="width:240px" class="text-success text-uppercase" title="{{ $p_stock->stock_id }}" id="copy_imei_{{ $process->id }}">
                                                @isset($p_stock->stock->imei) {{ $p_stock->stock->imei }}&nbsp; @endisset
                                                @isset($p_stock->stock->serial_number) {{ $p_stock->stock->serial_number }}&nbsp; @endisset
                                                @isset($p_stock->admin_id) | {{ $p_stock->admin->first_name }} |
                                                @else
                                                @isset($process->processed_by) | {{ $process->admin->first_name }} | @endisset
                                                @endisset
                                            </td>
                                            {{-- <td>@if ($p_stock->status == 1)
                                                Sent
                                                @else
                                                Received
                                            @endif</td> --}}
                                            <td style="width:220px">{{ $p_stock->created_at}} <br> {{ $process->tracking_number }}</td>
                                        </tr>
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

        @if (isset($stocks))

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h4 class="card-title mg-b-0">
                                Internal Movement
                            </h4>

                            <div class=" mg-b-0">
                                Today's count: {{ count($stocks) }}
                            </div>

                        </div>
                    </div>
                    <div class="card-body"><div class="table-responsive">

                            <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                                <thead>
                                    <tr>
                                        <th><small><b>No</b></small></th>
                                        <th><small><b>Old Variation</b></small></th>
                                        <th><small><b>New Variation</b></small></th>
                                        <th><small><b>IMEI</b></small></th>
                                        <th><small><b>Vendor | Lot</b></small></th>
                                        <th><small><b>Reason</b></small></th>
                                        <th><small><b>Added By</b></small></th>
                                        <th><small><b>DateTime</b></small></th>
                                        @if (session('user')->hasPermission('delete_move'))
                                        <th><small><b>Delete</b></small></th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 0;
                                    @endphp
                                    @foreach ($stocks as $operation)

                                            <tr>
                                                <td title="{{ $operation->id }}">{{ $i + 1 }}</td>
                                                <td>
                                                    @if ($operation->old_variation ?? false)
                                                        <strong>{{ $operation->old_variation->sku }}</strong>{{ " - " . $operation->old_variation->product->model . " - " . (isset($operation->old_variation->storage_id)?$operation->old_variation->storage_id->name . " - " : null) . (isset($operation->old_variation->color_id)?$operation->old_variation->color_id->name. " - ":null)}} <strong><u>{{ (isset($operation->old_variation->grade_id)?$operation->old_variation->grade_id->name:null)}} {{ $operation->old_variation->sub_grade_id->name ?? "" }} </u></strong>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($operation->new_variation ?? false)
                                                        <strong>{{ $operation->new_variation->sku }}</strong>{{ " - " . $operation->new_variation->product->model . " - " . (isset($operation->new_variation->storage_id)?$operation->new_variation->storage_id->name . " - " : null) . (isset($operation->new_variation->color_id)?$operation->new_variation->color_id->name. " - ":null)}} <strong><u>{{ $operation->new_variation->grade_id->name ?? "Missing Grade" }} {{ $operation->new_variation->sub_grade_id->name ?? "" }}</u></strong>
                                                    @endif
                                                </td>
                                                <td>{{ $operation->stock->imei.$operation->stock->serial_number }}</td>
                                                <td>{{ $operation->stock->order->customer->first_name." | ".$operation->stock->order->reference_id }}</td>
                                                <td>{{ $operation->description }}</td>
                                                <td>{{ $operation->admin->first_name ?? null }}</td>
                                                <td>{{ $operation->created_at }}</td>
                                                @if (session('user')->hasPermission('delete_move') && $i == 0)

                                                <td>
                                                    <form method="POST" action="{{url('move_inventory/delete_move')}}">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $operation->id }}">
                                                        <button type="submit" class="btn btn-link"><i class="fa fa-trash"></i></button>
                                                    </form>

                                                </td>
                                                @endif
                                            </tr>
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
        @if (isset($stock_room) && $stock_room->count() > 0)

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h4 class="card-title mg-b-0">
                                Stock Room Movement
                            </h4>

                            <div class=" mg-b-0">
                                Total count: {{ count($stock_room) }}
                            </div>

                        </div>
                    </div>
                    <div class="card-body"><div class="table-responsive">

                            <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                                <thead>
                                    <tr>
                                        <th><small><b>No</b></small></th>
                                        <th><small><b>Product</b></small></th>
                                        <th><small><b>Exit At</b></small></th>
                                        <th><small><b>Exit By</b></small></th>
                                        <th><small><b>Description</b></small></th>
                                        <th><small><b>Received At</b></small></th>
                                        <th><small><b>Received By</b></small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 0;
                                    @endphp
                                    @foreach ($stock_room as $stock_r)
                                        @php
                                            $stoc = $stock_r->stock;
                                        @endphp
                                        <tr>
                                            @if ($stoc == null)
                                                {{$stock_r->stock_id}}
                                                @continue

                                            @endif
                                            <td title="{{ $stock_r->stock_id }}">{{ $i + 1 }}</td>
                                            <td>{{ $stoc->variation->product->model . " " . (isset($stoc->variation->storage) ? $storages[$stoc->variation->storage] . " " : null) . " " .
                                            (isset($stoc->variation->color) ? $colors[$stoc->variation->color] . " " : null) . $grades[$stoc->variation->grade] }}</td>
                                            <td>{{ $stock_r->exit_at }}</td>
                                            <td>{{ $stock_r->admin->first_name ?? null }}</td>
                                            <td>
                                                {{ $stock_r->description }}
                                            </td>
                                            <td>{{ $stock_r->received_at }}</td>
                                            <td>{{ $stock_r->receiver->first_name ?? null }}</td>

                                        </tr>

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
        @if (isset($test_results) && $test_results->count() > 0)
        <br>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h4 class="card-title mg-b-0">
                                Testing Report
                            </h4>


                        </div>
                    </div>
                    <div class="card-body"><div class="table-responsive">
                        <pre>
                        @foreach ($test_results as $result)
                            @php

                                $data = $result->request;
                                $datas = json_decode(json_decode(preg_split('/(?<=\}),(?=\{)/', $data)[0]));
                                echo "Test DateTime s: ".$result->created_at;
                                echo "<a href='".url('testing/repush/'.$result->id)."'> Repush Test</a><br>";
                                print_r($datas);
                            @endphp
                            @php
                                $i ++;
                            @endphp
                        @endforeach
                        </pre>
                    </div>

                    </div>
                </div>
            </div>
        </div>

        @endif

    @endsection

    @section('scripts')

		<!--Internal Sparkline js -->
		<script src="{{asset('assets/plugins/jquery-sparkline/jquery.sparkline.min.js')}}"></script>

		<!-- Internal Piety js -->
		<script src="{{asset('assets/plugins/peity/jquery.peity.min.js')}}"></script>

		<!-- Internal Chart js -->
		<script src="{{asset('assets/plugins/chartjs/Chart.bundle.min.js')}}"></script>

    @endsection
