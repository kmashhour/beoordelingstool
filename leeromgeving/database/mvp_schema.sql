-- MVP database schema (MySQL/MariaDB) – Beoordelingstool i-lessen
-- Doel: basis tabellen + relaties + triggers voor punten/cijferberekening
-- Let op: gebruik InnoDB en utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ====== Rollen ======
DROP TABLE IF EXISTS rol;
CREATE TABLE rol (
  rol_id INT AUTO_INCREMENT PRIMARY KEY,
  naam VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Gebruikers ======
DROP TABLE IF EXISTS gebruiker;
CREATE TABLE gebruiker (
  gebruiker_id INT AUTO_INCREMENT PRIMARY KEY,
  rol_id INT NOT NULL,
  naam VARCHAR(150) NOT NULL,
  e_mail VARCHAR(190) NOT NULL UNIQUE,
  leerlingnummer VARCHAR(30) NULL UNIQUE,
  geboortedatum DATE NULL,
  actief TINYINT(1) NOT NULL DEFAULT 1,
  aangemaakt_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  bijgewerkt_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_gebruiker_rol
    FOREIGN KEY (rol_id) REFERENCES rol(rol_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Periode ======
DROP TABLE IF EXISTS periode;
CREATE TABLE periode (
  periode_id INT AUTO_INCREMENT PRIMARY KEY,
  naam VARCHAR(80) NOT NULL,
  startdatum DATE NULL,
  einddatum DATE NULL,
  actief TINYINT(1) NOT NULL DEFAULT 0,
  aangemaakt_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Klas ======
DROP TABLE IF EXISTS klas;
CREATE TABLE klas (
  klas_id INT AUTO_INCREMENT PRIMARY KEY,
  periode_id INT NOT NULL,
  naam VARCHAR(80) NOT NULL,
  CONSTRAINT uq_klas UNIQUE (periode_id, naam),
  CONSTRAINT fk_klas_periode
    FOREIGN KEY (periode_id) REFERENCES periode(periode_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Leerlinggroep ======
DROP TABLE IF EXISTS leerlinggroep;
CREATE TABLE leerlinggroep (
  leerlinggroep_id INT AUTO_INCREMENT PRIMARY KEY,
  klas_id INT NOT NULL,
  naam VARCHAR(80) NOT NULL,
  CONSTRAINT uq_leerlinggroep UNIQUE (klas_id, naam),
  CONSTRAINT fk_leerlinggroep_klas
    FOREIGN KEY (klas_id) REFERENCES klas(klas_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Leerlinggroep leden (koppeltabel) ======
DROP TABLE IF EXISTS leerlinggroep_lid;
CREATE TABLE leerlinggroep_lid (
  leerlinggroep_id INT NOT NULL,
  gebruiker_id INT NOT NULL,
  PRIMARY KEY (leerlinggroep_id, gebruiker_id),
  CONSTRAINT fk_lid_groep
    FOREIGN KEY (leerlinggroep_id) REFERENCES leerlinggroep(leerlinggroep_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_lid_gebruiker
    FOREIGN KEY (gebruiker_id) REFERENCES gebruiker(gebruiker_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Lesmoment (planning) ======
DROP TABLE IF EXISTS lesmoment;
CREATE TABLE lesmoment (
  lesmoment_id INT AUTO_INCREMENT PRIMARY KEY,
  periode_id INT NOT NULL,
  klas_id INT NOT NULL,
  presenterende_leerlinggroep_id INT NOT NULL,
  weeknummer INT NOT NULL,
  dag_van_week TINYINT NOT NULL,  -- 1=ma ... 7=zo
  lesuur TINYINT NOT NULL,
  onderwerp VARCHAR(200) NOT NULL,
  status ENUM('gepland','actief','afgesloten') NOT NULL DEFAULT 'gepland',
  aangemaakt_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lesmoment_periode
    FOREIGN KEY (periode_id) REFERENCES periode(periode_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_lesmoment_klas
    FOREIGN KEY (klas_id) REFERENCES klas(klas_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_lesmoment_presenterendegroep
    FOREIGN KEY (presenterende_leerlinggroep_id) REFERENCES leerlinggroep(leerlinggroep_id)
    ON DELETE RESTRICT,
  CONSTRAINT uq_lesmoment UNIQUE (klas_id, weeknummer, dag_van_week, lesuur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Rubriek ======
DROP TABLE IF EXISTS rubriek;
CREATE TABLE rubriek (
  rubriek_id INT AUTO_INCREMENT PRIMARY KEY,
  periode_id INT NOT NULL,
  naam VARCHAR(120) NOT NULL,
  actief TINYINT(1) NOT NULL DEFAULT 1,
  aangemaakt_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rubriek_periode
    FOREIGN KEY (periode_id) REFERENCES periode(periode_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Rubriek onderdeel ======
DROP TABLE IF EXISTS rubriek_onderdeel;
CREATE TABLE rubriek_onderdeel (
  rubriek_onderdeel_id INT AUTO_INCREMENT PRIMARY KEY,
  rubriek_id INT NOT NULL,
  titel VARCHAR(120) NOT NULL,
  uitleg_kort VARCHAR(255) NULL,
  max_punten INT NOT NULL,
  volgorde INT NOT NULL DEFAULT 1,
  actief TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_rubriekonderdeel_rubriek
    FOREIGN KEY (rubriek_id) REFERENCES rubriek(rubriek_id)
    ON DELETE CASCADE,
  CONSTRAINT uq_rubriekonderdeel UNIQUE (rubriek_id, titel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Beoordeling (kop) ======
DROP TABLE IF EXISTS beoordeling;
CREATE TABLE beoordeling (
  beoordeling_id INT AUTO_INCREMENT PRIMARY KEY,
  lesmoment_id INT NOT NULL,
  beoordelende_leerlinggroep_id INT NOT NULL,
  ingediend_door_gebruiker_id INT NOT NULL,
  status ENUM('ingediend','gepubliceerd') NOT NULL DEFAULT 'ingediend',
  ingediend_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  gepubliceerd_op DATETIME NULL,
  cijfer_zichtbaar TINYINT(1) NOT NULL DEFAULT 0,
  totaal_punten INT NOT NULL DEFAULT 0,
  totaal_max_punten INT NOT NULL DEFAULT 0,
  cijfer DECIMAL(3,1) NULL,
  CONSTRAINT fk_beoordeling_lesmoment
    FOREIGN KEY (lesmoment_id) REFERENCES lesmoment(lesmoment_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_beoordeling_groep
    FOREIGN KEY (beoordelende_leerlinggroep_id) REFERENCES leerlinggroep(leerlinggroep_id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_beoordeling_ingediend_door
    FOREIGN KEY (ingediend_door_gebruiker_id) REFERENCES gebruiker(gebruiker_id)
    ON DELETE RESTRICT,
  CONSTRAINT uq_beoordeling UNIQUE (lesmoment_id, beoordelende_leerlinggroep_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Beoordeling onderdeel (regels) ======
DROP TABLE IF EXISTS beoordeling_onderdeel;
CREATE TABLE beoordeling_onderdeel (
  beoordeling_id INT NOT NULL,
  rubriek_onderdeel_id INT NOT NULL,
  niveau ENUM('matig','voldoende','goed','uitstekend') NOT NULL,
  punten INT NOT NULL DEFAULT 0,
  max_punten INT NOT NULL DEFAULT 0,
  PRIMARY KEY (beoordeling_id, rubriek_onderdeel_id),
  CONSTRAINT fk_beoordelingonderdeel_beoordeling
    FOREIGN KEY (beoordeling_id) REFERENCES beoordeling(beoordeling_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_beoordelingonderdeel_rubriekonderdeel
    FOREIGN KEY (rubriek_onderdeel_id) REFERENCES rubriek_onderdeel(rubriek_onderdeel_id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Beoordeling tekst (1-op-1) ======
DROP TABLE IF EXISTS beoordeling_tekst;
CREATE TABLE beoordeling_tekst (
  beoordeling_id INT PRIMARY KEY,
  tops_tekst TEXT NOT NULL,
  tips_tekst TEXT NOT NULL,
  vraag_tekst TEXT NULL,
  CONSTRAINT fk_beoordelingtekst_beoordeling
    FOREIGN KEY (beoordeling_id) REFERENCES beoordeling(beoordeling_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Triggers ======
DROP TRIGGER IF EXISTS trg_bo_calc_before_ins;
DELIMITER $$
CREATE TRIGGER trg_bo_calc_before_ins
BEFORE INSERT ON beoordeling_onderdeel
FOR EACH ROW
BEGIN
  DECLARE v_max INT DEFAULT 0;
  DECLARE v_factor DECIMAL(4,2) DEFAULT 0;

  SELECT max_punten INTO v_max
  FROM rubriek_onderdeel
  WHERE rubriek_onderdeel_id = NEW.rubriek_onderdeel_id;

  SET NEW.max_punten = v_max;

  IF NEW.niveau = 'matig' THEN
    SET v_factor = 0.50;
  ELSEIF NEW.niveau = 'voldoende' THEN
    SET v_factor = 0.65;
  ELSEIF NEW.niveau = 'goed' THEN
    SET v_factor = 0.80;
  ELSEIF NEW.niveau = 'uitstekend' THEN
    SET v_factor = 1.00;
  END IF;

  SET NEW.punten = ROUND(v_max * v_factor);
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_bo_calc_before_upd;
DELIMITER $$
CREATE TRIGGER trg_bo_calc_before_upd
BEFORE UPDATE ON beoordeling_onderdeel
FOR EACH ROW
BEGIN
  DECLARE v_max INT DEFAULT 0;
  DECLARE v_factor DECIMAL(4,2) DEFAULT 0;

  SELECT max_punten INTO v_max
  FROM rubriek_onderdeel
  WHERE rubriek_onderdeel_id = NEW.rubriek_onderdeel_id;

  SET NEW.max_punten = v_max;

  IF NEW.niveau = 'matig' THEN
    SET v_factor = 0.50;
  ELSEIF NEW.niveau = 'voldoende' THEN
    SET v_factor = 0.65;
  ELSEIF NEW.niveau = 'goed' THEN
    SET v_factor = 0.80;
  ELSEIF NEW.niveau = 'uitstekend' THEN
    SET v_factor = 1.00;
  END IF;

  SET NEW.punten = ROUND(v_max * v_factor);
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_bo_recalc_after_ins;
DELIMITER $$
CREATE TRIGGER trg_bo_recalc_after_ins
AFTER INSERT ON beoordeling_onderdeel
FOR EACH ROW
BEGIN
  DECLARE v_sum INT DEFAULT 0;
  DECLARE v_maxsum INT DEFAULT 0;

  SELECT COALESCE(SUM(punten),0), COALESCE(SUM(max_punten),0)
    INTO v_sum, v_maxsum
  FROM beoordeling_onderdeel
  WHERE beoordeling_id = NEW.beoordeling_id;

  UPDATE beoordeling
  SET totaal_punten = v_sum,
      totaal_max_punten = v_maxsum,
      cijfer = CASE WHEN v_maxsum > 0 THEN ROUND((v_sum / v_maxsum) * 10, 1) ELSE NULL END
  WHERE beoordeling_id = NEW.beoordeling_id;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_bo_recalc_after_upd;
DELIMITER $$
CREATE TRIGGER trg_bo_recalc_after_upd
AFTER UPDATE ON beoordeling_onderdeel
FOR EACH ROW
BEGIN
  DECLARE v_sum INT DEFAULT 0;
  DECLARE v_maxsum INT DEFAULT 0;

  SELECT COALESCE(SUM(punten),0), COALESCE(SUM(max_punten),0)
    INTO v_sum, v_maxsum
  FROM beoordeling_onderdeel
  WHERE beoordeling_id = NEW.beoordeling_id;

  UPDATE beoordeling
  SET totaal_punten = v_sum,
      totaal_max_punten = v_maxsum,
      cijfer = CASE WHEN v_maxsum > 0 THEN ROUND((v_sum / v_maxsum) * 10, 1) ELSE NULL END
  WHERE beoordeling_id = NEW.beoordeling_id;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_bo_recalc_after_del;
DELIMITER $$
CREATE TRIGGER trg_bo_recalc_after_del
AFTER DELETE ON beoordeling_onderdeel
FOR EACH ROW
BEGIN
  DECLARE v_sum INT DEFAULT 0;
  DECLARE v_maxsum INT DEFAULT 0;

  SELECT COALESCE(SUM(punten),0), COALESCE(SUM(max_punten),0)
    INTO v_sum, v_maxsum
  FROM beoordeling_onderdeel
  WHERE beoordeling_id = OLD.beoordeling_id;

  UPDATE beoordeling
  SET totaal_punten = v_sum,
      totaal_max_punten = v_maxsum,
      cijfer = CASE WHEN v_maxsum > 0 THEN ROUND((v_sum / v_maxsum) * 10, 1) ELSE NULL END
  WHERE beoordeling_id = OLD.beoordeling_id;
END$$
DELIMITER ;

-- ====== Basisrollen ======
INSERT IGNORE INTO rol (naam) VALUES ('leerling'), ('docent'), ('beheerder');

SET FOREIGN_KEY_CHECKS = 1;
