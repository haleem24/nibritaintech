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

<div class="toast-container position-fixed top-0 end-0 p-5" style="z-index: 1000;">
    {{-- @if (session('error'))
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header text-danger">
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn" data-bs-dismiss="toast" aria-label="Close">x</button>
                </div>
                <div class="toast-body">{{ session('error') }}</div>
            </div>
        @php
        session()->forget('error');
        @endphp
    @endif

    @if (session('success'))
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header text-success bg-light">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn" data-bs-dismiss="toast" aria-label="Close">x</button>
                </div>
                <div class="toast-body">{{ session('success') }}</div>
            </div>
        @php
        session()->forget('success');
        @endphp
    @endif --}}

</div>


        <!-- breadcrumb -->
            <div class="breadcrumb-header justify-content-between">
                <div class="left-content">
                {{-- <span class="main-content-title mg-b-0 mg-b-lg-1">Move Inventory</span> --}}
                </div>
                <div class="justify-content-center mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item tx-15"><a href="/">Dashboards</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Move Inventory</li>
                    </ol>
                </div>
            </div>
        <!-- /breadcrumb -->
        <div class="row">
            <div class="col-md-12" style="border-bottom: 1px solid rgb(216, 212, 212);">
                <center><h4>Move Inventory To</h4></center>
            </div>
        </div>
        <br>

        <div class="" style="border-bottom: 1px solid rgb(216, 212, 212);">

            <div class="p-2">
                <form action="{{ url('move_inventory/change_grade') }}" method="POST" class="">
                    @csrf

                    @if (session('user')->hasPermission('advanced_move_inventory'))
                        <div class="d-flex justify-content-between">
                        <div class="col-md col-sm-3">
                            <div class="form-floating">
                                <input type="text" name="product" class="form-control" data-bs-placeholder="Select Model" list="product-menu">
                                <label for="product">Product</label>
                            </div>
                            <datalist id="product-menu">
                                <option value="">Products</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" >{{ $product->model }}</option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md col-sm-2">
                            {{-- <div class="card-header">
                                <h4 class="card-title mb-1">Storage</h4>
                            </div> --}}
                            <select name="storage" class="form-control form-select">
                                <option value="">Storage</option>
                                @foreach ($storages as $id=>$name)
                                    <option value="{{ $id }}" >{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md col-sm-2">
                            {{-- <div class="card-header">
                                <h4 class="card-title mb-1">Storage</h4>
                            </div> --}}
                            <select name="color" class="form-control form-select">
                                <option value="">Color</option>
                                @foreach ($colors as $id=>$name)
                                    <option value="{{ $id }}" >{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md col-sm-2">
                            <div class="form-floating">
                                <input type="text" class="form-control pd-x-20" value="" name="price" placeholder="Price">
                                <label for="">Price</label>
                            </div>
                        </div>
                        <div class="col-md col-sm-2">
                            <select name="vendor_grade" class="form-control form-select">
                                <option value="">Vendor Grade</option>
                                @foreach ($vendor_grades as $vendor_grade)
                                    <option value="{{ $vendor_grade->id }}" @if(session('vendor_grade') && $vendor_grade->id == session('vendor_grade')) {{'selected'}}@endif @if(request('vendor_grade') && $vendor_grade->id == request('vendor_grade')) {{'selected'}}@endif>{{ $vendor_grade->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        </div>
                        <br>
                    @endif
                    <div class="d-flex justify-content-between">
                        <div class="col-md col-sm-2">
                            <select name="grade" class="form-control form-select">
                                <option value="">Move to</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}" @if(session('grade') && $grade->id == session('grade')) {{'selected'}}@endif @if(request('grade') && $grade->id == request('grade')) {{'selected'}}@endif>{{ $grade->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md col-sm-2">
                            <select name="sub_grade" class="form-control form-select">
                                <option value="">Sub Grade</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}" @if(session('sub_grade') && $grade->id == session('sub_grade')) {{'selected'}}@endif @if(request('sub_grade') && $grade->id == request('sub_grade')) {{'selected'}}@endif>{{ $grade->name }}</option>
                                    {{-- <option value="{{ $grade->id }}">{{ $grade->name }}</option> --}}
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md col-sm-2">
                            <div class="form-floating">
                                <input type="text" class="form-control pd-x-20" value="{{session('description')}}" name="description" placeholder="Reason">
                                <label for="">Reason</label>
                            </div>
                        </div>
                        @if (session('user')->hasPermission('advanced_move_inventory'))

                            <div class="col-md col-sm-2">
                                <select name="if_grade" class="form-control form-select">
                                    <option value="">Don't Move if not Grade</option>
                                    @foreach ($grades as $grade)
                                        <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        @endif
                        <div class="col-md col-sm-2">
                            <div class="form-floating">
                                <input type="text" class="form-control focused" id="imeiInput" name="imei" placeholder="Enter IMEI" value="@isset($_GET['imei']){{$_GET['imei']}}@endisset" onload="this.focus()" autofocus required>
                                <label for="imeiInput">IMEI</label>
                            </div>
                        </div>
                            <button class="btn btn-primary pd-x-20" type="submit">Send</button>
                    </div>
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
                </form>
            </div>
        </div>
        <br>
        @if (isset($stocks))
        <div class="row">
            <div class="col-md-12" style="border-bottom: 1px solid rgb(216, 212, 212);">
                <center><h4>Moved Inventory Today</h4></center>
            </div>
        </div>
        <br>

        <script>
            function checkAlls() {
                var checkboxes = document.querySelectorAll('input[type="checkbox"]');
                var checkAllCheckbox = document.getElementById('checkAll');

                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = checkAllCheckbox.checked;
                });
            }
        </script>

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
            {{-- <script>
                alert("{{session('error')}}");
            </script> --}}
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
                            <h4 class="card-title mg-b-0">
                                @if (session('user')->hasPermission('delete_multiple_moves'))

                                    <form id="pdf" method="POST" action="{{url('move_inventory/delete_multiple_moves')}}">
                                        @csrf
                                        <input type="hidden" name="grade" value="{{ session('grade') }}">
                                        <input type="hidden" name="description" value="{{ session('description') }}">
                                        <label>
                                            <input type="checkbox" id="checkAll" onclick="checkAlls()"> Check All
                                        </label>
                                        <input class="btn btn-sm btn-secondary" type="submit" value="Delete Selected">

                                    </form>
                                @endif
                            </h4>

                            <h5 class="card-title mg-b-0">
                                @if (request('search') == '')

                                    {{ __('locale.From') }} {{$stocks->firstItem()}} {{ __('locale.To') }} {{$stocks->lastItem()}} {{ __('locale.Out Of') }} {{$stocks->total()}}
                                @endif
                            </h5>
                            <div>
                            <select id="per_page" class="form-select form-select-sm" onchange="this.form.submit()" name="per_page" form="search">
                                <option value="10" @if(isset($_GET['per_page']) && $_GET['per_page'] == 10) {{'selected'}}@endif>10</option>
                                <option value="25" @if(isset($_GET['per_page']) && $_GET['per_page'] == 25) {{'selected'}}@endif>25</option>
                                <option value="50" @if(isset($_GET['per_page']) && $_GET['per_page'] == 50) {{'selected'}}@endif>50</option>
                                <option value="100" @if(isset($_GET['per_page']) && $_GET['per_page'] == 100) {{'selected'}}@endif>100</option>
                            </select>
                            </div>

                        </div>
                        <div class="d-flex justify-content-center">

                            <form method="get" action="" class="form-inline" id="search">

                                <div class="form-floating">
                                    <input class="form-control" id="start_date_input" name="start_date" id="datetimepicker" type="date" value="@isset($_GET['start_date']){{$_GET['start_date']}}@endisset" oninput="this.form.submit()">
                                    <label for="start_date_input">{{ __('locale.Start Date') }}</label>
                                </div>
                                <div class="form-floating">
                                    <input class="form-control" id="end_date_input" name="end_date" id="datetimepicker" type="date" value="@isset($_GET['end_date']){{$_GET['end_date']}}@endisset" oninput="this.form.submit()">
                                    <label for="end_date_input">{{ __('locale.End Date') }}</label>
                                </div>
                                <select id="adm_input" name="adm" class="form-control form-select form-select-sm" data-bs-placeholder="Select Processed By" title="Processed by" onchange="this.form.submit()">
                                    <option value="">All</option>
                                    @foreach ($admins as $adm)
                                        <option value="{{$adm->id}}" @if(isset($_GET['adm']) && $adm->id == $_GET['adm']) {{'selected'}}@endif>{{$adm->first_name." ".$adm->last_name}}</option>
                                    @endforeach
                                </select>

                                <div class="form-floating">
                                    <input class="form-control" id="search_input" name="search" type="text" value="@isset($_GET['search']){{$_GET['search']}}@endisset" onchange="this.form.submit()" placeholder="Search">
                                    <label for="search_input"> Search Entry </label>
                                </div>
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="imei" name="imei" placeholder="Enter IMEI" value="@isset($_GET['imei']){{$_GET['imei']}}@endisset" onchange="this.form.submit()">
                                    <label for="imei">IMEI</label>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card-body"><div class="table-responsive">

                            <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                                <thead>
                                    <tr>
                                        @if (session('user')->hasPermission('delete_multiple_moves'))
                                        <th><small><b></b></small></th>
                                        @endif
                                        <th><small><b>No</b></small></th>
                                        <th><small><b>Old Variation</b></small></th>
                                        <th><small><b>New Variation</b></small></th>
                                        <th><small><b>IMEI</b></small></th>
                                        <th><small><b>Vendor | Lot</b></small></th>
                                        <th><small><b>Reason</b></small></th>
                                        <th><small><b>Processor</b></small></th>
                                        <th><small><b>DateTime</b></small></th>
                                        <th><small><b></b></small></th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        if(request('search') == ''){
                                            $i = $stocks->firstItem() - 1;
                                        }else{
                                            $i = 0;
                                            $list = [];
                                        }

                                    @endphp
                                    @foreach ($stocks as $operation)
                                        @php
                                            $stock = $operation->stock;
                                        @endphp
                                        @if (request('search') != '')
                                            @if (!in_array($operation->stock_id, $list))
                                                @php
                                                    $list[] = $operation->stock_id;
                                                @endphp
                                            @else
                                                @continue
                                            @endif
                                        @endif
                                            <tr>

                                                @if (session('user')->hasPermission('delete_multiple_moves'))
                                                <td><input type="checkbox" name="ids[]" value="{{ $operation->id }}" form="pdf"></td>
                                                @endif
                                                <td title="{{ $operation->id }}">{{ $i + 1 }}</td>
                                                <td>
                                                    @if ($operation->old_variation ?? false)
                                                        <strong>{{ $operation->old_variation->sku }}</strong>{{ " - " . $operation->old_variation->product->model . " - " . (isset($operation->old_variation->storage_id)?$operation->old_variation->storage_id->name . " - " : null) . (isset($operation->old_variation->color_id)?$operation->old_variation->color_id->name. " - ":null)}} <strong><u>{{ (isset($operation->old_variation->grade_id)?$operation->old_variation->grade_id->name:null) . (isset($operation->old_variation->sub_grade_id)?$operation->old_variation->sub_grade_id->name:null)}} </u></strong>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($operation->new_variation ?? false)
                                                        <strong>{{ $operation->new_variation->sku }}</strong>{{ " - " . $operation->new_variation->product->model . " - " . (isset($operation->new_variation->storage_id)?$operation->new_variation->storage_id->name . " - " : null) . (isset($operation->new_variation->color_id)?$operation->new_variation->color_id->name. " - ":null)}} <strong><u>{{ $operation->new_variation->grade_id->name ?? "Grade Missing" }} {{ $operation->new_variation->sub_grade_id->name ?? "" }}</u></strong>
                                                    @endif
                                                    @if ($stock->variation_id != $operation->new_variation_id)
                                                        <strong class="text-danger"> CHANGED </strong>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{url('imei')}}?imei={{ $stock->imei ?? null }}{{ $stock->serial_number ?? null }}"> {{ $stock->imei ?? null }}{{ $stock->serial_number ?? null }}</a>

                                                    @if ($stock->status == 2)
                                                        <strong class="text-danger"> SOLD </strong>
                                                    @endif
                                                </td>
                                                <td>{{ $stock->order->customer->first_name ?? "Purchase Order Missing"}} | {{$stock->order->reference_id ?? null}}</td>
                                                <td>{{ $operation->description }}</td>
                                                <td>{{ $operation->admin->first_name ?? null }}</td>
                                                <td>{{ $operation->created_at }}</td>
                                                <td>
                                                    <form method="POST" action="{{url('move_inventory/delete_move')}}">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $operation->id }}">
                                                        <input type="hidden" name="grade" value="{{ session('grade') }}">
                                                        <input type="hidden" name="description" value="{{ session('description') }}">
                                                        <button type="submit" class="btn btn-link"><i class="fa fa-trash"></i></button>
                                                    </form>

                                                </td>
                                            </tr>
                                        @php
                                            $i ++;
                                        @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        <br>
                        @if (request('search') == '')
                        {{ $stocks->onEachSide(1)->links() }} {{ __('locale.From') }} {{$stocks->firstItem()}} {{ __('locale.To') }} {{$stocks->lastItem()}} {{ __('locale.Out Of') }} {{$stocks->total()}}
                        @endif
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
