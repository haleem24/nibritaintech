<!-- resources/views/emails/invoice.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Invoice</title>
    <style>
        /* Add any styles specific to email content here */
    </style>
</head>
<body>
    <p>Dear Customer,</p>

    <p>Please find the attached invoice for your recent purchase. Thank you for choosing us!</p>

    <p>Best regards,</p>
    <p>{{env('APP_NAME')}}</p>

    <p><strong>Note: This is an automated message, please do not reply to this email.</strong></p>
</body>
</html>
