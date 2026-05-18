<?php
require_once 'includes/functions.php';
requireLogin();
require_once 'includes/db.php';
$conn = getConnection();
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Stats
$totalMeds = $conn->query("SELECT COUNT(*) as c FROM medicines WHERE status='active'")->fetch_assoc()['c'];
$totalStock = $conn->query("SELECT SUM(stock_quantity) as c FROM medicines WHERE status='active'")->fetch_assoc()['c'] ?? 0;
$todaySales = $conn->query("SELECT COALESCE(SUM(total_amount-discount),0) as c FROM sales WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'];
$monthlySales = $conn->query("SELECT COALESCE(SUM(total_amount-discount),0) as c FROM sales WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetch_assoc()['c'];
$expiredCount = $conn->query("SELECT COUNT(*) as c FROM medicines WHERE expiry_date < CURDATE() AND status='active' AND stock_quantity>0")->fetch_assoc()['c'];
$expiringSoon = $conn->query("SELECT COUNT(*) as c FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND status='active'")->fetch_assoc()['c'];
$lowStock = $conn->query("SELECT COUNT(*) as c FROM medicines WHERE stock_quantity <= min_stock_level AND status='active'")->fetch_assoc()['c'];

// Recent sales
$recentSales = $conn->query("SELECT * FROM sales ORDER BY created_at DESC LIMIT 8");

// Expiring medicines
$expiringMeds = $conn->query("SELECT m.*, c.name as cat_name, DATEDIFF(m.expiry_date, CURDATE()) as days_left FROM medicines m LEFT JOIN categories c ON m.category_id=c.id WHERE m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND m.status='active' AND m.stock_quantity>0 ORDER BY m.expiry_date ASC LIMIT 8");

// Monthly sales chart data (last 7 days)
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $res = $conn->query("SELECT COALESCE(SUM(total_amount-discount),0) as total FROM sales WHERE DATE(created_at)='$date'")->fetch_assoc();
    $chartData[] = ['date' => date('d M', strtotime($date)), 'total' => (float)$res['total']];
}

include 'includes/header.php';
?>
<div class="stat-grid">
  <div class="stat-card green">
    <div class="stat-label">Total Medicines</div>
    <div class="stat-value green"><?= $totalMeds ?></div>
    <div class="stat-sub">Active products</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Total Stock Units</div>
    <div class="stat-value blue"><?= number_format($totalStock) ?></div>
    <div class="stat-sub">Items in inventory</div>
  </div>
  <div class="stat-card green">
    <div class="stat-label">Today's Revenue</div>
    <div class="stat-value green" style="font-size:24px"><?= formatCurrency($todaySales) ?></div>
    <div class="stat-sub">Sales today</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Monthly Revenue</div>
    <div class="stat-value blue" style="font-size:24px"><?= formatCurrency($monthlySales) ?></div>
    <div class="stat-sub">This month</div>
  </div>
  <div class="stat-card red">
    <div class="stat-label">Expired Medicines</div>
    <div class="stat-value red"><?= $expiredCount ?></div>
    <div class="stat-sub">Needs immediate action</div>
  </div>
  <div class="stat-card yellow">
    <div class="stat-label">Expiring ≤90 Days</div>
    <div class="stat-value yellow"><?= $expiringSoon ?></div>
    <div class="stat-sub">Monitor closely</div>
  </div>
  <div class="stat-card yellow">
    <div class="stat-label">Low Stock Items</div>
    <div class="stat-value yellow"><?= $lowStock ?></div>
    <div class="stat-sub">Below minimum level</div>
  </div>
</div>

<!-- Quick Actions -->
<div style="display:flex;gap:12px;margin-bottom:28px;flex-wrap:wrap;">
  <a href="pages/new_sale.php" class="btn btn-primary">🛒 New Sale</a>
  <a href="pages/medicines.php" class="btn btn-outline">➕ Add Medicine</a>
  <a href="pages/expiry.php" class="btn btn-outline">⚠️ Check Expiry</a>
  <a href="pages/reports.php" class="btn btn-outline">📊 View Reports</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
  <!-- Sales Chart -->
  <div class="card">
    <h3 style="font-size:16px;font-weight:700;margin-bottom:20px;">📈 Last 7 Days Sales</h3>
    <canvas id="salesChart" height="180"></canvas>
  </div>
  <!-- Expiring Soon -->
  <div class="card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <h3 style="font-size:16px;font-weight:700;">⚠️ Expiring Soon</h3>
      <a href="pages/expiry.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Medicine</th><th>Expiry</th><th>Status</th></tr></thead>
      <tbody>
      <?php while($row = $expiringMeds->fetch_assoc()): 
        $days = $row['days_left'];
        $cls = $days < 0 ? 'badge-danger' : ($days <= 30 ? 'badge-danger' : 'badge-warning');
        $label = $days < 0 ? 'Expired' : ($days == 0 ? 'Today!' : "$days days");
      ?>
        <tr>
          <td><strong><?= htmlspecialchars($row['name']) ?></strong><br><span style="color:var(--muted);font-size:12px;"><?= $row['cat_name']??'—' ?></span></td>
          <td style="font-family:'Space Mono',monospace;font-size:13px;"><?= $row['expiry_date'] ?></td>
          <td><span class="badge <?= $cls ?>"><?= $label ?></span></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- Recent Sales -->
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
    <h3 style="font-size:16px;font-weight:700;">🧾 Recent Sales</h3>
    <a href="pages/sales_history.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Invoice</th><th>Customer</th><th>Amount</th><th>Payment</th><th>Date</th></tr></thead>
    <tbody>
    <?php while($row = $recentSales->fetch_assoc()): 
      $net = $row['total_amount'] - $row['discount'];
    ?>
      <tr>
        <td><span style="font-family:'Space Mono',monospace;color:var(--accent);font-size:13px;"><?= $row['invoice_number'] ?></span></td>
        <td><?= htmlspecialchars($row['customer_name'] ?: 'Walk-in Customer') ?></td>
        <td style="font-weight:700;"><?= formatCurrency($net) ?></td>
        <td><span class="badge badge-info"><?= ucfirst($row['payment_method']) ?></span></td>
        <td style="color:var(--muted);font-size:13px;"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const chartData = <?= json_encode($chartData) ?>;
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: chartData.map(d => d.date),
    datasets: [{
      label: 'Revenue (৳)',
      data: chartData.map(d => d.total),
      backgroundColor: 'rgba(0,212,170,0.2)',
      borderColor: '#00d4aa',
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#5a6a88', font: { size: 11 } } },
      y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#5a6a88', font: { size: 11 } } }
    }
  }
});
</script>
<?php include 'includes/footer.php'; $conn->close(); ?>
