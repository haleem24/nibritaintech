<!-- resources/views/invoices/invoice.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>RMA Invoice</title>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            font-size: 14px;
            /* line-height: 10px; */
        }

        .invoice-container {
            max-width: 800px;
            /* margin: 20px auto; */
            /* padding: 20px; */
            /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); */
        }

        .company-info, .invoice-header, .customer-details, .order-details, .order-items, .total-amount {
            /* margin-bottom: 20px; */
        }

        .invoice-header h2, .customer-details h3, .order-details h3, .total-amount h3 {
            /* border-bottom: 2px solid #333; */
            /* padding-bottom: 5px;
            margin-bottom: 10px; */
        }

        .order-items table {
            /* width: 100%;
            border-collapse: collapse;
            margin-top: 20px; */
        }


        .total-amount {
            /* text-align: right; */
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .invoice-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    {{-- <iframe src="{{ $order->delivery_note_url }}"></iframe> --}}
    <div class="invoice-container">
        <!-- Company Information -->
        <table border="0">
            <tr style="text-align: right; padding:0; margin:0;">
                <td style="text-align: left; padding:0; margin:0; line-height:10px">

                    {{-- <div class="company-info"> --}}
                        <br><br>
                        <img src="{{ public_path('assets/img/brand').'/'.env('APP_LOGO') }}" alt="" height="50">
                    {{-- </div> --}}
                        {{-- <br> --}}
                        {{-- <br> --}}
                </td>
                <td width="150"></td>
                <td width="200" style="line-height:8px;">
                        <h4><strong>{{ env('APP_NAME') }}</strong></h4>
                        <h4>Cromac Square,</h4>
                        <h4>Forsyth House,</h4>
                        <h4>Belfast, BT2 8LA</h4>
                        <h4>invoice@nibritaintech.com</h4>

                </td>

            </tr>

            <tr style="border-top: 1px solid Black">
                <td>
                    <table>
                    <tr>
                        <br>
                        <td colspan="2"><h3 style="line-height:10px; margin:0px; ">Bill To:</h3></td>
                    </tr>
                    <tr>
                        <td width="20"></td>
                        <td>
                            <div style="line-height:10px;">
                                <h5>{{ $customer->company }}</h5>
                                <h5>{{ $customer->first_name." ".$customer->last_name }}</h5>
                                <h5>{{ $customer->email }}</h5>
                                <h5>{{ $customer->phone }}</h5>
                                <h5>{{ $customer->street }} {{ $customer->street2 }}, {{ $customer->city }}</h5>
                                <h5>{{ $customer->postal_code }} {{ $customer->country_id->title ?? null }}</h5>
                                <h5>{{ $customer->vat }}</h5>
                                <!-- Add more customer details as needed -->
                            </div>
                        </td>
                    </tr>
                    </table>
                </td>
                <td>

                </td>
                {{-- <td></td> --}}
                <td style="text-align: right; padding:0; margin:0; line-height:10px" width="200">
                    <br><br>
                    <h1 style="font-size: 26px; text-align:right;">INVOICE</h1>
                    <table cellspacing="4">

                    <br><br><br><br>
                        <tr>
                            <td width="40"></td>
                            <td style="text-align: left; margin-top:5px;" width="70"><h4><strong>Order ID:</strong></h4></td>
                            <td colspan="2" width="70"><h4 style="font-weight: 400">{{ $order->reference_id }}</h4></td>
                        </tr>
                        @if ($order->admin)

                        <tr>
                            <td></td>
                            <td style="text-align: left; margin-top:5px;"><h4><strong>Sales Rep:</strong></h4></td>
                            <td colspan="2"><h4 style="font-weight: 400">{{ $order->admin->first_name }}</h4></td>
                        </tr>
                        @endif
                        <tr>
                            <td></td>
                            <td style="text-align: left; margin-top:5px;"><h4><strong>Order Date:</strong></h4></td>
                            <td colspan="2"><h4 style="font-weight: 400">{{ \Carbon\Carbon::parse($order->created_at)->format('d-m-Y') }}</h4></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="text-align: left; margin-top:5px;"><h4><strong>Invoice Date:</strong></h4></td>
                            <td colspan="2"><h4 style="font-weight: 400">{{ \Carbon\Carbon::parse($order->processed_at)->format('d-m-Y') }}</h4></td>
                        </tr>
                    </table>
                    {{-- <h3><strong>Order ID:</strong> {{ $order->reference_id }}</h3>
                    <h3><strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}</h4><h4> {{ \Carbon\Carbon::parse($order->created_at)->format('H:m:s') }}&nbsp;</h3>
                    <h3><strong>Invoice Date:</strong> {{ \Carbon\Carbon::parse($order->updated_at)->format('d/m/Y') }}</h4><h4> {{ \Carbon\Carbon::parse($order->updated_at)->format('H:m:s') }}&nbsp;</h3> --}}
                </td>
            </tr>
        </table>


        <!-- Order Items -->
        <div class="order-items">
            <br>
            {{-- <h3>Order Items</h3> --}}
            <table cellpadding="5">
                <thead border="1">
                    <tr border="1">
                        <th width="320" border="0.1">Product Name</th>
                        <th width="80" border="0.1">Price</th>
                        <th width="40" border="0.1">Qty</th>
                        <th width="90" border="0.1">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalAmount = 0;
                        $totalQty = 0;
                    @endphp
                    @foreach ($order_items as $item)
                        @php
                            // $itemTotal = $item->quantity * $item->price;
                            $totalAmount += $item->total_price;
                            $totalQty += $item->total_quantity;

                            if($item->storage){
                                $storage = $storages[$item->storage] ;
                            }else {
                                $storage = null;
                            }
                            // if($item->color){
                            //     $color = $colors[$item->color] . " - " ;
                            // }else {
                            //     $color = null;
                            // }
                        @endphp
                        <tr>
                            <td width="320">{{ $item->model . " - " . $storage }}</td>
                            @if ($invoice != 1)
                            <td width="80" align="right">€{{ amount_formatter($item->average_price,2) }}</td>
                            @else
                            <td width="80" align="right">{{ $order->currency_id->sign }}{{ amount_formatter($item->average_price*$order->exchange_rate,2) }}</td>
                            @endif
                            <td width="40">{{ $item->total_quantity }}</td>
                            @if ($invoice != 1)
                            <td width="90" align="right">€{{ amount_formatter($item->total_price,2) }}</td>
                            @else
                            <td width="90" align="right">{{ $order->currency_id->sign }}{{ amount_formatter($item->total_price*$order->exchange_rate,2) }}</td>
                            @endif
                            {{-- <td width="80" align="right">{{ $order->currency_id->sign }}{{ amount_formatter($item->average_price,2) }}</td>
                            <td width="40">{{ $item->total_quantity }}</td>
                            <td width="90" align="right">{{ $order->currency_id->sign }}{{ amount_formatter($item->total_price,2) }}</td> --}}
                        </tr>
                    @endforeach
                        {{-- <tr>
                            <td width="320">Accessories</td>
                            <td width="80" align="right">{{ $order->currency_id->sign }}0.00</td>
                            <td width="40">{{ $totalQty }}</td>
                            <td width="90" align="right">{{ $order->currency_id->sign }}0.00</td>
                        </tr> --}}
                    <hr>
                </tbody>
                <tfoot>
                    <tr style="border-top: 1px solid Black" >
                        <td></td>
                        <td colspan="3">
                            <table cellpadding="5">
                                <tr>
                                    <td>Sub Total:</td>
                                    {{-- <td>{{$totalQty}}</td> --}}
                                    @if ($invoice != 1)
                                    <td align="right"> <strong> €{{amount_formatter( $totalAmount,2) }}</strong></td>
                                    @else
                                    <td align="right"> <strong>{{ $order->currency_id->sign }}{{amount_formatter( $totalAmount*$order->exchange_rate,2) }}</strong></td>
                                    @endif
                                    {{-- <td align="right"> <strong>{{ $order->currency_id->sign }}{{amount_formatter( $totalAmount,2) }}</strong></td> --}}
                                </tr>
                                <tr>
                                    <td>Qty:</td>
                                    <td align="right"> <strong>{{$totalQty}} </strong></td>
                                </tr>
                                    <br>
                                    <hr>
                                    <tr>
                                        <td>Amount Due:</td>
                                        @if ($invoice != 1)
                                        <td align="right"> <strong> €{{amount_formatter( $totalAmount,2) }}</strong></td>
                                        @else
                                        <td align="right"> <strong>{{ $order->currency_id->sign }}{{amount_formatter( $totalAmount*$order->exchange_rate,2) }}</strong></td>
                                        @endif
                                        {{-- <td align="right"> <strong>{{ $order->currency_id->sign }}{{amount_formatter( $totalAmount,2) }}</strong></td> --}}
                                    </tr>
                                    {{-- <tr>
                                        <td>Back Market:</td>
                                        <td align="right"> <strong>{{ $order->currency_id->sign }}{{amount_formatter( $totalAmount,2) }}</strong></td>
                                    </tr>
                                    <hr>
                                    <tr>
                                        <td>Change:</td>
                                        <td align="right"> <strong>{{ $order->currency_id->sign }}0.00</strong></td>
                                    </tr> --}}
                            </table>



                        </td>

                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- Total Amount -->
        <div class="total-amount" style="padding:0; margin:0; line-height:6px">

            @if ($order->reference)
            <h5>Ref: {{ $order->reference }}</h5>
            <br>
            @endif
            <h3>Store Policy</h3>
            <hr>
            <h4>Stock Sold on Marginal VAT Scheme.</h4>
        </div>
    </div>
</body>
</html>
