<?php
/**
 * Hero carousel (3 slide) — dipakai index.php & beranda.php.
 * Input : $heroSlides (array 3 slide), $heroSize ('lg' | 'sm'), $heroLabel (opsional).
 * Slide : theme (teal|dark|amber), art (shop|ship|seller|cats|track), badge, title, desc, icon (watermark),
 *         ctas [[label, href], ...], meta [[icon|bold, teks], ...] (opsional).
 * Perilaku (autoplay, typing, navigasi) ada di assets/js/script.js; gaya di assets/css/style.css (.mh-hc).
 */
$heroSize = ($heroSize ?? 'lg') === 'sm' ? 'sm' : 'lg';
$heroLabel = $heroLabel ?? 'Promo MarketHub';
$heroTotal = count($heroSlides);
?>
<script>document.documentElement.classList.add('js');</script>
<div class="mh-hc mh-hc-<?= $heroSize ?>" data-hc data-hero data-interval="4800" data-theme="<?= e($heroSlides[0]['theme']) ?>" role="region" aria-roledescription="carousel" aria-label="<?= e($heroLabel) ?>">
  <div class="hc-stage">
  <?php foreach ($heroSlides as $i => $s):
      $tag = $i === 0 ? 'h1' : 'h2';
      $amber = $s['theme'] === 'amber';
      $btnSize = $heroSize === 'lg' ? ' btn-lg' : '';
      $btnClass = $amber ? ['btn-primary', 'btn-outline-dark'] : ['btn-accent', 'btn-ghost-light']; ?>
    <article class="hc-slide hc-<?= e($s['theme']) ?><?= $i === 0 ? ' is-active' : '' ?>" data-theme="<?= e($s['theme']) ?>" role="group" aria-roledescription="slide" aria-label="Slide <?= $i + 1 ?> dari <?= $heroTotal ?>"<?= $i === 0 ? '' : ' aria-hidden="true"' ?>>
      <div class="hc-txt">
        <span class="st hc-badge"><?= e($s['badge']) ?></span>
        <<?= $tag ?> class="hc-h" data-text="<?= e($s['title']) ?>">
          <span class="visually-hidden"><?= e($s['title']) ?></span>
          <span class="ty-ghost" aria-hidden="true"><?= e($s['title']) ?></span>
          <span class="ty-live" aria-hidden="true"><?= e($s['title']) ?></span>
        </<?= $tag ?>>
        <p class="hc-p"><?= e($s['desc']) ?></p>
        <div class="hc-cta">
          <?php foreach ($s['ctas'] as $ci => $c): ?>
            <a class="btn <?= $btnClass[min($ci, 1)] . $btnSize ?>" href="<?= e($c[1]) ?>"><?= e($c[0]) ?></a>
          <?php endforeach; ?>
        </div>
        <?php if (!empty($s['meta'])): ?>
          <ul class="hc-meta">
            <?php foreach ($s['meta'] as $m): ?>
              <li><?php if (strpos($m[0], 'bi-') === 0): ?><i class="bi <?= e($m[0]) ?>"></i><?php else: ?><b><?= e($m[0]) ?></b><?php endif; ?><span><?= e($m[1]) ?></span></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="hc-art hc-art-<?= e($s['art']) ?>" aria-hidden="true">
        <span class="hc-orb hc-orb-1"></span><span class="hc-orb hc-orb-2"></span>
        <?php if ($s['art'] === 'shop'): ?>
          <div class="hc-tiles">
            <span class="hc-tile hc-tile-a" data-depth="14" style="background:#FBE9C8;color:#7A4A06"><i class="bi bi-basket2"></i></span>
            <span class="hc-tile hc-tile-b" data-depth="22" style="background:#D9F2EE;color:#115E59"><i class="bi bi-cup-hot"></i></span>
          </div>
          <span class="hc-chip hc-c1" data-depth="34"><i class="bi bi-truck"></i>Gratis ongkir</span>
          <span class="hc-chip hc-c2 hc-chip-acc" data-depth="40"><i class="bi bi-tag-fill"></i>Promo</span>
          <span class="hc-chip hc-c3" data-depth="18"><i class="bi bi-patch-check-fill"></i>Stok terjamin</span>
          <span class="hc-cartb" data-depth="28"><i class="bi bi-cart3"></i><em>3</em></span>
        <?php elseif ($s['art'] === 'ship'): ?>
          <span class="hc-road"></span>
          <span class="hc-truck" data-depth="16"><i class="bi bi-truck"></i></span>
          <span class="hc-box hc-b1" data-depth="30"><i class="bi bi-box-seam"></i></span>
          <span class="hc-box hc-b2" data-depth="42"><i class="bi bi-box-seam"></i></span>
          <span class="hc-box hc-b3" data-depth="22"><i class="bi bi-gift"></i></span>
          <span class="hc-chip hc-c1" data-depth="34"><i class="bi bi-cash-coin"></i>Ongkir Rp0</span>
          <span class="hc-chip hc-c3" data-depth="20"><i class="bi bi-geo-alt-fill"></i>Pesanan terpantau</span>
        <?php elseif ($s['art'] === 'seller'): ?>
          <span class="hc-store" data-depth="16"><i class="bi bi-shop"></i></span>
          <span class="hc-bars" data-depth="24"><i style="--h:38%"></i><i style="--h:56%"></i><i style="--h:48%"></i><i style="--h:76%"></i><i style="--h:100%"></i></span>
          <span class="hc-chip hc-c1" data-depth="34"><i class="bi bi-box-seam"></i>Stok &amp; pesanan</span>
          <span class="hc-chip hc-c2 hc-chip-dark" data-depth="40"><i class="bi bi-graph-up-arrow"></i>Penjualan naik</span>
          <span class="hc-coin hc-k1" data-depth="30">Rp</span><span class="hc-coin hc-k2" data-depth="44">Rp</span>
        <?php elseif ($s['art'] === 'cats'):
            $catIcons = [['bi-bag-heart', '#FBE9C8', '#7A4A06', '6%', '14%'], ['bi-cup-hot', '#D9F2EE', '#115E59', '38%', '2%'], ['bi-house-heart', '#FADBD3', '#8A2A12', '70%', '16%'],
                         ['bi-gem', '#E1E6F0', '#3B4A66', '10%', '56%'], ['bi-flower1', '#DDEBD0', '#2F5A14', '42%', '50%'], ['bi-basket2', '#E8E1F5', '#4C3A8A', '72%', '58%']];
            foreach ($catIcons as $ci => [$ic, $bg, $fg, $lx, $ty]): ?>
              <span class="hc-cat hc-ct<?= $ci + 1 ?>" data-depth="<?= 14 + $ci * 5 ?>" style="left:<?= $lx ?>;top:<?= $ty ?>;background:<?= $bg ?>;color:<?= $fg ?>"><i class="bi <?= $ic ?>"></i></span>
            <?php endforeach; ?>
          <span class="hc-chip hc-c2 hc-chip-acc" data-depth="38"><i class="bi bi-grid-fill"></i>Beragam kategori</span>
        <?php elseif ($s['art'] === 'track'): ?>
          <span class="hc-line"><i></i></span>
          <?php foreach ([['bi-hourglass-split', 'Pending'], ['bi-gear-fill', 'Diproses'], ['bi-truck', 'Dikirim'], ['bi-check-lg', 'Selesai']] as $ni => [$ic, $lb]): ?>
            <span class="hc-node hc-n<?= $ni + 1 ?>" data-depth="<?= 12 + $ni * 4 ?>"><i class="bi <?= $ic ?>"></i><small><?= $lb ?></small></span>
          <?php endforeach; ?>
          <span class="hc-chip hc-c1" data-depth="34"><i class="bi bi-bell-fill"></i>Status diperbarui</span>
          <span class="hc-chip hc-c3" data-depth="22"><i class="bi bi-shield-check"></i>Stok dicek ulang</span>
        <?php endif; ?>
      </div>
      <i class="hc-wm bi <?= e($s['icon']) ?>" aria-hidden="true"></i>
    </article>
  <?php endforeach; ?>
  </div>

  <button class="hc-nav hc-prev" type="button" aria-label="Slide sebelumnya"><i class="bi bi-chevron-left"></i></button>
  <button class="hc-nav hc-next" type="button" aria-label="Slide berikutnya"><i class="bi bi-chevron-right"></i></button>
  <div class="hc-dots" role="group" aria-label="Pilih slide">
    <?php foreach ($heroSlides as $i => $s): ?>
      <button type="button" class="hc-dot<?= $i === 0 ? ' on' : '' ?>" aria-label="Slide <?= $i + 1 ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>></button>
    <?php endforeach; ?>
  </div>
  <div class="hc-prog" aria-hidden="true"><i></i></div>
</div>