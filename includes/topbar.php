<?php
/** Topbar dashboard. Variabel: $topTitle, $topCrumb. */
$u = current_user();
?>
<div class="mh-tb">
  <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mhSidebar" aria-controls="mhSidebar" aria-label="Buka menu">
    <i class="bi bi-list fs-5"></i>
  </button>
  <div>
    <h1 class="mh-tb-title"><?= e($topTitle ?? 'Dashboard') ?></h1>
    <div class="mh-tb-crumb"><?= e($topCrumb ?? tanggal_panjang()) ?></div>
  </div>
  <div class="ms-auto d-flex align-items-center gap-3">
    <span class="mh-me"><span class="mh-av mh-av-dark"><?= e(initials($u['nama'])) ?></span>
      <span class="d-none d-sm-block lh-sm"><?= e($u['nama']) ?><small class="d-block text-muted fw-normal"><?= e(role_label($u['role'])) ?></small></span></span>
  </div>
</div>
