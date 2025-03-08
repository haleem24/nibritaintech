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
                {{-- <span class="main-content-title mg-b-0 mg-b-lg-1">Testing</span> --}}
                </div>
                <div class="justify-content-center mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item tx-15"><a href="/">Dashboards</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Testing</li>
                    </ol>
                </div>
            </div>
        <!-- /breadcrumb -->
        <div class="row">
            <div class="col-md-12" style="border-bottom: 1px solid rgb(216, 212, 212);">
                <center><h4>Testing To</h4></center>
            </div>
        </div>
        <br>

        <div class="" style="border-bottom: 1px solid rgb(216, 212, 212);">

            <div class="p-2">
                <form action="{{ url('testing/change_grade') }}" method="POST" id="search" class="">
                    @csrf

                    @if (session('user')->hasPermission('change_variation'))
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
                            <div class="form-floating">
                                <input type="text" class="form-control pd-x-20" value="{{session('description')}}" name="description" placeholder="Reason">
                                <label for="">Reason</label>
                            </div>
                        </div>
                        <div class="col-md col-sm-2">
                            <div class="form-floating">
                                <input type="text" class="form-control focused" id="imeiInput" name="imei" placeholder="Enter IMEI" value="@isset($_GET['imei']){{$_GET['imei']}}@endisset" onload="this.focus()" autofocus>
                                <label for="imeiInput">IMEI</label>
                            </div>
                        </div>
                            <button class="btn btn-primary pd-x-20" type="submit">Send</button>
                    </div>
                    <script>
                        window.onload = function() {
                            document.getElementById('imeiInput').focus();
                        };
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
                            <h4 class="card-title mg-b-0">

                                <form id="pdf" method="POST" action="{{url('testing/delete_multiple_moves')}}">
                                    @csrf
                                    <input type="hidden" name="grade" value="{{ session('grade') }}">
                                    <input type="hidden" name="description" value="{{ session('description') }}">
                                    <label>
                                        <input type="checkbox" id="checkAll" onclick="checkAlls()"> Check All
                                    </label>
                                    <input class="btn btn-sm btn-secondary" type="submit" value="Delete Selected">

                                </form>
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
                                        <th><small><b></b></small></th>
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
                                        $i = 0;
                                    @endphp
                                    @foreach ($stocks as $operation)

                                            <tr>
                                                <td><input type="checkbox" name="ids[]" value="{{ $operation->id }}" form="pdf"></td>
                                                <td title="{{ $operation->id }}">{{ $i + 1 }}</td>
                                                <td>
                                                    @if ($operation->old_variation ?? false)
                                                        <strong>{{ $operation->old_variation->sku }}</strong>{{ " - " . $operation->old_variation->product->model . " - " . (isset($operation->old_variation->storage_id)?$operation->old_variation->storage_id->name . " - " : null) . (isset($operation->old_variation->color_id)?$operation->old_variation->color_id->name. " - ":null)}} <strong><u>{{ (isset($operation->old_variation->grade_id)?$operation->old_variation->grade_id->name:null)}} </u></strong>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($operation->new_variation ?? false)
                                                        <strong>{{ $operation->new_variation->sku }}</strong>{{ " - " . $operation->new_variation->product->model . " - " . (isset($operation->new_variation->storage_id)?$operation->new_variation->storage_id->name . " - " : null) . (isset($operation->new_variation->color_id)?$operation->new_variation->color_id->name. " - ":null)}} <strong><u>{{ $operation->new_variation->grade_id->name }}</u></strong>
                                                    @endif
                                                </td>
                                                <td>{{ $operation->stock->imei.$operation->stock->serial_number }}</td>
                                                <td>{{ $operation->stock->order->customer->first_name." | ".$operation->stock->order->reference_id }}</td>
                                                <td>{{ $operation->description }}</td>
                                                <td>{{ $operation->admin->first_name }}</td>
                                                <td>{{ $operation->created_at }}</td>
                                                <td>
                                                    <form method="POST" action="{{url('testing/delete_move')}}">
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
