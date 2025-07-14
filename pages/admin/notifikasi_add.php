<?php
require_once '../../includes/auth.php';
require_once '../../config/db.php';
checkRole(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $status = $_POST['status'] ?? 'Aktif';

    if ($nama === '' || $isi === '') {
        $error = 'Nama dan Isi wajib diisi.';
    } else {
        // Insert ke database
        $stmt = $conn->prepare("INSERT INTO notifikasi_template (nama, isi, status) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama, $isi, $status);
        if ($stmt->execute()) {
            header('Location: notifikasi_template.php');
            exit;
        } else {
            $error = 'Gagal menyimpan data.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Tambah Template Notifikasi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="p-4">
  <h4>Tambah Template Notifikasi</h4>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label for="nama" class="form-label">Nama Template</label>
      <input type="text" name="nama" id="nama" class="form-control" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" />
    </div>
    <div class="mb-3">
      <label for="isi" class="form-label">Isi Notifikasi</label>
      <textarea name="isi" id="isi" class="form-control" required rows="3"><?= htmlspecialchars($_POST['isi'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
      <label for="status" class="form-label">Status</label>
      <select name="status" id="status" class="form-select">
        <option value="Aktif" <?= (($_POST['status'] ?? '') === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
        <option value="Nonaktif" <?= (($_POST['status'] ?? '') === 'Nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="notifikasi_template.php" class="btn btn-secondary">Batal</a>
  </form>
</body>
</html>