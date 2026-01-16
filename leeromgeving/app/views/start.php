<?php
declare(strict_types=1);

$title = 'Start';
require __DIR__ . '/layout/header.php';
?>

<div class="topbar">
  <div>
    <h1 style="margin:0;">Start</h1>
    <div class="muted">
      Welkom <?= htmlspecialchars($_SESSION['volledige_naam'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </div>
  </div>
  <a class="btn btn-secondary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/uitloggen">
    Uitloggen
  </a>
</div>

<div class="row">
  <section class="card">
    <h2 style="margin:0 0 8px;">Jouw gegevens</h2>

    <div class="muted">Leerlinggroep</div>
    <div style="margin-bottom:12px;">
      <strong><?= htmlspecialchars($leerlinggroep['leerlinggroep_naam'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
    </div>

    <div class="muted">Klas / periode</div>
    <div>
      <strong>
        <?= htmlspecialchars(($leerlinggroep['klas_naam'] ?? '-') . ' • ' . ($leerlinggroep['periode_naam'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
      </strong>
    </div>
  </section>

  <section class="card">
    <h2 style="margin:0 0 8px;">Actueel lesmoment</h2>

    <?php if (empty($lesmoment)): ?>
      <p class="muted" style="margin:0;">
        Er staat nu geen lesmoment open.
      </p>
    <?php else: ?>
      <p style="margin:0 0 6px;">
        <strong><?= htmlspecialchars($lesmoment['onderwerp'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
      </p>

      <div class="muted">
        Week <?= (int)($lesmoment['weeknummer'] ?? 0) ?>
        • dag <?= (int)($lesmoment['dag_van_week'] ?? 0) ?>
        • uur <?= (int)($lesmoment['lesuur'] ?? 0) ?>
      </div>

      <div style="margin-top:12px;">
        <div class="muted">Presenterende groep</div>
        <div>
          <strong><?= htmlspecialchars($lesmoment['presenterende_groep_naam'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
      </div>

      <?php if (!empty($magBeoordelen)): ?>
        <div style="margin-top:16px;">
          <a class="btn btn-primary"
             href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/beoordelen?lesmoment_id=<?= (int)($lesmoment['lesmoment_id'] ?? 0) ?>">
            Start beoordeling
          </a>
        </div>
      <?php else: ?>
        <p class="muted" style="margin-top:12px; margin-bottom:0;">
          Je kunt dit lesmoment niet beoordelen (bijvoorbeeld omdat je groep zelf presenteert).
        </p>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>
	<?php if (!empty($_SESSION['login_fout'])): ?>
  <div class="card" style="border-color:#fca5a5; margin-top:16px;">
    <strong>Let op:</strong> <?= htmlspecialchars($_SESSION['login_fout'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php unset($_SESSION['login_fout']); ?>
	<?php endif; ?>
<?php require __DIR__ . '/layout/footer.php'; ?>
