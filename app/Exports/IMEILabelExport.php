<?php

namespace App\Exports;

use App\Models\Order_item_model;
use App\Models\Stock_model;
use App\Models\Stock_operations_model;
use App\Models\Variation_model;
use Illuminate\Support\Facades\DB;
use TCPDF;
use Picqer\Barcode\BarcodeGeneratorPNG;

class IMEILabelExport
{
    public function generatePdf()
    {
        $stock_id = request('stock_id');
        $stock = Stock_model::find($stock_id);
        // Fetch the product variation, order, and stock movements
        $variation = Variation_model::with(['product', 'storage_id', 'color_id', 'grade_id'])
                ->find($stock->variation_id);

        $orders = Order_item_model::where('stock_id', $stock_id)->orderBy('id','desc')->get();

        $stock_operations = Stock_operations_model::where('stock_id', $stock_id)->orderBy('id','desc')->get();
        // Fallback to N/A if IMEI is not available
        $imei = $stock->imei ?? 'N/A';

        // Create a new PDF document using TCPDF
        $pdf = new TCPDF('P', 'mm', array(62, 100), true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Company');
        $pdf->SetTitle('Product Label');
        $pdf->SetSubject('Product Label with History');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(2, 5, 2);

        // Add a page
        $pdf->AddPage();

        // Set font for the content
        $pdf->SetFont('helvetica', '', 9);
        $model = $variation->product->model;
        $storage = $variation->storage_id->name ?? '';
        $color = $variation->color_id->name ?? '';
        $grade = $variation->grade_id->name ?? '';
        // Write product information
        $html = '
            <h5 style="margin:0px; padding:0px;"><strong>' . $model . ' ' . $storage . ' ' . $color . ' ' . $grade . '<br>
            IMEI:</strong> ' . $imei. '</h5>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // Add Barcode for IMEI
        if ($imei !== 'N/A') {
            // The IMEI barcode, set the parameters for the barcode (width, height, style, etc.)
            $pdf->write1DBarcode($imei, 'C128', '', '', '', 10, 0.4, ['position' => 'C', 'align' => 'C'], 'N');
        } else {
            $pdf->Write(0, 'IMEI not available');
        }

        // Write Stock Movement history if needed
        $pdf->Ln(5); // Add some spacing
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Write(0, 'Stock Movement History:', '', 0, 'L', true, 0, false, false, 0);

        foreach ($stock_operations as $movement) {
            $movementDetails = $movement->created_at . ' - ' . ($movement->admin->first_name ?? 'Unknown') . ' - ' .
                'From: ' . ($movement->old_variation->sku ?? 'N/A') . ' To: ' . ($movement->new_variation->sku ?? 'N/A');
            $pdf->Write(0, $movementDetails, '', 0, 'L', true, 0, false, false, 0);
        }

        // Output the PDF as a response
        return $pdf->Output('product_label.pdf', 'I');

        if(request('start_date') != '' && request('start_time') != ''){
            $start_date = request('start_date').' '.request('start_time');
        }elseif(request('start_date') != ''){
            $start_date = request('start_date');
        }else{
            $start_date = 0;
        }
        if(request('end_date') != '' && request('end_time') != ''){
            $end_date = request('end_date').' '.request('end_time');
        }elseif(request('end_date') != ''){
            $end_date = request('end_date')." 23:59:59";
        }else{
            $end_date = now();
        }
        // Fetch data from the database
        $data = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('variation', 'order_items.variation_id', '=', 'variation.id')
            ->join('products', 'variation.product_id', '=', 'products.id')
            ->join('color', 'variation.color', '=', 'color.id')
            ->join('storage', 'variation.storage', '=', 'storage.id')
            ->join('grade', 'variation.grade', '=', 'grade.id')
            ->select(
                'variation.sku',
                'variation.storage',
                'variation.color',
                'variation.grade',
                'products.model',
                'color.name as color',
                'storage.name as storage',
                'grade.name as grade_name',
                DB::raw('SUM(order_items.quantity) as total_quantity')
            )
            ->where(['orders.deleted_at' => null, 'order_items.deleted_at' => null, 'variation.deleted_at' => null, 'products.deleted_at' => null])
            ->where('orders.order_type_id',3)
            ->when(request('start_date') != '', function ($q) use ($start_date) {
                return $q->where('orders.created_at', '>=', $start_date);
            })
            ->when(request('end_date') != '', function ($q) use ($end_date) {
                return $q->where('orders.created_at', '<=', $end_date);
            })
            ->when(request('status') != '', function ($q) {
                return $q->where('orders.status', request('status'));
            })
            // ->when(request('order_id') != '', function ($q) {
            //     if(str_contains(request('order_id'),'<')){
            //         $order_ref = str_replace('<','',request('order_id'));
            //         return $q->where('orders.reference_id', '<', $order_ref);
            //     }elseif(str_contains(request('order_id'),'>')){
            //         $order_ref = str_replace('>','',request('order_id'));
            //         return $q->where('orders.reference_id', '>', $order_ref);
            //     }elseif(str_contains(request('order_id'),'<=')){
            //         $order_ref = str_replace('<=','',request('order_id'));
            //         return $q->where('orders.reference_id', '<=', $order_ref);
            //     }elseif(str_contains(request('order_id'),'>=')){
            //         $order_ref = str_replace('>=','',request('order_id'));
            //         return $q->where('orders.reference_id', '>=', $order_ref);
            //     }elseif(str_contains(request('order_id'),'-')){
            //         $order_ref = explode('-',request('order_id'));
            //         return $q->whereBetween('orders.reference_id', $order_ref);
            //     }elseif(str_contains(request('order_id'),',')){
            //         $order_ref = explode(',',request('order_id'));
            //         return $q->whereIn('orders.reference_id', $order_ref);
            //     }elseif(str_contains(request('order_id'),' ')){
            //         $order_ref = explode(' ',request('order_id'));
            //         return $q->whereIn('orders.reference_id', $order_ref);
            //     }else{
            //         return $q->where('orders.reference_id', 'LIKE', request('order_id') . '%');
            //     }
            // })

            ->when(request('order_id') != '', function ($q) {
                return $this->filterOrderId($q, request('order_id'));
            })
            ->when(request('last_order') != '', function ($q) {
                return $q->where('orders.reference_id', '>', request('last_order'));
            })
            ->when(request('sku') != '', function ($q) {
                return $q->whereHas('order_items.variation', function ($q) {
                    $q->where('sku', 'LIKE', '%' . request('sku') . '%');
                });
                // where('orders.order_items.variation.sku', 'LIKE', '%' . request('sku') . '%');
            })
            ->when(request('imei') != '', function ($q) {
                return $q->whereHas('order_items.stock', function ($q) {
                    $q->where('imei', 'LIKE', '%' . request('imei') . '%');
                });
            })
            ->groupBy('variation.sku', 'products.model', 'variation.storage', 'variation.color', 'variation.grade', 'color.name', 'storage.name', 'grade.name')
            ->orderBy('products.model', 'ASC')
            // ->orderBy('storage.name', 'ASC')
            ->orderBy('variation.storage', 'ASC')
            ->orderBy('variation.color', 'ASC')
            ->orderBy('variation.grade', 'ASC')
            ->orderBy('variation.sku', 'ASC')
            // ->orderBy('grade.name', 'ASC')
            ->get();

        // Calculate the total number of distinct orders
        $totalOrders = DB::table('orders')
            ->where('deleted_at', null)
            ->where('order_type_id', 3)
            ->when(request('start_date') != '', function ($q) use ($start_date) {
                return $q->where('created_at', '>=', $start_date);
            })
            ->when(request('end_date') != '', function ($q) use ($end_date) {
                return $q->where('created_at', '<=', $end_date);
            })
            ->when(request('status') != '', function ($q) {
                return $q->where('status', request('status'));
            })
            ->when(request('order_id') != '', function ($q) {
                return $this->filterOrderId($q, request('order_id'));
            })
            ->when(request('last_order') != '', function ($q) {
                return $q->where('reference_id', '>', request('last_order'));
            })
            ->count();
        // Create a TCPDF instance
        $pdf = new TCPDF();
        $pdf->SetMargins(10, 10, 10);

        // Add a new page
        $pdf->AddPage();
        // Add heading cell at the top center
        $pdf->Cell(0, 10, 'Pick list', 0, 1, 'C');

        // Set font
        $pdf->SetFont('times', 'B', 12);

        // Add headings
        $pdf->Cell(8, 10, 'No');
        $pdf->Cell(110, 10, 'Product Name');
        $pdf->Cell(20, 10, 'Grade');
        $pdf->Cell(8, 10, 'Qty');
        $pdf->Cell(50, 10, 'Barcode');

        // Set font for data
        $pdf->SetFont('times', '', 12);

        // Create a BarcodeGenerator instance
        $barcodeGenerator = new BarcodeGeneratorPNG();
        $i = 0;
        $j = 0;
        // Iterate through data and add to PDF
        foreach ($data as $order) {
            $i++;
            $j += $order->total_quantity;
            $pdf->Ln();
            // Set line style for all borders
            $pdf->SetLineStyle(['width' => 0.1, 'color' => [0, 0, 0]]);
            // $pdf->Cell(110, 10, $order->name, 1);
            // Add Product Name (ellipsize to fit within 110)
            $pdf->Cell(8, 10, $i, 1);
            $variationName = $this->ellipsize($order->model." - ".$order->storage." - ".$order->color, 60);
            $pdf->Cell(110, 10, $variationName, 1);
            $pdf->Cell(22, 10, $order->grade_name, 1);
            $pdf->Cell(5, 10, $order->total_quantity, 1);

            // Generate and add barcode with SKU text
            $barcodeImage = $this->generateBarcodeWithSku($barcodeGenerator, $order->sku);
            $pdf->Image($barcodeImage, $pdf->GetX() + 2, $pdf->GetY() + 1, 50, 13);
        }


        $pdf->Ln();
        $pdf->Ln();
        $pdf->Cell(110, 10, "Total Item");
        $pdf->Cell(22, 10, $j);

        $pdf->Ln();
        $pdf->Cell(110, 10, "Total Orders", 0, 0);
        $pdf->Cell(22, 10, $totalOrders);

        $pdf->Ln();
        $pdf->Cell(110, 10, "Start Date & Time");
        $pdf->Cell(22, 10, $start_date);

        $pdf->Ln();
        $pdf->Cell(110, 10, "End Date & Time");
        $pdf->Cell(22, 10, $end_date);

        // Output PDF to the browser
        $pdf->Output('orders.pdf', 'I');
    }

    // Custom function for ellipsizing text
    private function ellipsize($text, $max_length) {
        if (mb_strlen($text, 'UTF-8') > $max_length) {
            $text = mb_substr($text, 0, $max_length - 3, 'UTF-8') . '...';
        }
        return $text;
    }

    private function generateBarcodeWithSku($barcodeGenerator, $sku)
    {

        // Sanitize SKU to remove any special characters that may cause issues
        $sanitizedSku = preg_replace('/[^A-Za-z0-9_\-]/', '_', $sku);

        // Generate barcode image
        $barcodeImage = imagecreatefromstring($barcodeGenerator->getBarcode($sanitizedSku, $barcodeGenerator::TYPE_CODE_128));

        // Generate barcode image
        // $barcodeImage = imagecreatefromstring($barcodeGenerator->getBarcode($sku, $barcodeGenerator::TYPE_CODE_128));

        // Create a new image with space for SKU below the barcode
        $combinedImageWidth = imagesx($barcodeImage) + 20; // Adjust the space as needed
        $combinedImageHeight = imagesy($barcodeImage) + 30; // Adjust the space as needed
        $combinedImage = imagecreatetruecolor($combinedImageWidth, $combinedImageHeight);

        // Set background color to white
        $whiteColor = imagecolorallocate($combinedImage, 255, 255, 255);
        imagefill($combinedImage, 0, 0, $whiteColor);

        // Copy barcode into the new image
        imagecopy($combinedImage, $barcodeImage, 0, 0, 0, 0, imagesx($barcodeImage), imagesy($barcodeImage));

        // Use a built-in font for SKU text
        $font = 5; // Built-in font number (5 represents a small font, you can experiment with different values)
        $skuColor = imagecolorallocate($combinedImage, 0, 0, 0); // Black color for SKU text

        // Calculate the position to center the SKU text
        $skuWidth = imagefontwidth($font) * strlen($sku);
        $skuX = ($combinedImageWidth - $skuWidth) / 2;
        $skuY = imagesy($barcodeImage) - 2; // Adjust the space between barcode and SKU as needed

        // Add SKU text to the new image
        imagestring($combinedImage, $font, $skuX, $skuY, $sku, $skuColor);

        // Save the combined image
        $path = storage_path('app/barcodes/');
        $filename = $sanitizedSku  . '.png';
        imagepng($combinedImage, $path . $filename);

        return $path . $filename;
    }

    private function filterOrderId($query, $order_id)
    {
        // Function to handle complex order_id filtering logic
        if (str_contains($order_id, '<')) {
            $order_ref = str_replace('<', '', $order_id);
            return $query->where('orders.reference_id', '<', $order_ref);
        } elseif (str_contains($order_id, '>')) {
            $order_ref = str_replace('>', '', $order_id);
            return $query->where('orders.reference_id', '>', $order_ref);
        } elseif (str_contains($order_id, '<=')) {
            $order_ref = str_replace('<=', '', $order_id);
            return $query->where('orders.reference_id', '<=', $order_ref);
        } elseif (str_contains($order_id, '>=')) {
            $order_ref = str_replace('>=', '', $order_id);
            return $query->where('orders.reference_id', '>=', $order_ref);
        } elseif (str_contains($order_id, '-')) {
            $order_ref = explode('-', $order_id);
            return $query->whereBetween('orders.reference_id', $order_ref);
        } elseif (str_contains($order_id, ',')) {
            $order_ref = explode(',', $order_id);
            return $query->whereIn('orders.reference_id', $order_ref);
        } elseif (str_contains($order_id, ' ')) {
            $order_ref = explode(' ', $order_id);
            return $query->whereIn('orders.reference_id', $order_ref);
        } else {
            return $query->where('orders.reference_id', 'LIKE', $order_id . '%');
        }
    }

}
