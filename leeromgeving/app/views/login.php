<?php
declare(strict_types=1);

$title = 'Inloggen';
require __DIR__ . '/layout/header.php';
?>

<div class="topbar">
  <div>
    <h1 style="margin:0;">Inloggen</h1>
    <div class="muted">MVP</div>
  </div>
</div>

<section class="card" style="max-width:520px;">
	<?php if (!empty($_SESSION['login_fout'])): ?>
  <div class="card" style="border-color:#fca5a5; margin-bottom:12px;">
    <strong>Let op:</strong> <?= htmlspecialchars($_SESSION['login_fout'], ENT_QUOTES, 'UTF-8') ?>
  </div>
	<?php unset($_SESSION['login_fout']); ?>
	<?php endif; ?>
  <form method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/inloggen">
    <div style="display:flex; flex-direction:column; gap:12px;">
      <div>
        <label for="login" class="muted" style="display:block; margin-bottom:6px;">Leerlingnummer of school e-mail</label>
        <input
          id="login"
          name="login"
          type="text"
          required
          autocomplete="username"
          placeholder="Bijv. b211001 of b211001@school.nl"
          style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px;"
        >
      </div>

      <div>
        <label for="geboortedatum" class="muted" style="display:block; margin-bottom:6px;">Geboortedatum</label>
        <input
          id="geboortedatum"
          name="geboortedatum"
          type="date"
          required
          autocomplete="bday"
          style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px;"
        >
      </div>

      <button class="btn btn-primary" type="submit" style="border:none;">
        Inloggen
      </button>

      <p class="muted" style="margin:0;">
        <!--Later kan dit vervangen worden door Microsoft Single Sign-On.-->
      </p>
    </div>
  </form>
</section>

<?php require __DIR__ . '/layout/footer.php'; ?>
