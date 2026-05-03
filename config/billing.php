<?php

/*
|--------------------------------------------------------------------------
| Billing — Subscription payments
|--------------------------------------------------------------------------
|
| Dummy GCash flow: tenants pay manually to a fixed merchant number, then
| paste the GCash reference into the invoice. Update these values when
| switching to a real billing provider.
|
*/

return [
    'gcash' => [
        'number'  => env('BILLING_GCASH_NUMBER',  '0917-555-0142'),
        'name'    => env('BILLING_GCASH_NAME',    'Baligya POS'),
        'qr_path' => env('BILLING_GCASH_QR_PATH', '/assets/billing/gcash-qr.png'),
    ],

    'invoice' => [
        // How many days the user has to pay before an invoice expires.
        'due_in_days' => (int) env('BILLING_DUE_IN_DAYS', 7),

        'instructions' => [
            'Open your GCash app and tap "Send Money".',
            'Scan the QR code or enter the GCash number above.',
            'Send the EXACT invoice amount.',
            'Copy the GCash reference number from your receipt.',
            'Return here and paste the reference, then tap "I\'ve paid".',
        ],
    ],
];
