<?php
session_start();
require_once '../config/db_con.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../auths/login.php');
    exit();
}

$tariffs = $pdo->query("SELECT * FROM tariffs ORDER BY min_unit ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tariff Management</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #eef6fa; }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
    th { background: #0077cc; color: white; }
    .btn { background: #0077cc; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
    .modal-content { background: white; padding: 20px; border-radius: 8px; width: 400px; }
    .modal input { width: 100%; padding: 8px; margin: 5px 0; }
  </style>
</head>
<body>
<a href="./dashst.php" class="back-link">← Back to Dashboard</a>
<h2>Tariff Management</h2>
<button class="btn" onclick="document.getElementById('tariffModal').style.display='flex'">➕ Add Tariff</button>

<table>
  <thead>
    <tr>
      <th>Min Units</th>
      <th>Max Units</th>
      <th>Rate per Unit (KES)</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($tariffs as $t): ?>
    <tr>
      <td><?= $t['min_unit'] ?></td>
      <td><?= is_null($t['max_unit']) ? '∞' : $t['max_unit'] ?></td>
      <td><?= number_format($t['rate_per_unit'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Modal -->
<div id="tariffModal" class="modal">
  <div class="modal-content">
    <h3>Add New Tariff</h3>
    <form id="tariffForm">
      <label>Min Unit</label>
      <input type="number" name="min_unit" required>

      <label>Max Unit (leave blank for ∞)</label>
      <input type="number" name="max_unit">

      <label>Rate per Unit</label>
      <input type="number" step="0.01" name="rate_per_unit" required>

      <button type="submit" class="btn">Save</button>
      <button type="button" class="btn" onclick="document.getElementById('tariffModal').style.display='none'">Cancel</button>
    </form>
  </div>
</div>

<script>
document.getElementById("tariffForm").addEventListener("submit", function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch("add_tariff_ajax.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.text())
  .then(response => {
    alert(response);
    window.location.reload();
  });
});
</script>

</body>
</html>
