<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
requireAdmin();

$orderId = (int) ($_GET['id'] ?? 0);
if (!$orderId) {
    flash('error', 'No order specified.');
    header('Location: ' . url('admin/orders.php'));
    exit;
}

$order = getInvoiceData($pdo, $orderId);
if (!$order) {
    flash('error', 'Order not found.');
    header('Location: ' . url('admin/orders.php'));
    exit;
}

if (empty($order['invoice_number'])) {
    try {
        $invNum = generateInvoiceNumber($pdo);
        $pdo->prepare(
            'UPDATE orders SET invoice_number = ?, invoice_date = CURDATE() WHERE id = ?'
        )->execute([$invNum, $orderId]);
        $order['invoice_number'] = $invNum;
        $order['invoice_date']   = date('Y-m-d');
    } catch (PDOException $e) {
        // If columns don't exist, generate invoice number without saving to DB
        $order['invoice_number'] = generateInvoiceNumber($pdo);
        $order['invoice_date']   = date('Y-m-d');
    }
}

$invDate        = $order['invoice_date'] ? date('d/m/Y', strtotime($order['invoice_date'])) : date('d/m/Y');
$subtotal       = (float)$order['subtotal'];
$delivery       = (float)($order['delivery_charge'] ?? 0);
$discount       = (float)($order['discount'] ?? 0);
$tax            = (float)($order['tax'] ?? 0);
$grandTotal     = $subtotal + $delivery + $tax - $discount;
if ($grandTotal <= 0) $grandTotal = $subtotal;
$payStatus      = ucfirst($order['payment_status'] ?? 'unpaid');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice <?= e($order['invoice_number']) ?> — <?= e($config['name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --gold:#c9a227;
  --gold2:#e8c547;
  --dark:#0d0d14;
  --dark2:#1a1a2e;
  --text:#1a1a1a;
  --muted:#555;
  --light:#f7f7f5;
  --border:#e0ddd5;
}

/* ── SCREEN CHROME ── */
body{background:#2a2a35;font-family:'Outfit',sans-serif;padding:32px 16px 64px;min-height:100vh}
.screen-toolbar{max-width:780px;margin:0 auto 24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
.toolbar-title{color:#c9a227;font-size:1rem;font-weight:600;letter-spacing:.02em}
.btn-group{display:flex;gap:10px}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:'Outfit',sans-serif;letter-spacing:.02em;transition:all .2s;text-decoration:none}
.btn-print{background:#c9a227;color:#0d0d14}
.btn-print:hover{background:#e8c547}
.btn-back{background:rgba(255,255,255,.08);color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.15)}
.btn-back:hover{background:rgba(255,255,255,.14);color:#fff}
.btn svg{width:15px;height:15px}

/* ── INVOICE PAPER ── */
.invoice-paper{
  max-width:780px;margin:0 auto;background:#fff;
  box-shadow:0 24px 80px rgba(0,0,0,.5);
  position:relative;overflow:hidden;
  min-height:1000px;
}

/* ── POLYGON HEADER (top decoration) ── */
.poly-top{
  position:relative;width:100%;height:220px;overflow:hidden;
  background:#fff;
}
.poly-top svg{position:absolute;top:0;left:0;width:100%;height:100%}

/* INVOICE title + number block */
.inv-title-block{
  position:absolute;right:52px;top:38px;text-align:right;z-index:2;
}
.inv-title-word{
  font-size:3rem;font-weight:800;color:var(--dark);
  letter-spacing:.12em;text-transform:uppercase;line-height:1;
}
.inv-meta-table{margin-top:14px}
.inv-meta-table tr td{font-size:13px;color:var(--muted);padding:2px 0}
.inv-meta-table tr td:last-child{padding-left:32px;color:var(--dark);font-weight:600;text-align:right}

/* ── BODY ── */
.inv-body{padding:0 52px 0}

/* Items table */
.items-table{width:100%;border-collapse:collapse;margin-bottom:0}
.items-table thead tr{background:var(--gold);color:#0d0d14}
.items-table thead th{
  padding:13px 16px;font-size:13px;font-weight:700;
  text-transform:uppercase;letter-spacing:.06em;
}
.items-table thead th:first-child{border-radius:0;text-align:left}
.items-table thead th:not(:first-child){text-align:right}
.items-table thead th:last-child{background:var(--dark);color:#fff;border-radius:0}
.items-table tbody tr{border-bottom:2px solid var(--text)}
.items-table tbody tr:last-child{border-bottom:none}
.items-table tbody td{
  padding:18px 16px;font-size:13.5px;color:var(--text);vertical-align:top;
}
.items-table tbody td:not(:first-child){text-align:right;font-weight:500}
.item-name{font-weight:500;color:var(--text)}
.item-sku{font-size:11px;color:#999;margin-top:2px}

/* Totals */
.totals-area{display:flex;justify-content:flex-end;padding:20px 0 28px}
.totals-inner{width:300px}
.totals-row{
  display:flex;justify-content:space-between;
  padding:7px 0;font-size:13.5px;color:var(--muted);
  border-bottom:1px solid var(--border);
}
.totals-row:last-of-type{border-bottom:none}
.totals-row span:last-child{font-weight:600;color:var(--dark)}
.discount-val{color:#16a34a!important}

/* TOTAL band */
.total-band{
  background:var(--gold);
  display:flex;justify-content:space-between;align-items:center;
  padding:16px 52px;
}
.total-band-label{
  font-size:1rem;font-weight:800;color:var(--dark);
  text-transform:uppercase;letter-spacing:.15em;
}
.total-band-amount{
  font-size:1.25rem;font-weight:800;color:var(--dark);letter-spacing:.02em;
}

/* ── POLYGON FOOTER (bottom decoration) ── */
.poly-bottom{
  position:relative;width:100%;height:200px;overflow:hidden;
  background:#fff;
}
.poly-bottom svg{position:absolute;bottom:0;left:0;width:100%;height:100%}

/* Company info block in footer */
.footer-info{
  position:absolute;left:52px;bottom:48px;z-index:2;
}
.footer-info p{font-size:12px;color:rgba(255,255,255,.85);line-height:1.9}
.footer-info .company-name{font-size:14px;font-weight:700;color:#fff;margin-bottom:2px}

/* Payment status badge */
.footer-badge{
  position:absolute;right:52px;bottom:54px;z-index:2;text-align:right;
}
.pay-pill{
  display:inline-block;padding:5px 18px;border-radius:20px;
  font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
}
.pay-pill.paid{background:#dcfce7;color:#15803d}
.pay-pill.unpaid{background:#fef2f2;color:#dc2626}
.pay-pill.partial{background:#fef9c3;color:#a16207}
.footer-thankyou{color:rgba(255,255,255,.5);font-size:11px;margin-top:8px}

/* ── PRINT ── */
@media print{
  body{background:#fff!important;padding:0!important}
  .screen-toolbar{display:none!important}
  .invoice-paper{box-shadow:none!important;max-width:100%!important;margin:0!important}
  .poly-top svg, .poly-bottom svg{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .items-table thead tr{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .total-band{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
</style>
</head>
<body>

<!-- Screen toolbar (hidden on print) -->
<div class="screen-toolbar">
  <span class="toolbar-title">Invoice <?= e($order['invoice_number']) ?></span>
  <div class="btn-group">
    <a href="<?= url('admin/orders.php') ?>" class="btn btn-back">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      Back
    </a>
    <button onclick="window.print()" class="btn btn-print">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print / Save PDF
    </button>
  </div>
</div>

<div class="invoice-paper">

  <!-- TOP POLYGON DECORATION -->
  <div class="poly-top">
    <svg viewBox="0 0 780 220" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <!-- dark navy shapes top-left -->
      <polygon points="0,0 180,0 120,80 0,60" fill="#0d0d14" opacity="1"/>
      <polygon points="0,0 80,0 0,40" fill="#1a1a2e" opacity="1"/>
      <polygon points="120,80 180,0 260,40 200,110" fill="#0d0d14" opacity="0.85"/>
      <polygon points="0,60 120,80 80,140 0,120" fill="#1a1a2e" opacity="0.7"/>
      <polygon points="200,110 260,40 340,90 280,150" fill="#0d0d14" opacity="0.5"/>
      <!-- gold accent shapes top-left -->
      <polygon points="60,0 120,0 100,30 50,20" fill="#c9a227" opacity="0.9"/>
      <polygon points="140,50 200,20 220,60 160,80" fill="#c9a227" opacity="0.7"/>
      <polygon points="0,80 60,70 40,120 0,110" fill="#c9a227" opacity="0.5"/>
      <!-- dark shapes top-right corner -->
      <polygon points="780,0 620,0 680,70 780,50" fill="#0d0d14" opacity="1"/>
      <polygon points="780,0 720,0 780,30" fill="#1a1a2e" opacity="1"/>
      <polygon points="680,70 620,0 540,50 600,110" fill="#0d0d14" opacity="0.8"/>
      <polygon points="780,50 680,70 720,130 780,110" fill="#1a1a2e" opacity="0.6"/>
      <!-- gold accent top-right -->
      <polygon points="720,0 660,0 680,28 730,18" fill="#c9a227" opacity="0.9"/>
      <polygon points="640,40 580,20 560,60 620,75" fill="#c9a227" opacity="0.7"/>
      <polygon points="780,60 720,55 740,100 780,95" fill="#c9a227" opacity="0.5"/>
    </svg>

    <!-- INVOICE title + meta (positioned over white space in middle) -->
    <div class="inv-title-block">
      <div class="inv-title-word">Invoice</div>
      <table class="inv-meta-table">
        <tr>
          <td>Invoice:</td>
          <td><?= e($order['invoice_number']) ?></td>
        </tr>
        <tr>
          <td>Date:</td>
          <td><?= e($invDate) ?></td>
        </tr>
        <tr>
          <td>Order Ref:</td>
          <td><?= e($order['order_ref']) ?></td>
        </tr>
        <tr>
          <td>Payment:</td>
          <td><?= ucfirst(str_replace('_',' ', $order['payment_method'] ?? 'cash')) ?></td>
        </tr>
      </table>
    </div>
  </div>

  <!-- ITEMS TABLE -->
  <div class="inv-body">
    <table class="items-table">
      <thead>
        <tr>
          <th>Product Description</th>
          <th>Unit Price</th>
          <th>Qty.</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($order['items'] as $item): ?>
        <tr>
          <td>
            <div class="item-name"><?= e($item['name']) ?></div>
            <?php if (!empty($item['sku'])): ?>
            <div class="item-sku">SKU: <?= e($item['sku']) ?></div>
            <?php endif; ?>
          </td>
          <td><?= formatPrice((float)($item['price'] ?? 0), $config) ?></td>
          <td><?= (int)($item['qty'] ?? 1) ?></td>
          <td><?= formatPrice((float)($item['line_total'] ?? 0), $config) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- TOTALS -->
    <div class="totals-area">
      <div class="totals-inner">
        <div class="totals-row">
          <span>Subtotal</span>
          <span><?= formatPrice($subtotal, $config) ?></span>
        </div>
        <?php if ($delivery > 0): ?>
        <div class="totals-row">
          <span>Shipping</span>
          <span><?= formatPrice($delivery, $config) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($tax > 0): ?>
        <div class="totals-row">
          <span>Tax Rate</span>
          <span><?= formatPrice($tax, $config) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($discount > 0): ?>
        <div class="totals-row">
          <span>Discount</span>
          <span class="discount-val">− <?= formatPrice($discount, $config) ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- TOTAL BAND -->
  <div class="total-band">
    <span class="total-band-label">Total</span>
    <span class="total-band-amount"><?= formatPrice($grandTotal, $config) ?></span>
  </div>

  <!-- BOTTOM POLYGON DECORATION -->
  <div class="poly-bottom">
    <svg viewBox="0 0 780 200" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <!-- dark navy shapes bottom-left -->
      <polygon points="0,200 200,200 140,130 0,150" fill="#0d0d14" opacity="1"/>
      <polygon points="0,200 90,200 0,170" fill="#1a1a2e" opacity="1"/>
      <polygon points="140,130 200,200 280,160 220,100" fill="#0d0d14" opacity="0.85"/>
      <polygon points="0,150 140,130 100,80 0,100" fill="#1a1a2e" opacity="0.6"/>
      <!-- gold bottom-left -->
      <polygon points="50,200 120,200 100,170 40,180" fill="#c9a227" opacity="0.9"/>
      <polygon points="150,160 210,185 220,145 160,130" fill="#c9a227" opacity="0.7"/>
      <polygon points="0,130 55,140 40,100 0,105" fill="#c9a227" opacity="0.5"/>
      <!-- dark shapes bottom-right -->
      <polygon points="780,200 580,200 640,130 780,155" fill="#0d0d14" opacity="1"/>
      <polygon points="780,200 690,200 780,170" fill="#1a1a2e" opacity="1"/>
      <polygon points="640,130 580,200 500,160 560,100" fill="#0d0d14" opacity="0.8"/>
      <polygon points="780,155 640,130 680,85 780,110" fill="#1a1a2e" opacity="0.6"/>
      <!-- gold bottom-right -->
      <polygon points="730,200 660,200 680,170 740,180" fill="#c9a227" opacity="0.9"/>
      <polygon points="620,155 560,180 550,140 610,125" fill="#c9a227" opacity="0.7"/>
      <polygon points="780,140 725,150 740,110 780,105" fill="#c9a227" opacity="0.5"/>
    </svg>

    <!-- Company info bottom-left -->
    <div class="footer-info">
      <p class="company-name"><?= e($config['name']) ?></p>
      <p><?= e($config['address']) ?></p>
      <p><?= e($config['phone']) ?></p>
      <p><?= e($config['email']) ?></p>
    </div>

    <!-- Customer + payment status bottom-right -->
    <div class="footer-badge">
      <p style="color:rgba(255,255,255,.6);font-size:11px;margin-bottom:4px">Billed to</p>
      <p style="color:#fff;font-weight:600;font-size:13px"><?= e($order['customer_name'] ?: 'Walk-in Customer') ?></p>
      <?php if ($order['customer_phone']): ?>
      <p style="color:rgba(255,255,255,.6);font-size:12px"><?= e($order['customer_phone']) ?></p>
      <?php endif; ?>
      <div style="margin-top:10px">
        <?php
          $ps = $order['payment_status'] ?? 'unpaid';
          $pc = $ps === 'paid' ? 'paid' : ($ps === 'partial' ? 'partial' : 'unpaid');
        ?>
        <span class="pay-pill <?= $pc ?>"><?= ucfirst($ps) ?></span>
      </div>
      <p class="footer-thankyou">Thank you for your business!</p>
    </div>
  </div>

</div><!-- /invoice-paper -->

</body>
</html>
