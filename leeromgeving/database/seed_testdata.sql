-- seed_testdata.sql
-- Testdata voor MVP Beoordelingstool i-lessen
-- Aannames:
-- 1) Je hebt mvp_schema.sql al geïmporteerd.
-- 2) Je hebt tabel `gebruiker` aangepast naar: voornaam, tussenvoegsel, achternaam (en kolom `naam` verwijderd).
-- 3) Database gebruikt InnoDB + utf8mb4.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ====== Opschonen (in juiste volgorde i.v.m. foreign keys) ======
TRUNCATE TABLE beoordeling_tekst;
TRUNCATE TABLE beoordeling_onderdeel;
DELETE FROM beoordeling;
ALTER TABLE beoordeling AUTO_INCREMENT = 1;
DELETE FROM lesmoment;
ALTER TABLE lesmoment AUTO_INCREMENT = 1;
DELETE FROM rubriek_onderdeel;
ALTER TABLE rubriek_onderdeel AUTO_INCREMENT = 1;

DELETE FROM rubriek;
ALTER TABLE rubriek AUTO_INCREMENT = 1;


TRUNCATE TABLE leerlinggroep_lid;
DELETE FROM leerlinggroep;
ALTER TABLE leerlinggroep AUTO_INCREMENT = 1;

DELETE FROM klas;
ALTER TABLE klas AUTO_INCREMENT = 1;
DELETE FROM periode;
ALTER TABLE periode AUTO_INCREMENT = 1;

DELETE FROM gebruiker;
ALTER TABLE gebruiker AUTO_INCREMENT = 1;

-- Rollen laten we staan (mvp_schema seeded ze al), maar voor zekerheid:
INSERT IGNORE INTO rol (rol_id, naam) VALUES
(1,'leerling'),
(2,'docent'),
(3,'beheerder');

-- ====== Gebruikers ======
-- Leerlingen (12 stuks, 3 per groep)
INSERT INTO gebruiker
(gebruiker_id, rol_id, voornaam, tussenvoegsel, achternaam, e_mail, leerlingnummer, geboortedatum, actief)
VALUES
(1, 1, 'Ayse',  NULL, 'Demir',     'b211001@school.nl', 'b211001', '2008-03-12', 1),
(2, 1, 'Noah',  NULL, 'Jansen',    'b211002@school.nl', 'b211002', '2008-11-04', 1),
(3, 1, 'Sara',  'de', 'Vries',     'b211003@school.nl', 'b211003', '2008-06-21', 1),

(4, 1, 'Mees',  NULL, 'Bakker',    'b211004@school.nl', 'b211004', '2008-01-30', 1),
(5, 1, 'Lina',  NULL, 'Smit',      'b211005@school.nl', 'b211005', '2008-09-17', 1),
(6, 1, 'Omar',  'El', 'Amrani',    'b211006@school.nl', 'b211006', '2008-02-08', 1),

(7, 1, 'Yara',  NULL, 'Visser',    'b211007@school.nl', 'b211007', '2008-07-02', 1),
(8, 1, 'Daan',  NULL, 'Bos',       'b211008@school.nl', 'b211008', '2008-12-19', 1),
(9, 1, 'Evi',   'van', 'Dijk',     'b211009@school.nl', 'b211009', '2008-05-26', 1),

(10,1, 'Milan', NULL, 'Kaya',      'b211010@school.nl', 'b211010', '2008-10-10', 1),
(11,1, 'Sofie', NULL, 'Willems',   'b211011@school.nl', 'b211011', '2008-04-14', 1),
(12,1, 'Adam',  'al', 'Hassan',    'b211012@school.nl', 'b211012', '2008-08-23', 1),

-- Docent + beheerder
(100,2, 'Kamal',  NULL, 'Docent',    'docent@school.nl',  NULL,      NULL,          1),
(101,3, 'Systeem', NULL, 'Beheerder','beheerder@school.nl', NULL,   NULL,          1);

-- ====== Periode + klas ======
INSERT INTO periode (periode_id, naam, actief) VALUES
(1, 'Periode 2', 1);

INSERT INTO klas (klas_id, periode_id, naam) VALUES
(1, 1, '5V1');

-- ====== Leerlinggroepen ======
INSERT INTO leerlinggroep (leerlinggroep_id, klas_id, naam) VALUES
(1, 1, 'Groep 1'),
(2, 1, 'Groep 2'),
(3, 1, 'Groep 3'),
(4, 1, 'Groep 4');

-- ====== Groepsleden ======
INSERT INTO leerlinggroep_lid (leerlinggroep_id, gebruiker_id) VALUES
(1,1),(1,2),(1,3),
(2,4),(2,5),(2,6),
(3,7),(3,8),(3,9),
(4,10),(4,11),(4,12);

-- ====== Rubriek + onderdelen (totaal 100 punten) ======
INSERT INTO rubriek (rubriek_id, periode_id, naam, actief) VALUES
(1, 1, 'Rubriek i-les', 1);

INSERT INTO rubriek_onderdeel
(rubriek_onderdeel_id, rubriek_id, titel, uitleg_kort, max_punten, volgorde, actief)
VALUES
(1, 1, 'Voorbereiding', 'De groep was goed voorbereid en kende de stof.', 20, 1, 1),
(2, 1, 'Inhoudelijke diepgang', 'De uitleg was correct, duidelijk en verdiepend.', 20, 2, 1),
(3, 1, 'Toepassing opdrachten', 'Opdrachten sloten aan bij leerdoelen en waren uitvoerbaar.', 15, 3, 1),
(4, 1, 'Interactie met de klas', 'De klas werd actief betrokken bij de i-les.', 10, 4, 1),
(5, 1, 'Samenwerking binnen de groep', 'Taken waren verdeeld en iedereen deed mee.', 10, 5, 1),
(6, 1, 'Presentatievaardigheden', 'Stemgebruik, houding en hulpmiddelen.', 15, 6, 1),
(7, 1, 'Tijdsbeheer', 'De tijd werd goed verdeeld en de les werd afgerond.', 10, 7, 1);

-- ====== Lesmoment (planning) ======
-- Groep 3 presenteert
INSERT INTO lesmoment
(lesmoment_id, periode_id, klas_id, presenterende_leerlinggroep_id, weeknummer, dag_van_week, lesuur, onderwerp, status)
VALUES
(1, 1, 1, 3, 7, 4, 3, 'Schakelingen – elementaire bewerkingen', 'actief');

-- ====== Voorbeeldbeoordelingen (om triggers te testen) ======
-- Groep 1 beoordeelt lesmoment 1 (ingediend door gebruiker 1)
INSERT INTO beoordeling
(beoordeling_id, lesmoment_id, beoordelende_leerlinggroep_id, ingediend_door_gebruiker_id, status, cijfer_zichtbaar)
VALUES
(1, 1, 1, 1, 'ingediend', 0);

INSERT INTO beoordeling_onderdeel (beoordeling_id, rubriek_onderdeel_id, niveau) VALUES
(1, 1, 'goed'),
(1, 2, 'voldoende'),
(1, 3, 'uitstekend'),
(1, 4, 'goed'),
(1, 5, 'goed'),
(1, 6, 'voldoende'),
(1, 7, 'goed');

INSERT INTO beoordeling_tekst (beoordeling_id, tops_tekst, tips_tekst, vraag_tekst) VALUES
(1, 'Duidelijke uitleg en goede voorbeelden.', 'Laat meer leerlingen zelf oefenen tijdens de opdrachten.', 'Kunnen jullie één extra voorbeeld geven van een schakeling in de praktijk?');

-- Groep 2 beoordeelt lesmoment 1 (ingediend door gebruiker 4)
INSERT INTO beoordeling
(beoordeling_id, lesmoment_id, beoordelende_leerlinggroep_id, ingediend_door_gebruiker_id, status, cijfer_zichtbaar)
VALUES
(2, 1, 2, 4, 'ingediend', 0);

INSERT INTO beoordeling_onderdeel (beoordeling_id, rubriek_onderdeel_id, niveau) VALUES
(2, 1, 'uitstekend'),
(2, 2, 'goed'),
(2, 3, 'goed'),
(2, 4, 'voldoende'),
(2, 5, 'goed'),
(2, 6, 'goed'),
(2, 7, 'voldoende');

INSERT INTO beoordeling_tekst (beoordeling_id, tops_tekst, tips_tekst, vraag_tekst) VALUES
(2, 'Sterke structuur en duidelijke taakverdeling.', 'Neem iets meer tijd voor vragen uit de klas.', NULL);

SET FOREIGN_KEY_CHECKS = 1;

-- Snelle checks (optioneel):
-- SELECT CONCAT_WS(' ', voornaam, tussenvoegsel, achternaam) AS naam, e_mail, leerlingnummer FROM gebruiker WHERE rol_id=1;
-- SELECT * FROM lesmoment;
-- SELECT beoordeling_id, totaal_punten, totaal_max_punten, cijfer FROM beoordeling ORDER BY beoordeling_id;
