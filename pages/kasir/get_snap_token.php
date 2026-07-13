<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_kasir.php';
require_once '../../includes/konversi_helper.php';
require_once 'midtrans_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$produkArray = $input['produk_kode'] ?? [];
$qtyArray = $input['qty_beli'] ?? [];
$redeemPoin = (int)($input['redeem_poin'] ?? 0);
$memberId = $input['member_id'] ?? null;

if (empty($produkArray)) {
    echo json_encode(['error' => 'Keranjang belanja kosong!']);
    exit;
}

try {
    // 1. Calculate totals and build item details
    $subtotalKeranjang = 0;
    $itemDetails = [];

    foreach ($produkArray as $index => $kodeProduk) {
        $qty = (int)$qtyArray[$index];
        if ($qty <= 0) continue;
        
        $stmtCekHarga = $koneksi->prepare("SELECT nama, hargaJual FROM tproduct WHERE kode = ?");
        $stmtCekHarga->execute([$kodeProduk]);
        $prod = $stmtCekHarga->fetch(PDO::FETCH_ASSOC);
        
        if ($prod) {
            $hargaJual = (float)$prod['hargaJual'];
            $subtotalKeranjang += ($hargaJual * $qty);
            
            $itemDetails[] = [
                'id' => $kodeProduk,
                'price' => (int)$hargaJual,
                'quantity' => $qty,
                'name' => substr($prod['nama'], 0, 50)
            ];
        }
    }

    if (empty($itemDetails)) {
        throw new Exception("Produk tidak ditemukan di database.");
    }

    // Get diskon nominal setting
    $stmtSet = $koneksi->query("SELECT setting_value FROM tsetting WHERE setting_key = 'poin_diskon_nominal'");
    $poin_diskon_nominal = 0;
    if ($rowSet = $stmtSet->fetch(PDO::FETCH_ASSOC)) {
        $poin_diskon_nominal = (float)$rowSet['setting_value'];
    }
    $diskonNominal = $redeemPoin * $poin_diskon_nominal;

    if ($diskonNominal > $subtotalKeranjang) {
        $diskonNominal = $subtotalKeranjang;
    }
    $grandTotal = $subtotalKeranjang - $diskonNominal;

    // Add discount as negative item in Midtrans
    if ($diskonNominal > 0) {
        $itemDetails[] = [
            'id' => 'DISCOUNT',
            'price' => -(int)$diskonNominal,
            'quantity' => 1,
            'name' => 'Diskon Poin Member'
        ];
    }

    // Customer details
    $customerDetails = [];
    if ($memberId) {
        $stmtMember = $koneksi->prepare("SELECT Nama, noHp FROM tmember WHERE noHp = ?");
        $stmtMember->execute([$memberId]);
        $member = $stmtMember->fetch(PDO::FETCH_ASSOC);
        if ($member) {
            $customerDetails = [
                'first_name' => $member['Nama'],
                'phone' => $member['noHp']
            ];
        }
    } else {
        $customerDetails = [
            'first_name' => 'Pelanggan Umum',
            'phone' => '-'
        ];
    }

    // Generate unique order ID
    $stmtNomor = $koneksi->query("SELECT nomor FROM tpenjualan ORDER BY nomor DESC LIMIT 1");
    $last = $stmtNomor->fetch(PDO::FETCH_ASSOC);
    $nomorPenjualan = $last ? $last['nomor'] + 1 : 1;
    $orderId = "PJ-" . $nomorPenjualan . "-" . time();

    // Build payload for Midtrans Snap API
    $payload = [
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => (int)$grandTotal
        ],
        'item_details' => $itemDetails,
        'customer_details' => $customerDetails,
        'credit_card' => [
            'secure' => true
        ]
    ];

    // Call Midtrans Snap API
    $auth = base64_encode(MIDTRANS_SERVER_KEY . ":");
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n" .
                         "Accept: application/json\r\n" .
                         "Authorization: Basic " . $auth . "\r\n",
            'method'  => 'POST',
            'content' => json_encode($payload),
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents(getMidtransSnapUrl(), false, $context);
    
    if ($response === false) {
        throw new Exception("Gagal menghubungi server Midtrans. Pastikan Server Key dan koneksi internet Anda benar.");
    }
    
    $result = json_decode($response, true);
    if (isset($result['error_messages'])) {
        throw new Exception(implode(', ', $result['error_messages']));
    }
    
    if (!isset($result['token'])) {
        throw new Exception($result['message'] ?? "Token pembayaran tidak ditemukan.");
    }

    echo json_encode([
        'token' => $result['token'],
        'order_id' => $orderId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
