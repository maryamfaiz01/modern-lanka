<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
requireAdmin();

// Date range handling
$range = $_GET['range'] ?? 'month';
$allowedRanges = ['today', 'week', 'month', 'year', 'custom'];
if (!in_array($range, $allowedRanges, true)) {
    $range = 'month';
}

$now = new DateTime();
$dateFrom = null;
$dateTo = null;

switch ($range) {
    case 'today':
        $dateFrom = $now->format('Y-m-d 00:00:00');
        $dateTo = $now->format('Y-m-d 23:59:59');
        break;
    case 'week':
        $dateFrom = (clone $now)->modify('monday this week')->format('Y-m-d 00:00:00');
        $dateTo = (clone $now)->modify('sunday this week')->format('Y-m-d 23:59:59');
        break;
    case 'month':
        $dateFrom = (clone $now)->modify('first day of this month')->format('Y-m-d 00:00:00');
        $dateTo = (clone $now)->modify('last day of this month')->format('Y-m-d 23:59:59');
        break;
    case 'year':
        $dateFrom = (clone $now)->modify('first day of january this year')->format('Y-m-d 00:00:00');
        $dateTo = (clone $now)->modify('last day of december this year')->format('Y-m-d 23:59:59');
        break;
    case 'custom':
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        try {
            if ($dateFrom) {
                $dateFrom = (new DateTime($dateFrom))->format('Y-m-d 00:00:00');
            }
            if ($dateTo) {
                $dateTo = (new DateTime($dateTo))->format('Y-m-d 23:59:59');
            }
        } catch (Exception $e) {
            $dateFrom = null;
            $dateTo = null;
        }
        if (!$dateFrom || !$dateTo) {
            $range = 'month';
            $dateFrom = (clone $now)->modify('first day of this month')->format('Y-m-d 00:00:00');
            $dateTo = (clone $now)->modify('last day of this month')->format('Y-m-d 23:59:59');
        }
        break;
}

// Stats queries
$stmt = $pdo->prepare(
    "SELECT 
        COALESCE(SUM(CASE WHEN status != 'cancelled' THEN subtotal ELSE 0 END), 0) as total_revenue,
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        COALESCE(AVG(CASE WHEN status != 'cancelled' THEN subtotal ELSE NULL END), 0) as avg_order_value,
        COUNT(DISTINCT customer_phone) as unique_customers
     FROM orders 
     WHERE created_at BETWEEN ? AND ?"
);
$stmt->execute([$dateFrom, $dateTo]);
$stats = $stmt->fetch();

// Orders by status
$stmt = $pdo->prepare(
    "SELECT status, COUNT(*) as count 
     FROM orders 
     WHERE created_at BETWEEN ? AND ? 
     GROUP BY status"
);
$stmt->execute([$dateFrom, $dateTo]);
$statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Daily revenue for sparkline
$stmt = $pdo->prepare(
    "SELECT DATE(created_at) as date, SUM(CASE WHEN status != 'cancelled' THEN subtotal ELSE 0 END) as revenue
     FROM orders 
     WHERE created_at BETWEEN ? AND ? 
     GROUP BY DATE(created_at) 
     ORDER BY date ASC"
);
$stmt->execute([$dateFrom, $dateTo]);
$dailyRevenue = $stmt->fetchAll();

// Fetch all orders for aggregation
$stmt = $pdo->prepare(
    "SELECT items_json FROM orders WHERE created_at BETWEEN ? AND ?"
);
$stmt->execute([$dateFrom, $dateTo]);
$orders = $stmt->fetchAll();

// Aggregate top products
$productStats = [];
foreach ($orders as $order) {
    $items = json_decode($order['items_json'], true) ?: [];
    foreach ($items as $item) {
        $name = $item['name'] ?? 'Unknown';
        $qty = (int)($item['qty'] ?? 0);
        $price = (float)($item['price'] ?? 0);
        $lineTotal = (float)($item['line_total'] ?? ($qty * $price));
        
        if (!isset($productStats[$name])) {
            $productStats[$name] = ['qty' => 0, 'revenue' => 0];
        }
        $productStats[$name]['qty'] += $qty;
        $productStats[$name]['revenue'] += $lineTotal;
    }
}

// Sort by revenue and get top 8
uasort($productStats, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
$topProducts = array_slice($productStats, 0, 8, true);

// Aggregate revenue by category
$categoryRevenue = [];
foreach ($orders as $order) {
    $items = json_decode($order['items_json'], true) ?: [];
    foreach ($items as $item) {
        $productId = (int)($item['id'] ?? 0);
        if ($productId > 0) {
            $stmt = $pdo->prepare('SELECT category FROM products WHERE id = ?');
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            if ($product) {
                $category = $product['category'] ?? 'Other';
                $lineTotal = (float)($item['line_total'] ?? 0);
                if (!isset($categoryRevenue[$category])) {
                    $categoryRevenue[$category] = 0;
                }
                $categoryRevenue[$category] += $lineTotal;
            }
        }
    }
}

arsort($categoryRevenue);

$adminTitle = 'Sales Reports';
require __DIR__ . '/includes/header.php';
?>

<style>
.date-range-custom {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-top: 12px;
}
.date-range-custom input[type="date"] {
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--glass-border);
    border-radius: 8px;
    padding: 8px 12px;
    color: var(--text-primary);
    font-family: var(--font-body);
}
.date-range-custom button {
    padding: 8px 16px;
}
.chart-container {
    position: relative;
    height: 300px;
}
.chart-container--donut {
    position: relative;
    height: 280px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.chart-container--category {
    position: relative;
    min-height: auto;
    padding: 10px 0 28px 0;
}
.export-row {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 16px;
}
.category-bar-colors {
    --cat-1: #c9a227;
    --cat-2: #4a9eff;
    --cat-3: #34d399;
    --cat-4: #f87171;
    --cat-5: #a78bfa;
}
.donut-center-label {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    pointer-events: none;
}
.donut-center-value {
    font-family: var(--font-display);
    font-size: 2rem;
    font-weight: 700;
    color: var(--gold);
    line-height: 1;
}
.donut-center-text {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
</style>

<div class="admin-page-header">
    <div>
        <h1>Sales Reports</h1>
        <p>Analytics for <?= ucfirst($range) ?></p>
    </div>
</div>

<!-- Date Range Selector -->
<div class="filter-tabs reports-section">
    <a href="?range=today" class="filter-tab <?= $range === 'today' ? 'active' : '' ?>">Today</a>
    <a href="?range=week" class="filter-tab <?= $range === 'week' ? 'active' : '' ?>">This Week</a>
    <a href="?range=month" class="filter-tab <?= $range === 'month' ? 'active' : '' ?>">This Month</a>
    <a href="?range=year" class="filter-tab <?= $range === 'year' ? 'active' : '' ?>">This Year</a>
    <a href="?range=custom" class="filter-tab <?= $range === 'custom' ? 'active' : '' ?>">Custom</a>
</div>

<?php if ($range === 'custom'): ?>
<form class="date-range-custom" method="get">
    <input type="hidden" name="range" value="custom">
    <input type="date" name="date_from" value="<?= $_GET['date_from'] ?? '' ?>">
    <span>to</span>
    <input type="date" name="date_to" value="<?= $_GET['date_to'] ?? '' ?>">
    <button type="submit" class="btn btn--primary btn--sm">Go</button>
</form>
<?php endif; ?>

<!-- KPI Cards -->
<div class="stats-grid">
    <div class="stat-card glass-card">
        <div class="stat-card__label">Total Revenue</div>
        <div class="stat-card__value" style="color: var(--gold)"><?= formatPrice((float)$stats['total_revenue'], $config) ?></div>
    </div>
    <div class="stat-card glass-card">
        <div class="stat-card__label">Total Orders</div>
        <div class="stat-card__value"><?= (int)$stats['total_orders'] ?></div>
    </div>
    <div class="stat-card glass-card">
        <div class="stat-card__label">Avg Order Value</div>
        <div class="stat-card__value"><?= formatPrice((float)$stats['avg_order_value'], $config) ?></div>
    </div>
    <div class="stat-card glass-card">
        <div class="stat-card__label">Unique Customers</div>
        <div class="stat-card__value"><?= (int)$stats['unique_customers'] ?></div>
    </div>
</div>

<!-- Charts Grid -->
<div class="admin-grid-2">
    <!-- Revenue Over Time -->
    <div class="glass-card reports-card">
        <h3>Revenue Over Time</h3>
        <div class="chart-container">
            <?php if (empty($dailyRevenue)): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 100px 0;">No data for this period</p>
            <?php elseif (count($dailyRevenue) === 1): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 100px 0;">Not enough data yet for a trend line</p>
            <?php else: ?>
            <canvas id="revenueChart"></canvas>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Orders by Status -->
    <div class="glass-card reports-card">
        <h3>Orders by Status</h3>
        <div class="chart-container--donut">
            <canvas id="statusChart"></canvas>
            <div class="donut-center-label">
                <div class="donut-center-value"><?= (int)$stats['total_orders'] ?></div>
                <div class="donut-center-text">Total</div>
            </div>
        </div>
    </div>
</div>

<!-- Top Products -->
<div class="glass-card reports-card">
    <div class="export-row">
        <h3>Top Products</h3>
        <button type="button" class="btn btn--outline btn--sm" onclick="exportCSV()">Export CSV</button>
    </div>
    <?php if (empty($topProducts)): ?>
    <p style="text-align: center; color: var(--text-muted); padding: 40px 0;">No product data for this period</p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Product Name</th>
                <th>Units Sold</th>
                <th>Revenue</th>
                <th>% of Total Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $rank = 1;
            $totalRevenue = (float)$stats['total_revenue'];
            foreach ($topProducts as $name => $data): 
                $percent = $totalRevenue > 0 ? round(($data['revenue'] / $totalRevenue) * 100, 1) : 0;
            ?>
            <tr>
                <td><?= $rank++ ?></td>
                <td><?= e($name) ?></td>
                <td><?= $data['qty'] ?></td>
                <td><?= formatPrice($data['revenue'], $config) ?></td>
                <td><?= $percent ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Revenue by Category -->
<div class="glass-card reports-card category-bar-colors">
    <h3>Revenue by Category</h3>
    <div class="chart-container--category">
        <?php if (empty($categoryRevenue)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 100px 0;">No category data for this period</p>
        <?php else: ?>
        <canvas id="categoryChart"></canvas>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// Daily Revenue Data
const dailyRevenueData = <?= json_encode($dailyRevenue) ?>;
const revenueLabels = dailyRevenueData.map(d => d.date);
const revenueValues = dailyRevenueData.map(d => d.revenue);

if (revenueLabels.length > 0) {
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Revenue',
                data: revenueValues,
                borderColor: '#c9a227',
                backgroundColor: 'rgba(201,162,39,0.12)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            color: '#9ca3af',
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.06)' },
                    ticks: { color: '#9ca3af' }
                },
                y: {
                    grid: { color: 'rgba(255,255,255,0.06)' },
                    ticks: { color: '#9ca3af' }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

// Orders by Status
const statusData = <?= json_encode($statusCounts) ?>;
const statusLabels = Object.keys(statusData);
const statusValues = Object.values(statusData);
const statusColors = ['#c9a227', '#4a9eff', '#34d399', '#f87171', '#a78bfa'];

if (statusLabels.length > 0) {
    const totalOrders = statusValues.reduce((a, b) => a + b, 0);
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
            datasets: [{
                data: statusValues,
                backgroundColor: statusColors.slice(0, statusLabels.length),
                borderWidth: 0,
                cutout: '65%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            color: '#9ca3af',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        color: '#9ca3af',
                        padding: 12,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            }
        }
    });
}

// Revenue by Category
const categoryData = <?= json_encode($categoryRevenue) ?>;
const categoryLabels = Object.keys(categoryData);
const categoryValues = Object.values(categoryData);

if (categoryLabels.length > 0) {
    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: categoryLabels,
            datasets: [{
                label: 'Revenue',
                data: categoryValues,
                backgroundColor: '#c9a227',
                borderRadius: 6,
                barThickness: 24
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            color: '#9ca3af',
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.06)' },
                    ticks: { color: '#9ca3af' }
                },
                y: {
                    grid: { display: false },
                    ticks: { color: '#9ca3af' },
                    categoryPercentage: 0.7,
                    barPercentage: 0.5
                }
            },
            plugins: {
                legend: { display: false }
            },
            layout: {
                padding: {
                    top: 10,
                    bottom: 10,
                    left: 10,
                    right: 10
                }
            }
        }
    });
}

// CSV Export
function exportCSV() {
    const topProducts = <?= json_encode($topProducts) ?>;
    const totalRevenue = <?= (float)$stats['total_revenue'] ?>;
    
    let csv = 'Rank,Product Name,Units Sold,Revenue,% of Total Revenue\n';
    let rank = 1;
    
    for (const [name, data] of Object.entries(topProducts)) {
        const percent = totalRevenue > 0 ? ((data.revenue / totalRevenue) * 100).toFixed(1) : 0;
        csv += `${rank},"${name}",${data.qty},${data.revenue.toFixed(2)},${percent}%\n`;
        rank++;
    }
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'top-products-export.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
