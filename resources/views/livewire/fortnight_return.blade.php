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
                {{-- <span class="main-content-title mg-b-0 mg-b-lg-1">Fortnight Return</span> --}}
                </div>
                <div class="justify-content-center mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item tx-15"><a href="/">Dashboards</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Fortnight Return</li>
                    </ol>
                </div>
            </div>
        <!-- /breadcrumb -->
        <div class="row">
            <div class="col-md-12" style="border-bottom: 1px solid rgb(216, 212, 212);">
                <center><h4>Fortnight Returns</h4></center>
            </div>
        </div>
        <br>

        {{-- <div class="" style="border-bottom: 1px solid rgb(216, 212, 212);">

            <div class="p-2">
                <form action="{{ url('fortnight_return/change_grade') }}" method="POST" id="search" class="">
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
                            <select name="storage" class="form-control form-select">
                                <option value="">Storage</option>
                                @foreach ($storages as $id=>$name)
                                    <option value="{{ $id }}" >{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md col-sm-2">
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
        </div> --}}

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
                        <a href="{{ url('fortnight_return/print').$request_param}}" class="btn btn-secondary">Export</a>
                        @foreach ($returns as $return)
                            <a href="{{ url('fortnight_return').'?tested_by='.$return->tested_by }}" class="btn btn-link">{{ $return }}</a>
                        @endforeach
                        {{-- {{ $returns }} --}}
                    </div>
                    <div class="card-body"><div class="table-responsive" id="reportPrinting">

                            <table class="table table-bordered table-hover mb-0 text-md-nowrap" id="">
                                <thead>
                                    <tr>
                                        <th><small><b>No</b></small></th>
                                        <th><small><b>Order Detail</b></small></th>
                                        <th><small><b>Old Variation</b></small></th>
                                        <th><small><b>New Variation</b></small></th>
                                        <th><small><b>Reason</b></small></th>
                                        <th><small><b>Processor</b></small></th>
                                        <th><small><b>DateTime</b></small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 0;
                                    @endphp
                                    @foreach ($latest_items as $item)

                                        @foreach ($item->stock->stock_operations as $index => $operation)

                                            <tr>
                                                @if ($index == 0)

                                                    <td rowspan="{{ count($item->stock->stock_operations) }}">{{ $i + 1 }}</td>
                                                    <td rowspan="{{ count($item->stock->stock_operations) }}" align="center" width="240">
                                                        <span title="Order Number">{{ $item->reference_id }}</span><br>
                                                        <span title="Customer">{{ $item->refund_order->customer->first_name." ".$item->refund_order->customer->last_name }}</span><br>
                                                        <span title="Product">
                                                            <strong>{{ $item->refund_order->order_items[0]->variation->sku }}</strong><br>
                                                            {{$item->refund_order->order_items[0]->variation->product->model ?? "Model not defined"}} - {{(isset($item->refund_order->order_items[0]->variation->storage_id)?$item->refund_order->order_items[0]->variation->storage_id->name . " - " : null)}}<br>
                                                            {{(isset($item->refund_order->order_items[0]->variation->color_id)?$item->refund_order->order_items[0]->variation->color_id->name. " - ":null)}} <strong><u>{{ $item->refund_order->order_items[0]->variation->grade_id->name }}</u></strong><br>
                                                        </span>
                                                        <span title="IMEI | Invoiced By | Tested By">
                                                            {{ $item->stock->imei.$item->stock->serial_number }}
                                                            @isset($item->refund_order->processed_by) | {{ $item->refund_order->admin->first_name[0] }} | @endisset
                                                            @isset($item->stock->tester) ({{ $item->stock->tester }}) @endisset
                                                        </span><br>
                                                        <span title="Vendor | Lot">{{ $item->stock->order->customer->first_name ?? "Purchase Order Missing"}} | {{$item->stock->order->reference_id ?? null}}</span><br>
                                                        <span title="Invoiced at">{{ $item->refund_order->processed_at }}</span><br>
                                                        <span title="Refunded at">{{ $item->created_at }}</span>
                                                    </td>
                                                @endif
                                                <td title="{{ $operation->id }}">
                                                    @if ($operation->old_variation ?? false)
                                                        <strong>{{ $operation->old_variation->sku }}</strong>{{ " - " . $operation->old_variation->product->model . " - " . (isset($operation->old_variation->storage_id)?$operation->old_variation->storage_id->name . " - " : null) . (isset($operation->old_variation->color_id)?$operation->old_variation->color_id->name. " - ":null)}} <strong><u>{{ (isset($operation->old_variation->grade_id)?$operation->old_variation->grade_id->name:null)}} </u></strong>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($operation->new_variation ?? false)
                                                        <strong>{{ $operation->new_variation->sku }}</strong>{{ " - " . $operation->new_variation->product->model . " - " . (isset($operation->new_variation->storage_id)?$operation->new_variation->storage_id->name . " - " : null) . (isset($operation->new_variation->color_id)?$operation->new_variation->color_id->name. " - ":null)}} <strong><u>{{ $operation->new_variation->grade_id->name ?? "Grade Missing" }}</u></strong>
                                                    @endif
                                                </td>
                                                <td>{{ $operation->description }}</td>
                                                <td>{{ $operation->admin->first_name ?? null }}</td>
                                                <td>{{ $operation->created_at }}</td>
                                            </tr>
                                        @endforeach
                                        @php
                                            $i ++;
                                        @endphp
                                    @endforeach
                                </tbody>
                                {{-- <tbody>
                                    @php
                                        $i = 0;
                                    @endphp
                                    @foreach ($latest_items as $item)
                                        @foreach ($item->stock->stock_operations as $index => $operation)
                                            <tr>
                                                @if ($index == 0)
                                                    <td rowspan="{{ count($item->stock->stock_operations) }}">{{ $i + 1 }}</td>
                                                    <td rowspan="{{ count($item->stock->stock_operations) }}" align="center" width="240">
                                                        <span title="Order Number">{{ $item->reference_id }}</span><br>
                                                        <span title="Customer">{{ $item->refund_order->customer->first_name." ".$item->refund_order->customer->last_name }}</span><br>
                                                        <span title="Product">
                                                            <strong>{{ $item->refund_order->order_items[0]->variation->sku }}</strong><br>
                                                            {{$item->refund_order->order_items[0]->variation->product->model ?? "Model not defined"}} - {{(isset($item->refund_order->order_items[0]->variation->storage_id)?$item->refund_order->order_items[0]->variation->storage_id->name . " - " : null)}}<br>
                                                            {{(isset($item->refund_order->order_items[0]->variation->color_id)?$item->refund_order->order_items[0]->variation->color_id->name. " - ":null)}} <strong><u>{{ $item->refund_order->order_items[0]->variation->grade_id->name }}</u></strong><br>
                                                        </span>
                                                        <span title="IMEI | Invoiced By | Tested By">
                                                            {{ $item->stock->imei.$item->stock->serial_number }}
                                                            @isset($item->refund_order->processed_by) | {{ $item->refund_order->admin->first_name[0] }} | @endisset
                                                            @isset($item->stock->tester) ({{ $item->stock->tester }}) @endisset
                                                        </span><br>
                                                        <span title="Vendor | Lot">{{ $item->stock->order->customer->first_name ?? "Purchase Order Missing"}} | {{$item->stock->order->reference_id ?? null}}</span><br>
                                                        <span title="Invoiced at">{{ $item->refund_order->processed_at }}</span><br>
                                                        <span title="Refunded at">{{ $item->created_at }}</span>
                                                    </td>
                                                @endif
                                                <td title="{{ $operation->id }}">
                                                    @if ($operation->old_variation ?? false)
                                                        <strong>{{ $operation->old_variation->sku }}</strong>{{ " - " . $operation->old_variation->product->model . " - " . (isset($operation->old_variation->storage_id)?$operation->old_variation->storage_id->name . " - " : null) . (isset($operation->old_variation->color_id)?$operation->old_variation->color_id->name. " - ":null)}} <strong><u>{{ (isset($operation->old_variation->grade_id)?$operation->old_variation->grade_id->name:null)}} </u></strong>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($operation->new_variation ?? false)
                                                        <strong>{{ $operation->new_variation->sku }}</strong>{{ " - " . $operation->new_variation->product->model . " - " . (isset($operation->new_variation->storage_id)?$operation->new_variation->storage_id->name . " - " : null) . (isset($operation->new_variation->color_id)?$operation->new_variation->color_id->name. " - ":null)}} <strong><u>{{ $operation->new_variation->grade_id->name ?? "Grade Missing" }}</u></strong>
                                                    @endif
                                                </td>
                                                <td>{{ $operation->description }}</td>
                                                <td>{{ $operation->admin->first_name ?? null }}</td>
                                                <td>{{ $operation->created_at }}</td>
                                                @if ($index > 0)
                                                    <!-- Add empty cells for the first two columns when index > 0 -->
                                                    <td style="display: none;"></td>
                                                    <td style="display: none;"></td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        @php
                                            $i++;
                                        @endphp
                                    @endforeach
                                </tbody> --}}

                            </table>
                        <br>
                    </div>

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


    <script type="text/javascript">
        $(document).ready(function() {
            // $('#datatable').DataTable({
                // processing: true,
                // serverSide: true,
                // ajax: '',
                // columns: [
                //     { data: 'name', name: 'name' },
                //     { data: 'email', name: 'email' },
                //     { data: 'created_at', name: 'created_at' }
                // ]
            // });
            // new DataTable('#datatable', {
            //     layout: {
            //         topStart: {
            //             buttons: ['print']
            //         }
            //     }
            // });
//             new DataTable('#datatable', {
// $('#datatable').DataTable({

//             columnDefs: [
//                 { targets: 0, orderable: false },
//                 { targets: 1, orderable: false },
//                 { targets: 2, orderable: false },
//                 { targets: 3, orderable: false },
//                 { targets: 4, orderable: false },
//                 { targets: 5, orderable: false },
//                 { targets: 6, orderable: false }
//             ]
//         });
        });

    </script>
    @endsection
