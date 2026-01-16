<?php
declare(strict_types=1);

session_start();

/**
 * Basisinstellingen
 */
$basePath = '/leeromgeving';

/**
 * URL pad bepalen (zonder querystring)
 * Voorbeeld: /leeromgeving/inloggen  ->  /inloggen
 */
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
if (str_starts_with($uriPath, $basePath)) {
    $uriPath = substr($uriPath, strlen($basePath));
}
$path = rtrim($uriPath, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/**
 * Mini-router: map URL → view
 */
switch (true) {
    // Home -> door naar inloggen (MVP)
    case $path === '/' && $method === 'GET':
        header('Location: ' . $basePath . '/inloggen');
        exit;

    // Inloggen (alleen view tonen, nog geen DB)
    case $path === '/inloggen' && $method === 'GET':
        require __DIR__ . '/../app/views/login.php';
        exit;
				
		case $path === '/inloggen' && $method === 'POST':
    require __DIR__ . '/../app/core/db.php';

		$login = trim($_POST['login'] ?? '');
		$geboortedatum = trim($_POST['geboortedatum'] ?? '');

		// Basiscontrole
		if ($login === '' || $geboortedatum === '') {
			$_SESSION['login_fout'] = 'Vul alle velden in.';
			header('Location: ' . $basePath . '/inloggen');
			exit;
		}

		// Strikte datumcontrole
		$dt = DateTime::createFromFormat('Y-m-d', $geboortedatum);
		$errors = DateTime::getLastErrors();
		if ($errors === false) {
			$errors = ['warning_count' => 0, 'error_count' => 0];
		}

		if (!$dt || $errors['warning_count'] > 0 || $errors['error_count'] > 0) {
    $_SESSION['login_fout'] = 'Ongeldige geboortedatum. Gebruik het formaat DD-MM-JJJJ.';
    header('Location: ' . $basePath . '/inloggen');
    exit;
		}

		// Extra check op jaar (YYYY)
		$year = (int)$dt->format('Y');
		$currentYear = (int)date('Y');

		if ($year < 1990 || $year > $currentYear) {
			$_SESSION['login_fout'] = 'Ongeldig jaar in geboortedatum (JJJJ).';
			header('Location: ' . $basePath . '/inloggen');
			exit;
		}

    $sql = "
      SELECT g.gebruiker_id, g.rol_id, g.actief,
             CONCAT_WS(' ', g.voornaam, g.tussenvoegsel, g.achternaam) AS volledige_naam
      FROM gebruiker g
      WHERE g.actief = 1
        AND g.geboortedatum = :geboortedatum
        AND (g.leerlingnummer = :login OR g.e_mail = :login)
      LIMIT 1
    ";

    $stmt = db()->prepare($sql);
    $stmt->execute([
      ':geboortedatum' => $geboortedatum,
      ':login' => $login,
    ]);

    $user = $stmt->fetch();

    if (!$user) {
			$_SESSION['login_fout'] = 'Onjuiste combinatie van leerlingnummer/e-mail en geboortedatum.';
			header('Location: ' . $basePath . '/inloggen');
      exit;
    }

    // Sessiestatus
    $_SESSION['gebruiker_id'] = (int)$user['gebruiker_id'];
    $_SESSION['rol_id'] = (int)$user['rol_id'];
    $_SESSION['volledige_naam'] = $user['volledige_naam'];

    header('Location: ' . $basePath . '/start');
    exit;
		// case start
		case $path === '/start' && $method === 'GET':
    if (!isset($_SESSION['gebruiker_id'])) {
        header('Location: ' . $basePath . '/inloggen');
        exit;
    }
    require __DIR__ . '/../app/core/db.php';

    // Query 2: leerling → leerlinggroep/klas/periode
    $sql2 = "
      SELECT
        lg.leerlinggroep_id,
        lg.naam AS leerlinggroep_naam,
        k.klas_id,
        k.naam AS klas_naam,
        p.periode_id,
        p.naam AS periode_naam
      FROM leerlinggroep_lid lgl
      JOIN leerlinggroep lg ON lg.leerlinggroep_id = lgl.leerlinggroep_id
      JOIN klas k          ON k.klas_id = lg.klas_id
      JOIN periode p       ON p.periode_id = k.periode_id
      WHERE lgl.gebruiker_id = :gebruiker_id
        AND p.actief = 1
      LIMIT 1
    ";
    $stmt2 = db()->prepare($sql2);
    $stmt2->execute([':gebruiker_id' => (int)$_SESSION['gebruiker_id']]);
    $leerlinggroep = $stmt2->fetch();

    if (!$leerlinggroep) {
        http_response_code(403);
        echo 'Geen leerlinggroep gevonden (of geen actieve periode).';
        exit;
    }

    // Query 3: actueel lesmoment
    $sql3 = "
      SELECT
        lm.lesmoment_id,
        lm.weeknummer,
        lm.dag_van_week,
        lm.lesuur,
        lm.onderwerp,
        lm.status,
        lgp.leerlinggroep_id AS presenterende_groep_id,
        lgp.naam AS presenterende_groep_naam
      FROM lesmoment lm
      JOIN leerlinggroep lgp ON lgp.leerlinggroep_id = lm.presenterende_leerlinggroep_id
      WHERE lm.klas_id = :klas_id
        AND lm.periode_id = :periode_id
        AND lm.status = 'actief'
      ORDER BY lm.weeknummer DESC, lm.dag_van_week DESC, lm.lesuur DESC
      LIMIT 1
    ";
    $stmt3 = db()->prepare($sql3);
    $stmt3->execute([
      ':klas_id' => (int)$leerlinggroep['klas_id'],
      ':periode_id' => (int)$leerlinggroep['periode_id'],
    ]);
    $lesmoment = $stmt3->fetch();

    // MVP-regel: presenterende groep mag niet beoordelen
    $magBeoordelen = false;
    if ($lesmoment) {
        $magBeoordelen = ((int)$lesmoment['presenterende_groep_id'] !== (int)$leerlinggroep['leerlinggroep_id']);
    }

    require __DIR__ . '/../app/views/start.php';
    exit;
		
		//Case GET /beoordelen
		case $path === '/beoordelen' && $method === 'GET':
    if (!isset($_SESSION['gebruiker_id'])) {
        header('Location: ' . $basePath . '/inloggen');
        exit;
    }
    require __DIR__ . '/../app/core/db.php';

    $lesmomentId = (int)($_GET['lesmoment_id'] ?? 0);
    if ($lesmomentId <= 0) {
        http_response_code(400);
        echo 'Lesmoment ontbreekt.';
        exit;
    }

    // Leerling -> groep/klas/periode (Query 2)
    $sql2 = "
      SELECT
        lg.leerlinggroep_id,
        lg.naam AS leerlinggroep_naam,
        k.klas_id,
        k.naam AS klas_naam,
        p.periode_id,
        p.naam AS periode_naam
      FROM leerlinggroep_lid lgl
      JOIN leerlinggroep lg ON lg.leerlinggroep_id = lgl.leerlinggroep_id
      JOIN klas k          ON k.klas_id = lg.klas_id
      JOIN periode p       ON p.periode_id = k.periode_id
      WHERE lgl.gebruiker_id = :gebruiker_id
        AND p.actief = 1
      LIMIT 1
    ";
    $stmt2 = db()->prepare($sql2);
    $stmt2->execute([':gebruiker_id' => (int)$_SESSION['gebruiker_id']]);
    $leerlinggroep = $stmt2->fetch();
    if (!$leerlinggroep) {
        http_response_code(403);
        echo 'Geen leerlinggroep gevonden.';
        exit;
    }

    // Lesmoment ophalen + presenterende groep (controle)
    $sqlLm = "
      SELECT
        lm.lesmoment_id, lm.periode_id, lm.klas_id, lm.onderwerp, lm.status,
        lm.presenterende_leerlinggroep_id,
        lgp.naam AS presenterende_groep_naam
      FROM lesmoment lm
      JOIN leerlinggroep lgp ON lgp.leerlinggroep_id = lm.presenterende_leerlinggroep_id
      WHERE lm.lesmoment_id = :lesmoment_id
      LIMIT 1
    ";
    $stmtLm = db()->prepare($sqlLm);
    $stmtLm->execute([':lesmoment_id' => $lesmomentId]);
    $lesmoment = $stmtLm->fetch();
    if (!$lesmoment) {
        http_response_code(404);
        echo 'Lesmoment niet gevonden.';
        exit;
    }

    // Autorisatie: zelf niet beoordelen + alleen actieve lesmomenten (MVP)
    if ((int)$lesmoment['klas_id'] !== (int)$leerlinggroep['klas_id'] || (int)$lesmoment['periode_id'] !== (int)$leerlinggroep['periode_id']) {
        http_response_code(403);
        echo 'Geen toegang tot dit lesmoment.';
        exit;
    }
    if ((int)$lesmoment['presenterende_leerlinggroep_id'] === (int)$leerlinggroep['leerlinggroep_id']) {
        http_response_code(403);
        echo 'Presenterende groep kan niet beoordelen.';
        exit;
    }
    if ($lesmoment['status'] !== 'actief') {
        http_response_code(403);
        echo 'Dit lesmoment is niet actief.';
        exit;
    }

    // Check: al beoordeeld?
    $sqlBestaat = "
      SELECT beoordeling_id
      FROM beoordeling
      WHERE lesmoment_id = :lesmoment_id
        AND beoordelende_leerlinggroep_id = :leerlinggroep_id
      LIMIT 1
    ";
    $stmtB = db()->prepare($sqlBestaat);
    $stmtB->execute([
      ':lesmoment_id' => $lesmomentId,
      ':leerlinggroep_id' => (int)$leerlinggroep['leerlinggroep_id'],
    ]);
    $bestaat = $stmtB->fetch();
    if ($bestaat) {
        $_SESSION['login_fout'] = 'Jullie hebben dit lesmoment al beoordeeld.';
        header('Location: ' . $basePath . '/start');
        exit;
    }

    // Rubriek ophalen (Query 5)
    $sqlRub = "
      SELECT ro.rubriek_onderdeel_id, ro.titel, ro.uitleg_kort, ro.max_punten
      FROM rubriek r
      JOIN rubriek_onderdeel ro ON ro.rubriek_id = r.rubriek_id
      WHERE r.periode_id = :periode_id
        AND r.actief = 1
        AND ro.actief = 1
      ORDER BY ro.volgorde ASC
    ";
    $stmtRub = db()->prepare($sqlRub);
    $stmtRub->execute([':periode_id' => (int)$leerlinggroep['periode_id']]);
    $rubriekOnderdelen = $stmtRub->fetchAll();

    require __DIR__ . '/../app/views/beoordelen.php';
    exit;

		//Case post beoordelen
		case $path === '/beoordelen' && $method === 'POST':
    if (!isset($_SESSION['gebruiker_id'])) {
        header('Location: ' . $basePath . '/inloggen');
        exit;
    }
    require __DIR__ . '/../app/core/db.php';

    $lesmomentId = (int)($_POST['lesmoment_id'] ?? 0);
    $tops = trim($_POST['tops'] ?? '');
    $tips = trim($_POST['tips'] ?? '');
    $vraag = trim($_POST['vraag'] ?? '');

    if ($lesmomentId <= 0) {
        $_SESSION['login_fout'] = 'Lesmoment ontbreekt.';
        header('Location: ' . $basePath . '/start');
        exit;
    }
    if (mb_strlen($tops) < 10 || mb_strlen($tips) < 10) {
        $_SESSION['login_fout'] = 'Vul minimaal één top en één tip in (minstens 10 tekens).';
        header('Location: ' . $basePath . '/beoordelen?lesmoment_id=' . $lesmomentId);
        exit;
    }

    // Leerling -> groep (Query 2, compact)
    $sql2 = "
      SELECT lg.leerlinggroep_id, k.klas_id, p.periode_id
      FROM leerlinggroep_lid lgl
      JOIN leerlinggroep lg ON lg.leerlinggroep_id = lgl.leerlinggroep_id
      JOIN klas k          ON k.klas_id = lg.klas_id
      JOIN periode p       ON p.periode_id = k.periode_id
      WHERE lgl.gebruiker_id = :gebruiker_id AND p.actief = 1
      LIMIT 1
    ";
    $stmt2 = db()->prepare($sql2);
    $stmt2->execute([':gebruiker_id' => (int)$_SESSION['gebruiker_id']]);
    $lg = $stmt2->fetch();
    if (!$lg) {
        http_response_code(403);
        echo 'Geen leerlinggroep.';
        exit;
    }

    // Lesmoment ophalen + autorisatie
    $sqlLm = "SELECT lesmoment_id, periode_id, klas_id, status, presenterende_leerlinggroep_id
              FROM lesmoment WHERE lesmoment_id = :lesmoment_id LIMIT 1";
    $stmtLm = db()->prepare($sqlLm);
    $stmtLm->execute([':lesmoment_id' => $lesmomentId]);
    $lesmoment = $stmtLm->fetch();
    if (!$lesmoment) {
        http_response_code(404);
        echo 'Lesmoment niet gevonden.';
        exit;
    }
    if ((int)$lesmoment['klas_id'] !== (int)$lg['klas_id'] || (int)$lesmoment['periode_id'] !== (int)$lg['periode_id']) {
        http_response_code(403);
        echo 'Geen toegang.';
        exit;
    }
    if ((int)$lesmoment['presenterende_leerlinggroep_id'] === (int)$lg['leerlinggroep_id']) {
        http_response_code(403);
        echo 'Presenterende groep kan niet beoordelen.';
        exit;
    }
    if ($lesmoment['status'] !== 'actief') {
        http_response_code(403);
        echo 'Lesmoment niet actief.';
        exit;
    }

    // Rubriek onderdelen ophalen (om te checken wat verwacht wordt)
    $sqlRub = "
      SELECT ro.rubriek_onderdeel_id
      FROM rubriek r
      JOIN rubriek_onderdeel ro ON ro.rubriek_id = r.rubriek_id
      WHERE r.periode_id = :periode_id AND r.actief = 1 AND ro.actief = 1
      ORDER BY ro.volgorde ASC
    ";
    $stmtRub = db()->prepare($sqlRub);
    $stmtRub->execute([':periode_id' => (int)$lg['periode_id']]);
    $onderdelen = $stmtRub->fetchAll();

    // Verwachte niveaus uit POST: niveau[onderdeel_id] = 'goed'
    $niveaus = $_POST['niveau'] ?? [];
    $toegestaan = ['matig','voldoende','goed','uitstekend'];

    try {
        $pdo = db();
        $pdo->beginTransaction();

        // Kop aanmaken
        $stmtIns = $pdo->prepare("
          INSERT INTO beoordeling (lesmoment_id, beoordelende_leerlinggroep_id, ingediend_door_gebruiker_id)
          VALUES (:lesmoment_id, :leerlinggroep_id, :gebruiker_id)
        ");
        $stmtIns->execute([
          ':lesmoment_id' => $lesmomentId,
          ':leerlinggroep_id' => (int)$lg['leerlinggroep_id'],
          ':gebruiker_id' => (int)$_SESSION['gebruiker_id'],
        ]);
        $beoordelingId = (int)$pdo->lastInsertId();

        // Regels
        $stmtRegel = $pdo->prepare("
          INSERT INTO beoordeling_onderdeel (beoordeling_id, rubriek_onderdeel_id, niveau)
          VALUES (:beoordeling_id, :onderdeel_id, :niveau)
        ");

        foreach ($onderdelen as $row) {
            $oid = (int)$row['rubriek_onderdeel_id'];
            $niv = $niveaus[$oid] ?? '';

            if (!in_array($niv, $toegestaan, true)) {
                throw new RuntimeException('Niet alle rubric-onderdelen zijn ingevuld.');
            }

            $stmtRegel->execute([
              ':beoordeling_id' => $beoordelingId,
              ':onderdeel_id' => $oid,
              ':niveau' => $niv,
            ]);
        }

        // Tekst
        $stmtTxt = $pdo->prepare("
          INSERT INTO beoordeling_tekst (beoordeling_id, tops_tekst, tips_tekst, vraag_tekst)
          VALUES (:bid, :tops, :tips, :vraag)
        ");
        $stmtTxt->execute([
          ':bid' => $beoordelingId,
          ':tops' => $tops,
          ':tips' => $tips,
          ':vraag' => ($vraag === '' ? null : $vraag),
        ]);

        $pdo->commit();

        header('Location: ' . $basePath . '/bedankt');
        exit;

    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        $_SESSION['login_fout'] = 'Opslaan mislukt: ' . $e->getMessage();
        header('Location: ' . $basePath . '/beoordelen?lesmoment_id=' . $lesmomentId);
        exit;
    }

		// Case bedankt
		case $path === '/bedankt' && $method === 'GET':
    if (!isset($_SESSION['gebruiker_id'])) {
        header('Location: ' . $basePath . '/inloggen');
        exit;
    }
    echo '<h1>Bedankt!</h1><p>Beoordeling is ingediend.</p><p><a href="'.$basePath.'/start">Terug naar start</a></p>';
    exit;

		// Case uitloggen
		case $path === '/uitloggen' && $method === 'GET':
    session_destroy();
    header('Location: ' . $basePath . '/inloggen');
    exit;
		
		default:
    http_response_code(404);
    echo '<h1>404</h1><p>Pagina niet gevonden: ' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '</p>';
    exit;
}
