@extends('layouts.app')

    @section('styles')
    <!-- INTERNAL Select2 css -->
    <link href="{{asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet" />
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
    @section('content')


        <!-- breadcrumb -->
            <div class="breadcrumb-header justify-content-between">
                <div class="left-content">
                    {{-- <span class="ms-3 form-check form-switch ms-4">
                        <input type="checkbox" value="1" name="bypass_check" class="form-check-input" form="rma_item" @if (session('bypass_check') == 1) checked @endif>
                        <label class="form-check-label" for="bypass_check">Bypass Wholesale check</label>
                    </span> --}}
                <span class="main-content-title mg-b-0 mg-b-lg-1">RMA Order Detail</span><br>
                @if ($order->status == 1)
                <form class="form-inline" method="POST" action="{{url('rma/submit').'/'.$order->id}}" id="approveform">
                    @csrf
                    <div class="form-floating">
                        <input type="text" list="currencies" id="currency" name="currency" class="form-control" value="{{$order->currency_id->code}}">
                        <datalist id="currencies">
                            @foreach ($exchange_rates as $target_currency => $rate)
                                <option value="{{$target_currency}}" data-rate="{{$rate}}"></option>
                            @endforeach
                        </datalist>
                        <label for="currency">Currency</label>
                    </div>
                    <div class="form-floating">
                        <input type="text" class="form-control" id="rate" name="rate" placeholder="Enter Exchange Rate" value="{{$order->exchange_rate}}" >
                        <label for="rate">Exchange Rate</label>
                    </div>
                    <div class="form-floating">
                        <input type="text" class="form-control" id="reference" name="reference" placeholder="Enter Vendor Reference" value="{{$order->reference}}" onchange="submitForm()" required>
                        <label for="reference">Vendor Reference</label>
                    </div>
                    <button type="submit" class="btn btn-success" name="approve" value="1">Approve</button>
                    <a class="btn btn-danger" href="{{url('delete_rma') . "/" . $order->id }}">Delete</a>
                </form>
                <script>
                    function submitForm() {
                        var form = $("#approveform");
                        var actionUrl = form.attr('action');

                        $.ajax({
                            type: "POST",
                            url: actionUrl,
                            data: form.serialize(), // serializes the form's elements.
                            success: function(data) {
                                alert("Success: " + data); // show response from the PHP script.
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                alert("Error: " + textStatus + " - " + errorThrown);
                            }
                        });
                    }

                </script>
                @else
                @if ($order->status == 2)
                <form class="form-inline" method="POST" action="{{url('rma/approve').'/'.$order->id}}" id="approveform">
                    @csrf
                    <div class="form-floating">
                        <input type="text" class="form-control" id="tracking_number" name="tracking_number" placeholder="Enter Tracking Number" value="{{$order->tracking_number}}" required>
                        <label for="tracking_number">Tracking Number</label>
                    </div>
                    <button type="submit" class="btn btn-success" name="approve" value="1">Approve</button>
                    <a class="btn btn-danger" href="{{url('delete_rma') . "/" . $order->id }}">Delete</a>
                </form>
                    <br>
                @else
                Tracking Number: <a href="https://www.dhl.com/gb-en/home/tracking/tracking-express.html?submit=1&tracking-id={{$order->tracking_number}}" target="_blank"> {{$order->tracking_number}}</a>
                <br>
                @endif
                Reference: {{ $order->reference }}
                <br>
                @if (session('user')->hasPermission('rma_revert_status'))
                    <a href="{{url('rma/revert_status').'/'.$order->id}}">Revert Back to Pending</a>
                @endif
                @endif

                </div>
                <div class="justify-content-center mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item tx-15"><a href="/">Dashboards</a></li>
                        <li class="breadcrumb-item tx-15"><a href="{{ session('previous')}}">RMA</a></li>
                        <li class="breadcrumb-item active" aria-current="page">RMA Detail</li>
                    </ol>
                </div>
            </div>
        <!-- /breadcrumb -->
        <div class="text-center" style="border-bottom: 1px solid rgb(216, 212, 212);">
                {{-- <center><h4>RMA Order Detail</h4></center> --}}
                <h5>Reference: {{ $order->reference_id }} | Vendor: {{ $order->customer->first_name }} | Total Items: {{ $order->order_items->count() }} @if (session('user')->hasPermission('view_cost')) | Total Price: {{ '€'.amount_formatter($order->order_items->sum('price'),2) }} @endif</h5>

        </div>
        <br>
        @if ($order->status == 1)
            <h4>Add RMA Item</h4>
        @elseif ($order->status == 2)
            <h4>Remove RMA Item</h4>
        @endif
        <div class="d-flex justify-content-between" style="border-bottom: 1px solid rgb(216, 212, 212);">


                @if ($order->status == 1)
            <div class="p-2">
                <span class="form-check form-switch ms-4" title="Bypass Wholesale check" onclick="$('#bypass_check').check()">
                    <input type="checkbox" value="1" id="bypass_check" name="bypass_check" class="form-check-input" form="rma_item" @if (session('bypass_check') == 1) checked @endif>
                    <label class="form-check-label" for="bypass_check">Bypass check</label>
                </span>
            </div>

                @endif
            <div class="p-1">
                @if ($order->status == 1)
                    <form class="form-inline" action="{{ url('check_rma_item').'/'.$order_id }}" method="POST" id="rma_item">
                        @csrf


                        <div class="form-floating">
                            <input type="text" class="form-control" id="rma_reason" name="rma_reason" placeholder="Enter RMA reason" value="{{session('rma_reason') ?? null}}" >
                            <label for="rma_reason">If Move than Reason</label>
                        </div>

                        <div class="form-floating">
                            <input type="text" class="form-control" id="imei" name="imei" placeholder="Enter IMEI | Serial Number" onloadeddata="$(this).focus()" autofocus required>
                            <label for="imei">IMEI | Serial Number</label>
                        </div>

                        {{-- <label for="imei" class="">IMEI | Serial Number: &nbsp;</label>
                        <input type="text" class="form-control form-control-sm" name="imei" id="imei" placeholder="Enter IMEI" onloadeddata="$(this).focus()" autofocus required> --}}
                        <button class="btn btn-primary pd-x-20" type="submit">Insert</button>

                    </form>
                    <script>
                        window.onload = function() {
                            document.getElementById('imei').focus();
                            document.getElementById('imei').click();
                            setTimeout(function(){ document.getElementById('imei').focus();$('#imei').focus(); }, 500);
                        };
                        document.addEventListener('DOMContentLoaded', function() {
                            var input = document.getElementById('imei');
                            input.focus();
                            input.select();
                            document.getElementById('imei').click();
                            setTimeout(function(){ document.getElementById('imei').focus();$('#imei').focus(); }, 500);
                        });
                    </script>
                @elseif ($order->status == 2)
                    <form class="form-inline" action="{{ url('return_rma_item').'/'.$order_id }}" method="POST" id="rma_item">
                        @csrf

                        <div class="form-floating">
                            <input type="text" class="form-control" id="imeiInput" name="imei" placeholder="Enter IMEI" value="@isset($_GET['imei']){{$_GET['imei']}}@endisset" onloadeddata="$(this).focus()" autofocus required>
                            <label for="">IMEI | Serial Number:</label>
                        </div>
                        {{-- <label for="imei" class="">IMEI | Serial Number: &nbsp;</label>
                        <input type="text" class="form-control form-control-sm" name="imei" id="imei" placeholder="Enter IMEI"> --}}
                        <select name="grade" class="form-control form-select" required>
                            <option value="">Move to</option>
                            @foreach ($grades as $id => $name)
                                @if($id > 5)
                                <option value="{{ $id }}" @if(session('grade') && $id == session('grade')) {{'selected'}}@endif @if(request('grade') && $id == request('grade')) {{'selected'}}@endif>{{ $name }}</option>
                                @endif
                            @endforeach
                        </select>

                        <div class="form-floating">
                            <input type="text" class="form-control pd-x-20" name="description" placeholder="Reason" style="width: 270px;" value="{{session('description')}}">
                            {{-- <input type="text" class="form-control" name="wholesale_return[imei]" placeholder="Enter IMEI" value="@isset($_GET['imei']){{$_GET['imei']}}@endisset"> --}}
                            <label for="">Reason</label>
                        </div>
                        <div class="form-floating">
                            <input type="text" class="form-control pd-x-20" name="check_testing_days" placeholder="Reason" style="width: 270px;" value="{{session('check_testing_days')}}">
                            {{-- <input type="text" class="form-control" name="wholesale_return[imei]" placeholder="Enter IMEI" value="@isset($_GET['imei']){{$_GET['imei']}}@endisset"> --}}
                            <label for="">Tested __ Days Ago</label>
                        </div>
                        {{-- <label for="imei" class="">IMEI | Serial Number: &nbsp;</label>
                        <input type="text" class="form-control form-control-sm" name="imei" id="imei" placeholder="Enter IMEI" onloadeddata="$(this).focus()" autofocus required> --}}
                        <button class="btn btn-secondary pd-x-20" type="submit">Remove</button>

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
                @endif
            </div>
            <div class="p-2">
                @if ($order->customer->email == null)
                    Customer Email Not Added
                @else
                <a href="{{url('rma_email')}}/{{ $order->id }}" target="_blank"><button class="btn-sm btn-secondary">Send Email</button></a>
                @endif
                <a href="{{url('export_rma_invoice')}}/{{ $order->id }}" target="_blank"><button class="btn-sm btn-secondary">Invoice</button></a>

                @if ($order->exchange_rate != null)

                <div class="btn-group" role="group">
                    <button type="button" class="btn-sm btn-secondary dropdown-toggle" id="pack_sheet" data-bs-toggle="dropdown" aria-expanded="false">
                    Exchanged {{$order->currency_id->sign}}
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="pack_sheet">
                        <li><a class="dropdown-item" href="{{url('export_rma_invoice')}}/{{ $order->id }}/1?packlist=2&id={{ $order->id }}">.xlsx</a></li>
                        {{-- <a href="{{url('export_rma_invoice')}}/{{ $order->id }}/1" target="_blank"><button class="btn-sm btn-secondary">{{$order->currency_id->sign}} Invoice</button></a> --}}
                        <li><a class="dropdown-item" href="{{url('export_rma_invoice')}}/{{ $order->id }}/1" target="_blank">Invoice</a></li>
                    </ul>
                </div>
                @endif
                <div class="btn-group" role="group">
                    <button type="button" class="btn-sm btn-secondary dropdown-toggle" id="pack_sheet" data-bs-toggle="dropdown" aria-expanded="false">
                    Pack Sheet
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="pack_sheet">
                        <li><a class="dropdown-item" href="{{url('export_rma_invoice')}}/{{ $order->id }}?packlist=2&id={{ $order->id }}">.xlsx</a></li>
                        <li><a class="dropdown-item" href="{{url('export_rma_invoice')}}/{{ $order->id }}?packlist=1" target="_blank">.pdf</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <br>

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
                            <h4 class="card-title mg-b-0">Latest Added Items</h4>
                            <div class=" mg-b-0">
                                @if (request('hide') == 'all')
                                    <a href="{{ url('rma/detail').'/'.$order_id }}" class="btn btn-sm btn-link">Show All</a>
                                @else
                                    <a href="{{ url('rma/detail').'/'.$order_id.'?hide=all' }}" class="btn btn-sm btn-link">Hide All</a>
                                @endif
                                <form method="get" action="" class="row form-inline">
                                    <label for="perPage" class="card-title inline">per page:</label>
                                    <select name="per_page" class="form-select form-select-sm" id="perPage" onchange="this.form.submit()">
                                        <option value="10" {{ Request::get('per_page') == 10 ? 'selected' : '' }}>10</option>
                                        <option value="20" {{ Request::get('per_page') == 20 ? 'selected' : '' }}>20</option>
                                        <option value="50" {{ Request::get('per_page') == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ Request::get('per_page') == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                    {{-- <button type="submit">Apply</button> --}}
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="card-body"><div class="table-responsive" style="max-height: 250px">
                            <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                                <thead>
                                    <tr>
                                        <th><small><b>No</b></small></th>
                                        <th><small><b>Variation</b></small></th>
                                        <th><small><b>IMEI | Serial Number</b></small></th>
                                        <th><small><b>Vendor</b></small></th>
                                        <th><small><b>PO</b></small></th>
                                        <th><small><b>Comment</b></small></th>
                                        @if (session('user')->hasPermission('view_cost'))
                                        <th><small><b>Cost</b></small></th>
                                        @endif
                                        <th><small><b>Creation Date</b></small></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 0;
                                    @endphp
                                    @foreach ($last_ten as $item)
                                        @php
                                            $i ++;
                                            $variation = $item->variation;
                                            $stock = $item->stock;
                                            $po = $stock->order;
                                            $customer = $po->customer;

                                        @endphp
                                        <tr>
                                            <td>{{ $i }}</td>
                                            <td>{{ $products[$variation->product_id]}} {{$storages[$variation->storage] ?? null}} {{$colors[$variation->color] ?? null}} {{$grades[$variation->grade] ?? "Grade not added" }}</td>
                                            <td>{{ $stock->imei.$stock->serial_number }}</td>
                                            <td>{{ $customer->last_name }}
                                                @if ($stock->latest_repair != null)
                                                    <a href="{{url('repair/detail/'.$stock->latest_repair->proces_id)}}">{{ $stock->latest_repair->process->reference_id }}</a>
                                                @endif
                                            </td>
                                            <td title="{{ $po->created_at }}">{{ $po->reference_id }}</td>
                                            <td>{{ $stock->latest_operation->description ?? null }}</td>
                                            @if (session('user')->hasPermission('view_cost'))
                                            <td>€{{ amount_formatter($item->price,2) }}</td>
                                            @endif
                                            <td style="width:220px">{{ $item->created_at }}</td>
                                            @if (session('user')->hasPermission('delete_rma_item') && $order->status == 1)
                                            <td><a href="{{ url('delete_rma_item').'/'.$item->id }}"><i class="fa fa-trash"></i></a></td>

                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        <br>
                    </div>

                    </div>
                </div>
            </div>
        </div>
        <br>

        @if (request('hide') != 'all')
        <div class="row">

            {{-- @foreach ($variations as $variation) --}}
            @foreach ($variations as $key=>$vars)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header pb-0">
                        @php
                            $varss = $vars->toArray();
                        @endphp
                        {{ $products[$key]." ".$storages[array_key_first($varss)] }}
            {{-- <div class="col-md-4">
                <div class="card">
                    <div class="card-header pb-0">
                        @php
                            isset($variation->color_id)?$color = $variation->color_id->name:$color = null;
                            isset($variation->storage)?$storage = $storages[$variation->storage]:$storage = null;
                        @endphp
                        {{ $variation->product->model." ".$storage." ".$color." ".$variation->grade_id->name }} --}}
                    </div>
                            {{-- {{ $variation }} --}}
                    <div class="card-body"><div class="table-responsive" style="max-height: 400px">

                            <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                                <thead>
                                    <tr>
                                        <th><small><b>#</b></small></th>
                                        <th><small><b>Color - Grade</b></small></th>
                                        <th><small><b>IMEI/Serial</b></small></th>
                                        {{-- @if (session('user')->hasPermission('view_cost')) --}}
                                        <th><small><b>Vendor Price</b></small></th>
                                        {{-- @endif --}}
                                        @if (session('user')->hasPermission('delete_rma_item') && $order->status == 1)
                                        <th></th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 0;
                                    @endphp
                                    <form method="POST" action="{{url('rma')}}/update_prices" id="update_prices_{{ $key."_".array_key_first($varss) }}">
                                        @csrf
                                    @foreach ($vars as $var)
                                    @foreach ($var as $variation)
                                    @php
                                        $stocks = $variation->stocks;
                                        // $items = $stocks->order_item;
                                        // print_r($variation);
                                    @endphp

                                    @foreach ($stocks as $item)
                                        {{-- @dd($item->sale_item) --}}
                                        {{-- @if($item->sale_item($order_id)->order_id == $order_id) --}}
                                        @php
                                            $i ++;
                                            $sale_order = $item->sale_item($order_id);
                                        @endphp
                                        <tr>
                                            <td>{{ $i }}</td>
                                            <td>{{ $colors[$variation->color] ?? null }} - {{ $grades[$variation->grade] ?? null }}</td>
                                            <td><a title="{{$item->id}} | Search Serial" href="{{url('imei')."?imei=".$item->imei.$item->serial_number}}" target="_blank"> {{$item->imei.$item->serial_number }} </a></td>
                                            <td @if (session('user')->hasPermission('view_cost') && $item->purchase_item != null) title="Cost Price: €{{ amount_formatter($item->purchase_item->price,2) }}" @endif>
                                                {{ $item->order->customer->first_name }}
                                                @if (session('user')->hasPermission('view_cost'))
                                                €{{ amount_formatter($sale_order->price,2) }}
                                                @endif
                                                @if ($item->purchase_item == null)
                                                    Missing Purchase Entry
                                                @endif
                                            </td>

                                            @if (session('user')->hasPermission('delete_rma_item') && $order->status == 1)
                                            <td><a href="{{ url('delete_rma_item').'/'.$sale_order->id }}"><i class="fa fa-trash"></i></a></td>
                                            @endif
                                            <input type="hidden" name="item_ids[]" value="{{ $sale_order->id }}">
                                        </tr>
                                        {{-- @endif --}}
                                    @endforeach
                                    @endforeach
                                    @endforeach
                                    </form>
                                </tbody>
                            </table>
                        <br>
                    </div>
                    <div class="d-flex justify-content-between">
                        @if (session('user')->hasPermission('view_cost'))
                        <div>
                            <label for="unit-price" class="">Change Unit Price: </label>
                            <input type="number" name="unit_price" id="unit_price" class="w-50 border-0" placeholder="Input Unit price" form="update_prices_{{ $key."_".array_key_first($varss) }}">
                        </div>
                        @endif
                        <div>Total: {{$i }}</div>
                    </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

    @endsection

    @section('scripts')
        <script>
            $(document).ready(function() {
                $('#currency').on('input', function() {
                    var selectedCurrency = $(this).val();
                    var rate = $('#currencies').find('option[value="' + selectedCurrency + '"]').data('rate');
                    if (rate !== undefined) {
                        $('#rate').val(rate);
                    } else {
                        $('#rate').val(''); // Clear the rate field if the currency is not in the list
                    }
                });
            });
        </script>
		<!--Internal Sparkline js -->
		<script src="{{asset('assets/plugins/jquery-sparkline/jquery.sparkline.min.js')}}"></script>

		<!-- Internal Piety js -->
		<script src="{{asset('assets/plugins/peity/jquery.peity.min.js')}}"></script>

		<!-- Internal Chart js -->
		<script src="{{asset('assets/plugins/chartjs/Chart.bundle.min.js')}}"></script>

		<!-- INTERNAL Select2 js -->
		<script src="{{asset('assets/plugins/select2/js/select2.full.min.js')}}"></script>
		<script src="{{asset('assets/js/select2.js')}}"></script>
    @endsection
