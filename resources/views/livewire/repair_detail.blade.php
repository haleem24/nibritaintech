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
                        <input type="checkbox" value="1" name="bypass_check" class="form-check-input" form="repair_item" @if (session('bypass_check') == 1) checked @endif>
                        <label class="form-check-label" for="bypass_check">Bypass Repair check</label>
                    </span> --}}
                <span class="main-content-title mg-b-0 mg-b-lg-1">External Repair Order Detail</span>
                @if ($process->status == 1)
                <form class="form-inline" id="approveform" method="POST" action="{{url('repair/ship').'/'.$process->id}}">
                    @csrf
                    <div class="">
                        <select name="customer_id" class="form-select">
                            <option value="" disabled selected>Select Repairer</option>
                            @foreach ($repairers as $id=>$vendor)
                                <option value="{{ $id }}" {{ $process->customer_id == $id ? 'selected' : '' }}>{{ $vendor }}</option>

                            @endforeach
                        </select>
                    </div>
                    <div class="form-floating">
                        <input type="text" list="currencies" id="currency" name="currency" class="form-control" value="{{$process->currency_id->code}}">
                        <datalist id="currencies">
                            @foreach ($exchange_rates as $target_currency => $rate)
                                <option value="{{$target_currency}}" data-rate="{{$rate}}"></option>
                            @endforeach
                        </datalist>
                        <label for="currency">Currency</label>
                    </div>
                    <div class="form-floating">
                        <input type="text" class="form-control" id="rate" name="rate" placeholder="Enter Exchange Rate" value="{{$process->exchange_rate}}" >
                        <label for="rate">Exchange Rate</label>
                    </div>
                    <div class="form-floating">
                        <input type="text" class="form-control" id="tracking_number" name="tracking_number" placeholder="Enter Tracking Number" value="{{$process->tracking_number}}" onchange="submitForm()" required>
                        <label for="tracking_number">Tracking Number</label>
                    </div>
                    <div class="form-floating">
                        <input type="text" class="form-control" id="description" name="description" placeholder="Enter Description" value="{{$process->description}}" required>
                        <label for="description">Description</label>
                    </div>
                    <button type="submit" class="btn btn-success" name="approve" value="1">Ship</button>
                    <a class="btn btn-danger" href="{{url('delete_repair') . "/" . $process->id }}">Delete</a>
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
                <br>
                Tracking Number: <a href="https://www.dhl.com/gb-en/home/tracking/tracking-express.html?submit=1&tracking-id={{$process->tracking_number}}" target="_blank"> {{$process->tracking_number}}</a>
                <br>
                {{ $process->description }}



                @if (session('user')->hasPermission('repair_revert_status'))
                    <br>
                    <a href="{{url('repair/revert_status').'/'.$process->id}}">Revert Back to Pending</a>
                @endif

                @endif
                    @if ($process->status == 2 && $variations->count() == 0)
                    <form class="form-inline" method="POST" action="{{url('repair/approve').'/'.$process->id}}">
                        @csrf
                        <div class="form-floating">
                            <input type="text" class="form-control" id="cost" name="cost" value="{{$process->process_stocks->sum('price')}}" placeholder="Enter Total Cost" required>
                            <label for="cost">Total Cost</label>
                        </div>
                        <button type="submit" class="btn btn-success">Close</button>
                    </form>

                    @endif
                </div>
                <div class="justify-content-center mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item tx-15"><a href="/">Dashboards</a></li>
                        <li class="breadcrumb-item tx-15"><a href="{{ session('previous')}}">External Repair</a></li>
                        <li class="breadcrumb-item active" aria-current="page">External Repair Detail</li>
                    </ol>
                </div>
            </div>
        <!-- /breadcrumb -->
        <div class="d-flex justify-content-between" style="border-bottom: 1px solid rgb(216, 212, 212);">
                {{-- <center><h4>External Repair Order Detail</h4></center> --}}
            <h5>Reference: {{ $process->reference_id }} | Repairer: {{ $process->customer->first_name }} | Total Items: {{ $process->process_stocks->count() }} | Total Price: {{ $currency.amount_formatter($process->process_stocks->sum('price'),2) }}</h5>
            @if ($process->status == 1)
            <div class="p-1">
                <form class="form-inline" action="{{ url('delete_repair_item') }}" method="POST" id="repair_item">
                    @csrf
                    <label for="imei" class="">IMEI | Serial Number: &nbsp;</label>
                    <input type="text" class="form-control form-control-sm" name="imei" @if (request('remove') == 1) id="imei" @endif placeholder="Enter IMEI" onloadeddata="$(this).focus()" autofocus required>
                    <input type="hidden" name="process_id" value="{{$process->id}}">
                    <input type="hidden" name="remove" value="1">
                    <button class="btn-sm btn-secondary pd-x-20" type="submit">Remove</button>

                </form>
            </div>
            @endif
        </div>

        <br>

        <div class="d-flex justify-content-between" style="border-bottom: 1px solid rgb(216, 212, 212);">

            @if ($process->status == 1)
            <div class="p-2">
                <h4>Add External Repair Item
                    {{-- Option to show advance options --}}
                    <a href="{{ url('repair').'/'.$process->id.'?show_advance=1' }}" class="btn btn-sm btn-link" data-bs-toggle="collapse" data-bs-target="#advance_options" aria-expanded="false" aria-controls="advance_options" id="advance_options_button" >Show Advance Options</a>
                </h4>

                <div class="collapse" id="advance_options">
                    <form method="GET" class="row">
                        <div class="input-group col-md-6">
                            <label for="exclude_vendor" class="form-label">Exclude Vendor</label>
                            <select name="exclude_vendor[]" id="exclude_vendor" class="select2 form-control" multiple>
                                @foreach ($vendors as $vendor)
                                    <option value="{{$vendor->id}}"
                                        @if (request('exclude_vendor') != null)
                                            @if (in_array($vendor->id,request('exclude_vendor')))
                                                selected
                                            @endif

                                        @endif
                                        >{{$vendor->first_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group col-md-6">
                            <label for="include_vendor" class="form-label">Include Vendor</label>
                            <select name="include_vendor[]" id="include_vendor" class="select2 form-control" multiple>
                                @foreach ($vendors as $vendor)
                                    <option value="{{$vendor->id}}"
                                        @if (request('include_vendor') != null)
                                            @if (in_array($vendor->id,request('include_vendor')))
                                                selected
                                            @endif

                                        @endif
                                        >{{$vendor->first_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group col-md-6">
                            <label for="exclude_product" class="form-label">Exclude Product</label>
                            <select name="exclude_product[]" id="exclude_product" class="select2 form-control" multiple>
                                @foreach ($products as $id => $product)
                                    <option value="{{$id}}" @if (request('exclude_product') != null) @if (in_array($id,request('exclude_product'))) selected @endif @endif>{{$product}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group col-md-6">
                            <label for="include_product" class="form-label">Include Product</label>
                            <select name="include_product[]" id="include_product" class="select2 form-control" multiple>
                                @foreach ($products as $id => $product)
                                    <option value="{{$id}}" @if (request('include_product') != null) @if (in_array($id,request('include_product'))) selected @endif @endif>{{$product}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group col-md-6">
                            <label for="exclude_grade" class="form-label">Exclude Grade</label>
                            <select name="exclude_grade[]" id="exclude_grade" class="select2 form-control" multiple>
                                @foreach ($grades as $id => $grade)
                                    <option value="{{$id}}" @if (request('exclude_grade') != null) @if (in_array($id,request('exclude_grade'))) selected @endif @endif>{{$grade}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group col-md-6">
                            <label for="include_grade" class="form-label">Include Grade</label>
                            <select name="include_grade[]" id="include_grade" class="select2 form-control" multiple>
                                @foreach ($grades as $id => $grade)
                                    <option value="{{$id}}" @if (request('include_grade') != null) @if (in_array($id,request('include_grade'))) selected @endif @endif>{{$grade}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group col-md-6">
                            <label for="apply_filter" class="form-label">Apply Filter</label>
                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                        </div>
                        <input type="hidden" name="show_advance" value="1">
                        @if (request('hide') == 'all')
                        <input type="hidden" name="hide" value="all">
                        @endif
                    </form>
                </div>

            </div>
            <div class="p-1">
                <form class="form-inline" action="{{ url('check_repair_item').'/'.$process_id }}" method="POST" id="repair_item">
                    @csrf
                    <label for="imei" class="">IMEI | Serial Number: &nbsp;</label>
                    <input type="text" class="form-control form-control-sm" name="imei"  @if (request('remove') != 1) id="imei" @endif id="imei" placeholder="Enter IMEI" onloadeddata="$(this).focus()" autofocus required>
                    <button class="btn-sm btn-primary pd-x-20" type="submit">Insert</button>
                    @if (request('exclude_vendor'))
                        @foreach (request('exclude_vendor') as $vendor)
                            <input type="hidden" name="exclude_vendor[]" value="{{$vendor}}">
                        @endforeach
                        <input type="hidden" name="apply_filter" value="1">
                    @endif
                    @if (request('include_vendor'))
                        @foreach (request('include_vendor') as $vendor)
                            <input type="hidden" name="include_vendor[]" value="{{$vendor}}">
                        @endforeach
                        <input type="hidden" name="apply_filter" value="1">
                    @endif
                    @if (request('exclude_product'))
                        @foreach (request('exclude_product') as $product)
                            <input type="hidden" name="exclude_product[]" value="{{$product}}">
                        @endforeach
                        <input type="hidden" name="apply_filter" value="1">
                    @endif
                    @if (request('include_product'))
                        @foreach (request('include_product') as $product)
                            <input type="hidden" name="include_product[]" value="{{$product}}">
                        @endforeach
                        <input type="hidden" name="apply_filter" value="1">
                    @endif
                    @if (request('exclude_grade'))
                        @foreach (request('exclude_grade') as $grade)
                            <input type="hidden" name="exclude_grade[]" value="{{$grade}}">
                        @endforeach
                        <input type="hidden" name="apply_filter" value="1">
                    @endif
                    @if (request('include_grade'))
                        @foreach (request('include_grade') as $grade)
                            <input type="hidden" name="include_grade[]" value="{{$grade}}">
                        @endforeach
                        <input type="hidden" name="apply_filter" value="1">
                    @endif
                </form>
            </div>
            <div class="p-2 tx-right">
                <form method="POST" enctype="multipart/form-data" action="{{ url('repair/add_repair_sheet').'/'.$process_id}}" class="form-inline p-1">
                    @csrf
                    <input type="file" class="form-control form-control-sm" name="sheet">
                    <button type="submit" class="btn btn-sm btn-primary">Upload Sheet</button>
                </form>

                <a href="{{url('repair_email')}}/{{ $process->id }}" target="_blank"><button class="btn-sm btn-secondary">Send Email</button></a>
                <a href="{{url('export_repair_invoice')}}/{{ $process->id }}" target="_blank"><button class="btn-sm btn-secondary">Invoice</button></a>
                @if ($process->exchange_rate != null)
                <a href="{{url('export_repair_invoice')}}/{{ $process->id }}/1" target="_blank"><button class="btn-sm btn-secondary">{{$process->currency_id->sign}} Invoice</button></a>

                @endif
                <div class="btn-group p-1" role="group">
                    <button type="button" class="btn-sm btn-secondary dropdown-toggle" id="pack_sheet" data-bs-toggle="dropdown" aria-expanded="false">
                    Pack Sheet
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="pack_sheet">
                        <li><a class="dropdown-item" href="{{url('export_repair_invoice')}}/{{ $process->id }}?packlist=2&id={{ $process->id }}">.xlsx</a></li>
                        <li><a class="dropdown-item" href="{{url('export_repair_invoice')}}/{{ $process->id }}?packlist=1" target="_blank">.pdf</a></li>
                    </ul>
                </div>
            </div>

            @elseif ($process->status == 2)

            <div class="p-2">
                <h4>Receive External Repair Item</h4>

            </div>
            <div class="p-1">
                <form class="form-inline" action="{{ url('receive_repair_item').'/'.$process_id }}" method="POST" id="repair_item">
                    @csrf
                    <label for="imei" class="">IMEI | Serial Number: &nbsp;</label>
                    <input type="text" class="form-control form-control-sm" name="imei" id="imei" placeholder="Enter IMEI" onloadeddata="$(this).focus()" autofocus required>
                    <label for="">Tested __ Days Ago</label>
                    <input type="number" class="form-control form-control-sm" name="check_testing_days" placeholder="Days" value="{{session('check_testing_days')}}">
                    <button class="btn-sm btn-primary pd-x-20" type="submit">Insert</button>

                </form>
            </div>

            <div class="btn-group p-1" role="group">
                <a href="{{url('repair_email')}}/{{ $process->id }}" target="_blank"><button class="btn btn-secondary">Send Email</button></a>
                <a href="{{url('export_repair_invoice')}}/{{ $process->id }}" target="_blank"><button class="btn btn-secondary">Invoice</button></a>
                @if ($process->exchange_rate != null)
                <a href="{{url('export_repair_invoice')}}/{{ $process->id }}/1" target="_blank"><button class="btn btn-secondary">{{$process->currency_id->sign}} Invoice</button></a>

                @endif
                <button type="button" class="btn btn-secondary dropdown-toggle" id="pack_sheet" data-bs-toggle="dropdown" aria-expanded="false">
                Pack Sheet
                </button>
                <ul class="dropdown-menu" aria-labelledby="pack_sheet">
                    <li><a class="dropdown-item" href="{{url('export_repair_invoice')}}/{{ $process->id }}?packlist=2&id={{ $process->id }}">.xlsx</a></li>
                    <li><a class="dropdown-item" href="{{url('export_repair_invoice')}}/{{ $process->id }}?packlist=1" target="_blank">.pdf</a></li>
                </ul>
            </div>

            @endif

        </div>
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
        @php
            $imei_list = [];
        @endphp

        @if ($process->status == 1)

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between">
                            <h4 class="card-title mg-b-0">Latest Added Items</h4>
                            @if (request('hide') == 'all')
                                <a href="{{ url('repair/detail').'/'.$process_id }}" class="btn btn-sm btn-link">Show All</a>
                            @else
                                <a href="{{ url('repair/detail').'/'.$process_id.'?hide=all' }}" class="btn btn-sm btn-link">Hide All</a>
                            @endif
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
                                        <th><small><b>Reason</b></small></th>
                                        @if (session('user')->hasPermission('view_cost'))
                                        <th><small><b>Cost</b></small></th>
                                        @endif
                                        <th><small><b>Creation Date</b></small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 0;
                                    @endphp
                                    @foreach ($last_ten as $p_stock)
                                        @php
                                            $item = $p_stock->stock;
                                        @endphp

                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $products[$item->variation->product_id]}} {{$storages[$item->variation->storage] ?? null}} {{$colors[$item->variation->color] ?? null}} {{$grades[$item->variation->grade] ?? "Grade not added" }} {{$grades[$item->variation->sub_grade] ?? '' }}</td>
                                            <td>{{ $item->imei.$item->serial_number }}</td>
                                            <td>{{ $item->order->customer->first_name }}
                                                @if ($item->previous_repair != null)
                                                    <a href="{{url('repair/detail/'.$item->previous_repair->proces_id)}}">{{ $item->previous_repair->process->reference_id }}</a>
                                                @endif
                                            </td>
                                            <td>{{ $item->latest_operation->description ?? null }}</td>
                                            @if (session('user')->hasPermission('view_cost'))
                                            <td>{{ $currency.(amount_formatter($item->purchase_item->price ?? "Cost not found",2)) }}</td>
                                            @endif
                                            <td style="width:220px">{{ $item->created_at }}</td>
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
        <br>
        @endif

        @if (request('hide') != 'all')
        <div @if ($process->status != 1)  class="row" @endif>
            <div @if ($process->status != 1) class="col-md-7 row" @else class="row" @endif>

            @foreach ($variations as $variation)
            <div @if ($process->status == 1) class="col-md-4" @else class="col-md-6" @endif>
                <div class="card">
                    <div class="card-header pb-0">
                        @php
                            isset($variation->color_id)?$color = $variation->color_id->name:$color = null;
                            isset($variation->storage)?$storage = $storages[$variation->storage]:$storage = null;
                        @endphp
                        {{ $variation->product->model." ".$storage." ".$color }} {{ $variation->grade_id->name ?? "Grade not added" }} {{ $variation->sub_grade_id->name ?? '' }}
                    </div>
                            {{-- {{ $variation }} --}}
                    <div class="card-body"><div class="table-responsive" style="max-height: 400px">

                            <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                                <thead>
                                    <tr>
                                        <th><small><b>#</b></small></th>
                                        {{-- <th><small><b>Vendor</b></small></th> --}}
                                        <th><small><b>IMEI/Serial</b></small></th>
                                        {{-- @if (session('user')->hasPermission('view_cost')) --}}
                                        <th><small><b>Vendor Price</b></small></th>
                                        {{-- @endif --}}
                                        @if (session('user')->hasPermission('delete_repair_item'))
                                        <th></th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    <form method="POST" action="{{url('repair')}}/update_prices" id="update_prices_{{ $variation->id }}">
                                        @csrf
                                    @php
                                        $i = 0;
                                        $id = [];
                                    @endphp
                                    @php
                                        $stocks = $variation->stocks;
                                        // $items = $stocks->order_item;
                                        $j = 0;
                                        $total = 0;
                                        // print_r($variation);
                                    @endphp

                                    @foreach ($stocks as $item)
                                        {{-- @dd($item->sale_item) --}}
                                        @if($item->process_stock($process_id)->process_id == $process_id)
                                        @php
                                            $i ++;
                                            $total += $item->purchase_item->price ?? 0;

                                            if(!in_array($item->imei.$item->serial_number,$imei_list)){
                                                array_push($imei_list,$item->imei.$item->serial_number);
                                            }
                                        @endphp
                                        @if ($process->tracking_number != null)
                                            @if ($item->multi_process_stocks($previous_repairs)->count() > 0 || $item->order->customer_id != 7110)
                                                @php
                                                    $danger = "bg-danger";
                                                @endphp
                                            @else
                                                @php
                                                    $danger = "";
                                                @endphp
                                            @endif
                                        @else
                                            @php
                                                $danger = "";
                                            @endphp
                                        @endif
                                        <tr class="{{ $danger }}">
                                            <td>{{ $i }}</td>
                                            {{-- <td>{{ $item->order->customer->first_name }}</td> --}}
                                            <td><a title="Search Serial" href="{{url('imei')."?imei=".$item->imei.$item->serial_number}}" target="_blank"> {{ $item->imei.$item->serial_number }} </a></td>
                                            <td @if (session('user')->hasPermission('view_cost')) title="Cost Price: {{ $currency.amount_formatter($item->purchase_item->price ?? "0") }}" @endif>
                                                {{ $item->order->customer->first_name }} {{ $currency.amount_formatter($item->purchase_item->price ?? "0") }}
                                            </td>

                                            @if (session('user')->hasPermission('delete_repair_item'))
                                            <td><a href="{{ url('delete_repair_item').'/'.$item->process_stock($process_id)->id }}"><i class="fa fa-trash"></i></a></td>
                                            @endif
                                            <input type="hidden" name="item_ids[]" value="{{ $item->process_stock($process_id)->id }}">
                                        </tr>
                                        @endif
                                    @endforeach
                                    </form>
                                </tbody>
                            </table>
                        <br>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Total: {{$i }}</div>
                    </div>
                </div>
            </div>
            </div>
            @endforeach

            </div>
            @if ($process->status != 1)

            <div class="col-md-5">
                <div class="card">
                    <div class="card-header pb-0">
                        Received Items
                    </div>
                            {{-- {{ $variation }} --}}
                    <div class="card-body"><div class="table-responsive" style="max-height: 400px">

                            <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                                <thead>
                                    <tr>
                                        <th><small><b>#</b></small></th>
                                        {{-- <th><small><b>Vendor</b></small></th> --}}
                                        <th><small><b>IMEI/Serial</b></small></th>
                                        {{-- @if (session('user')->hasPermission('view_cost')) --}}
                                        <th><small><b>Name</b></small></th>
                                        {{-- @endif --}}
                                        @if ($process->status == 3 && session('user')->hasPermission('view_cost'))
                                        <th><small><b>Cost</b></small></th>
                                        @endif
                                        <th><small><b>Last Updated</b></small></th>

                                        @if (session('user')->hasPermission('delete_repair_item'))
                                        {{-- <th></th> --}}
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- <form method="POST" action="{{url('repair')}}/update_prices" id="update_prices_{{ $variation->id }}"> --}}
                                        @csrf
                                    @php
                                        $i = 0;
                                        $id = [];
                                    @endphp
                                    @php
                                        // $items = $stocks->order_item;
                                        $j = 0;
                                        $total = 0;
                                        // print_r($variation);
                                    @endphp

                                    @foreach ($processed_stocks as $processed_stock)
                                        {{-- @dd($item->sale_item) --}}
                                        @php
                                            $item = $processed_stock->stock;
                                            $variation = $item->variation;
                                            $i ++;

                                            isset($variation->product)?$product = $products[$variation->product_id]:$product = null;
                                            isset($variation->storage)?$storage = $storages[$variation->storage]:$storage = null;
                                            isset($variation->color)?$color = $colors[$variation->color]:$color = null;
                                            isset($variation->grade)?$grade = $grades[$variation->grade]:$grade = null;
                                            isset($variation->sub_grade)?$sub_grade = $grades[$variation->sub_grade]:$sub_grade = null;

                                        @endphp
                                        <tr>
                                            <td>{{ $i }}</td>
                                            {{-- <td>{{ $item->order->customer->first_name }}</td> --}}
                                            <td>{{ $item->imei.$item->serial_number }}</td>
                                            <td>
                                                {{ $product." ".$storage." ".$color." ".$grade." ".$sub_grade }}
                                            </td>

                                            @if ($process->status == 3 && session('user')->hasPermission('view_cost'))
                                            <td>{{ amount_formatter($processed_stock->price,2) }}</td>
                                            @endif
                                            <td>{{$processed_stock->updated_at}}</td>
                                            @if (session('user')->hasPermission('delete_repair_item'))
                                            {{-- <td><a href="{{ url('delete_repair_item').'/'.$item->process_stock($process_id)->id }}"><i class="fa fa-trash"></i></a></td> --}}
                                            @endif
                                            <input type="hidden" name="item_ids[]" value="{{ $item->process_stock($process_id)->id }}">
                                        </tr>
                                    @endforeach
                                    </form>
                                </tbody>
                            </table>
                        <br>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Total: {{$i }}</div>
                    </div>
                </div>
            </div>

            @endif


            <button class="btn btn-link" id="open_all_imei">Open All IMEIs</button>
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

            $('.select2').select2({
                placeholder: 'Select an option',
                allowClear: true
            });

            $('#advance_options').collapse("{{ request('show_advance') == 1 ? 'show' : 'hide' }}");
        });


        document.getElementById("open_all_imei").onclick = function(){
            @php
                foreach ($imei_list as $imei) {
                    echo "window.open('".url("imei")."?imei=".$imei."','_blank');";
                }

            @endphp
        }
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
