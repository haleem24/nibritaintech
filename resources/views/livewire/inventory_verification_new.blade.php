@extends('layouts.new')

@section('styles')
<style type="text/css" media="print">
    @page { size: landscape; }
  </style>


@endsection

    @section('content')


    <div class="">

        <form class="form-inline" action="{{ url('inventory/add_verification_imei').'/'.$active_inventory_verification->id }}" method="POST" id="">
            @csrf
            <div class="input-group">
                <label for="reference" class="">Reference: &nbsp;</label>
                <input type="text" class="form-control form-control-sm" name="reference" id="reference" placeholder="Enter Reference" value="{{ session('reference') }}">
            </div>
                <div class="input-group">
                    <label for="imei" class="">IMEI | Serial Number: &nbsp;</label>
                    <input type="text" class="form-control form-control-sm" name="imei" id="imei" placeholder="Enter IMEI" onloadeddata="$(this).focus()" autofocus required>

                </div>

                <select name="color" class="form-control form-select" style="width: 150px;">
                    <option value="">Color</option>
                    @foreach ($colors as $id => $name)
                        <option value="{{ $id }}"@if($id == session('color')) {{'selected'}}@endif>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="grade" class="form-control form-select">
                    <option value="">Grade</option>
                    @foreach ($grades as $id => $name)
                        <option value="{{ $id }}" @if ($id == session('grade')) {{'selected'}}@endif>{{ $name }}</option>
                    @endforeach
                </select>


                <div class="input-group form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="com" name="copy" value="1" @if (session('copy') == 1) {{'checked'}} @endif>&nbsp;&nbsp;\
                    <label class="form-check-label" for="com">Copy</label>
                </div>
                <button class="btn-sm btn-primary pd-x-20" type="submit">Insert</button>
        </form>
    </div>
    <script>

        window.onload = function() {
            document.getElementById('imei').focus();
        };
        document.addEventListener('DOMContentLoaded', function() {
            var input = document.getElementById('imei');
            input.focus();
            input.select();
        });
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
    <div class="card">
        <div class="card-header pb-0">
            <div class="d-flex justify-content-between">
                <h5 class="card-title mg-b-0">{{ __('locale.From') }} {{$last_ten->firstItem()}} {{ __('locale.To') }} {{$last_ten->lastItem()}} {{ __('locale.Out Of') }} {{$last_ten->total()}} </h5>
                <h4 class="card-title mg-b-0">Latest Scanned</h4>
                <h4 class="card-title mg-b-0">Counter: {{ session('counter') }} <a href="{{ url('inventory/resume_verification?reset_counter=1') }}">Reset</a></h4>

                <h4 class="card-title mg-b-0">Total Scanned: {{$scanned_total}}</h4>
                <form method="get" action="" class="row form-inline">
                    <label for="perPage" class="card-title inline">per page:</label>
                    <select name="per_page" class="form-select form-select-sm" id="perPage" onchange="this.form.submit()">
                        <option value="10" {{ Request::get('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ Request::get('per_page') == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ Request::get('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ Request::get('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body"><div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-md-nowrap">
                    <thead>
                        <tr>
                            <th><small><b>No</b></small></th>
                            <th><small><b>Variation</b></small></th>
                            <th><small><b>IMEI | Serial Number</b></small></th>
                            <th><small><b>Reference</b></small></th>
                            <th><small><b>Operation</b></small></th>
                            <th><small><b>Vendor</b></small></th>
                            <th><small><b>Creation Date</b></small></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $i = $last_ten->firstItem() - 1;
                        @endphp
                        @foreach ($last_ten as $item)
                            <tr>
                                @if ($item->stock == null)
                                    {{$item->stock_id}}
                                    @continue
                                @endif
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $item->stock->variation->product->model ?? "Variation Model Not added"}} {{$storages[$item->stock->variation->storage] ?? null}} {{$colors[$item->stock->variation->color] ?? null}} {{$grades[$item->stock->variation->grade] ?? "Variation Grade Not added Reference: ".$item->stock->variation->reference_id }}</td>
                                <td>{{ $item->stock->imei.$item->stock->serial_number }}</td>
                                <td>{{ $item->description }}</td>
                                <td>{{ $item->stock->latest_operation->description ?? null }}</td>
                                <td>{{ $item->stock->order->customer->first_name ?? "Purchase Entry Error" }}</td>
                                <td style="width:220px">{{ $item->created_at }}</td>
                            </tr>
                            @php
                                $i ++;
                            @endphp
                        @endforeach
                    </tbody>
                </table>
                <br>
                {{ $last_ten->onEachSide(5)->links() }} {{ __('locale.From') }} {{$last_ten->firstItem()}} {{ __('locale.To') }} {{$last_ten->lastItem()}} {{ __('locale.Out Of') }} {{$last_ten->total()}}
        </div>

        </div>
    </div>

    @endsection

    @section('scripts')

    @endsection
