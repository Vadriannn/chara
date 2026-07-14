<?php
// AI
// Midtrans Configuration for Chara POS Cashier
// Replace these keys with your own Sandbox or Production keys from your Midtrans Dashboard (https://dashboard.midtrans.com)

define('MIDTRANS_SERVER_KEY', 'Mid-server-LQxRPj6skJiPBzQEb8Rk5-r3'); 
define('MIDTRANS_CLIENT_KEY', 'Mid-client-WcZi9vQKVCgfbUeJ'); 
define('MIDTRANS_IS_PRODUCTION', false); // Set to true for Production, false for Sandbox

// Get the correct API URL based on environment
if (!function_exists('getMidtransSnapUrl')) {
    function getMidtransSnapUrl() {
        return MIDTRANS_IS_PRODUCTION 
            ? 'https://app.midtrans.com/snap/v1/transactions' 
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }
}

if (!function_exists('getMidtransJsUrl')) {
    function getMidtransJsUrl() {
        return MIDTRANS_IS_PRODUCTION 
            ? 'https://app.midtrans.com/snap/snap.js' 
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }
}
?>
