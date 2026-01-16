# beoordelingstool
Masterpiece beoordelingstool.
## Inleiding
Dit project is een webapplicatie voor het verzamelen en modereren van feedback en beoordelingen bij **i-lessen** (interactieve lessen) in de bovenbouw havo/vwo informatica.  
Leerlingen werken in groepen en verzorgen per periode een i-les. Andere leerlinggroepen geven na afloop **gestructureerde feedback** en een **beoordeling** aan de presenterende groep.

De applicatie ondersteunt **academische vaardigheden** zoals kritisch kijken, feedback geven en reflecteren, met nadruk op formatief handelen.

Dit project is ontwikkeld als **Master portfolio–onderdeel Full-stack Web Design**.


## Doel van de applicatie
- Leerlingen gestructureerd feedback laten geven (tops & tips)
- Leerlingen als groep een beoordeling laten geven via een rubriek
- Docent regie laten houden via moderatie en publicatie
- Planning van i-lessen als uitgangspunt gebruiken
- Schaalbaar ontwerp voor toekomstige uitbreiding

## MVP-afbakening
De MVP richt zich bewust op de **kernfunctionaliteit**:

### In scope (MVP)
- Inloggen met leerlingnummer/e-mail + geboortedatum
- Automatisch bepalen van:
  - klas
  - leerlinggroep
  - actueel lesmoment
- Groepsbeoordeling per beoordelende groep (1 inzending)
- Dynamische beoordelingsrubriek
- Feedbackvelden: top, tip (feedforward), optionele vraag
- Docentmoderatie:
  - feedback aanpassen
  - publiceren
  - instellen of cijfer zichtbaar is
- Leerlingen zien feedback **pas na publicatie**

### Buiten scope (toekomstig)
- Individuele beoordelingen per leerling
- Microsoft Single Sign-On
- AI-ondersteuning bij samenvatten feedback
- Zelfreflectie door presenterende groep
- Vergelijkingen over meerdere schooljaren
- Integratie met leeromgeving (lesmateriaal, toetsen, logboek)

## Gebruikersrollen
- **Leerling**
  - beoordeelt andere groepen
  - ziet gepubliceerde feedback over eigen presentatie
- **Docent**
  - beheert lesmomenten
  - bekijkt ingezonden beoordelingen
  - modereert en publiceert feedback
  - bepaalt zichtbaarheid van het cijfer
- **Beheerder** (conceptueel, MVP beperkt)
  - import van klassen, groepen en gebruikers

## Technische keuzes

### Backend
- **Plain PHP (geen framework)**  
  Bewuste keuze vanwege tijdsbeperking en focus op kernfunctionaliteit.
- PDO met prepared statements
- Transacties bij opslaan beoordelingen
- Server-side validatie (defensief programmeren)

### Frontend
- HTML5 + CSS
- **Flexbox** voor layout (responsive)
- Centrale stylesheet (`app.css`)
- Geen JavaScript vereist voor kernfunctionaliteit

### Database
- MySQL (InnoDB)
- Relationeel model met o.a.:
  - gebruiker
  - rol
  - klas / leerlinggroep
  - lesmoment
  - rubriek / rubriek_onderdeel
  - beoordeling (+ onderdelen + tekst)
- Triggers voor:
  - puntentelling
  - cijferberekening
  
## Projectstructuur
leeromgeving/
├─ public/
│ ├─ index.php # Front controller / router
│ └─ assets/
│ └─ css/app.css
├─ app/
│ ├─ config/
│ ├─ core/
│ │ ├─ db.php
│ │ └─ helpers
│ ├─ views/
│ │ ├─ layout/
│ │ ├─ login.php
│ │ ├─ start.php
│ │ ├─ beoordelen.php
│ │ └─ docent_*.php
└─ database/
├─ schema.sql
└─ seed_testdata.sql

## Installatie (lokaal / server)
1. Plaats project in webroot (bijv. IIS virtual directory)
2. Zet document root op `/public`
3. Maak MySQL database aan (bijv. `leeromgeving`)
4. Importeer:
   - `schema.sql`
   - `seed_testdata.sql`
5. Configureer databasegegevens in:
6. Zorg dat URL Rewrite actief is (IIS: `web.config` aanwezig)

## Testaccounts (seed)
Voorbeeld:
- **Leerling**
- leerlingnummer: `201001`
- geboortedatum: `2008-03-12`
- **Docent**
- e-mail: `docent@school.nl`
- geboortedatum: `1980-01-01`


## Onderwijskundige onderbouwing (kort)
De applicatie ondersteunt formatief handelen:
- leerlingen leren feedback geven en ontvangen
- nadruk op reflectie en feedforward
- docent borgt kwaliteit en veiligheid via moderatie
- beoordeling is primair leergericht (formatief), niet summatief


## Toekomstige uitbreiding
Het ontwerp houdt rekening met:
- extra rollen
- andere rubrieken per vak/periode
- individuele feedback
- SSO
- analytics en voortgangsoverzichten


## Auteur
**Kamal Mashhour**  
Informatica / Onderwijstechnologie  
Full-stack Web Design portfolio
