<?php
declare(strict_types=1);

$title = 'Beoordelen';
require __DIR__ . '/layout/header.php';
?>

<div class="topbar">
  <div>
    <h1 style="margin:0;">Beoordelen</h1>
    <div class="muted">
      Lesmoment: <?= htmlspecialchars($lesmoment['onderwerp'] ?? '', ENT_QUOTES, 'UTF-8') ?>
      • Presenterend: <?= htmlspecialchars($lesmoment['presenterende_groep_naam'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </div>
  </div>
  <a class="btn btn-secondary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/start">Terug</a>
</div>

<?php if (!empty($_SESSION['login_fout'])): ?>
  <div class="card" style="border-color:#fca5a5; margin-bottom:16px;">
    <strong>Let op:</strong> <?= htmlspecialchars($_SESSION['login_fout'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php unset($_SESSION['login_fout']); ?>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/beoordelen">
  <input type="hidden" name="lesmoment_id" value="<?= (int)($lesmoment['lesmoment_id'] ?? 0) ?>">

  <div class="row">
    <section class="card">
      <h2 style="margin:0 0 12px;">Rubriek</h2>

      <?php foreach ($rubriekOnderdelen as $o): ?>
        <?php $oid = (int)$o['rubriek_onderdeel_id']; ?>
        <div style="padding:12px 0; border-bottom:1px solid var(--border);">
          <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
              <strong><?= htmlspecialchars($o['titel'], ENT_QUOTES, 'UTF-8') ?></strong>
              <?php if (!empty($o['uitleg_kort'])): ?>
                <div class="muted"><?= htmlspecialchars($o['uitleg_kort'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>
            <div class="muted">max <?= (int)$o['max_punten'] ?> pt</div>
          </div>

          <!--<div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">-->
					<div class="rubriek-opties" style="margin-top:10px;">
            <?php foreach (['matig','voldoende','goed','uitstekend'] as $niv): ?>
              <label class="btn btn-secondary rubriek-optie" style="cursor:pointer;">
                <input
                  type="radio"
                  name="niveau[<?= $oid ?>]"
                  value="<?= $niv ?>"
                  required
                  style="margin-right:8px;"
                >
                <?= htmlspecialchars($niv, ENT_QUOTES, 'UTF-8') ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

    </section>

    <section class="card">
      <h2 style="margin:0 0 12px;">Feedback</h2>

      <label class="muted" for="tops" style="display:block; margin-bottom:6px;">Top</label>
      <textarea id="tops" name="tops" rows="4" required
        style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px;"></textarea>

      <div style="height:12px;"></div>

      <label class="muted" for="tips" style="display:block; margin-bottom:6px;">Tip (feedforward)</label>
      <textarea id="tips" name="tips" rows="4" required
        style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px;"></textarea>

      <div style="height:12px;"></div>

      <label class="muted" for="vraag" style="display:block; margin-bottom:6px;">Vraag (optioneel)</label>
      <textarea id="vraag" name="vraag" rows="3"
        style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px;"></textarea>

      <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn btn-primary" type="submit" style="border:none;">
          Verzenden
        </button>
        <a class="btn btn-secondary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/start">
          Annuleren
        </a>
      </div>

      <p class="muted" style="margin-top:12px;">
        Je inzending wordt pas zichtbaar voor de presenterende groep nadat de docent heeft gepubliceerd.
      </p>
    </section>
  </div>
</form>

<?php require __DIR__ . '/layout/footer.php'; ?>
