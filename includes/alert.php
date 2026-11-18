<?php
/** Notifikasi sukses / gagal (flash message). Variabel dibungkus closure agar tidak menimpa variabel halaman. */
(function () {
    $icons = ['success' => 'bi-check-circle-fill', 'danger' => 'bi-exclamation-octagon-fill', 'warning' => 'bi-exclamation-triangle-fill', 'info' => 'bi-info-circle-fill'];
    foreach (flash_pull() as $fl):
        $tp = isset($icons[$fl['type']]) ? $fl['type'] : 'info'; ?>
<div class="alert alert-<?= $tp ?> alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
  <i class="bi <?= $icons[$tp] ?> mt-1"></i><div><?= e($fl['msg']) ?></div>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
<?php endforeach;
})();
