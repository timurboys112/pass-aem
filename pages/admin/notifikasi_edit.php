<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
checkRole(['admin']);

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: notifikasi_template.php');
    exit;
}

$error = '';
// Ambil data lama
$stmt = $conn->prepare("SELECT * FROM notifikasi_template WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$template = $result->fetch_assoc();
$stmt->close();

if (!$template) {
    header('Location: notifikasi_template.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $status = $_POST['status'] ?? 'Aktif';

    if ($nama === '' || $isi === '') {
        $error = 'Nama dan Isi wajib diisi.';
    } else {
        $stmt = $conn->prepare("UPDATE notifikasi_template SET nama = ?, isi = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nama, $isi, $status, $id);
        if ($stmt->execute()) {
            header('Location: notifikasi_template.php');
            exit;
        } else {
            $error = 'Gagal mengupdate data.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Edit Template Notifikasi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="p-4">
  <h4>Edit Template Notifikasi</h4>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label for="nama" class="form-label">Nama Template</label>
      <input type="text" name="nama" id="nama" class="form-control" required value="<?= htmlspecialchars($_POST['nama'] ?? $template['nama']) ?>" />
    </div>
    <div class="mb-3">
      <label for="isi" class="form-label">Isi Notifikasi</label>
      <textarea name="isi" id="isi" class="form-control" required rows="3"><?= htmlspecialchars($_POST['isi'] ?? $template['isi']) ?></textarea>
    </div>
    <div class="mb-3">
      <label for="status" class="form-label">Status</label>
      <select name="status" id="status" class="form-select">
        <option value="Aktif" <?= ((($_POST['status'] ?? $template['status']) === 'Aktif') ? 'selected' : '') ?>>Aktif</option>
        <option value="Nonaktif" <?= ((($_POST['status'] ?? $template['status']) === 'Nonaktif') ? 'selected' : '') ?>>Nonaktif</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="notifikasi_template.php" class="btn btn-secondary">Batal</a>
  </form>
</body>
</html>