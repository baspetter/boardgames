# My Game Circle

Zelf-gehoste bordspellencollectie voor jou en je speelgroep. Zoek spellen op via
BoardGameGeek (BGG), houd je eigen collectie bij, deel 'm met een of meerdere
"playgroups", en krijg suggesties welke spellen je kan spelen op een speelavond
op basis van wie er aanwezig is.

Gebouwd als klassieke PHP-website (geen build-stap, geen Node/Docker) zodat het
op gewone, goedkope shared hosting draait (bijv. mijn.host webhosting).

## Features

- Inloggen/registreren met een uitnodigingscode (bcrypt via `password_hash`,
  PHP-sessies).
- Spellen zoeken en toevoegen via de BoardGameGeek API — naam, spelersaantal,
  speeltijd, complexiteit, BGG-rating, categorieën/mechanics, ontwerpers en
  illustratoren (met link naar hun BGG-pagina), beste aantal spelers (uit BGG's
  stemming).
- Handmatig spellen toevoegen (titel, cover art URL, beschrijving, jaartal,
  spelersaantal, complexiteit, type, how-to-play-link) — werkt ook zonder BGG.
- **Automatische image-optimalisatie**: elke cover art (via BGG of handmatig)
  wordt gedownload en lokaal opgeslagen als twee gecomprimeerde JPEG's (~800px
  voor de detailpagina, ~400px voor de grid) via GD — nooit de originele
  (soms meerdere MB grote) bron-afbeelding zelf bewaard.
- Bewerken: pas achteraf alsnog info aan bij elk spel, of ververs een
  BGG-gekoppeld spel met de laatste data via "Update with BGG".
- "How to play"-knop die doorlinkt naar de opgegeven video, of anders een
  YouTube-zoekopdracht.
- Playgroups: maak een groep aan of join er een met een uitnodigingscode.
  Iedereen in een groep ziet elkaars collectie in een gecombineerd overzicht.
- Speelavond-tool: kies een groep, vink aan wie er is, en krijg een
  gefilterde/gesorteerde lijst met spellen die geschikt zijn voor dat aantal
  spelers.
- Pinterest-stijl masonry-grid — cover art in de eigen (vierkante/rechthoekige)
  verhouding, geen geforceerde crop.

## Vereisten op je hostingpakket

- PHP 8.1 of hoger, met de extensies `pdo_mysql`, `gd`, `curl`, `simplexml`
  (vrijwel altijd standaard aanwezig bij shared hosting).
- Een MySQL/MariaDB-database.
- SSH-toegang (voor het eenmalig draaien van het schema/seed-script en voor
  `git pull`-updates).

## Installatie

### 1. Bestanden op de server zetten

Via SSH (aanbevolen, dan kun je later gewoon `git pull` doen voor updates):

```bash
git clone <repo-url> ~/domains/mygamecircle.com/  # of jouw webroot-pad
cd ~/domains/mygamecircle.com/
```

### 2. `.env` aanmaken

```bash
cp .env.example .env
```

Vul in:
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`: de gegevens van de database die
  je in je hostingpaneel hebt aangemaakt.
- `BGG_API_TOKEN`: **vereist voor het zoeken/toevoegen van spellen via BGG.**
  BoardGameGeek vereist een geregistreerde, goedgekeurde applicatie met een
  Authorization-token voor API-toegang. Ga naar
  https://boardgamegeek.com/applications, registreer een applicatie (kies
  "Non-commercial"), wacht op goedkeuring (kan een week of langer duren),
  maak daarna onder "Tokens" een token aan, en zet die hier. Zonder dit token
  werkt de rest van de app gewoon door (handmatig toevoegen blijft werken),
  je krijgt alleen een duidelijke foutmelding bij BGG-acties.

### 3. Database-schema laden

```bash
mysql -u JOUW_DB_USER -p JOUW_DB_NAAM < sql/schema.sql
```

### 4. Eerste uitnodigingscode aanmaken

```bash
php bin/seed.php
```

Dit toont een code — deel die met je vrienden zodat zij een account kunnen
aanmaken op `https://mygamecircle.com/register.php`.

### 5. Header-afbeeldingen (optioneel)

Zet een `header-background.png` en `header-logo.png` in de map `assets/` als
je een eigen bannerafbeelding boven de site wil (verwijst naar precies die
bestandsnamen in `includes/header.php`).

### 6. Klaar

Zorg dat het domein naar deze map wijst (webroot), en dat `uploads/covers/`
beschrijfbaar is voor de webserver (meestal standaard goed via je hostingpaneel).

## Updates uitrollen

```bash
cd ~/domains/mygamecircle.com/
git pull origin main
```

Geen build-stap, geen herstart nodig — PHP-bestanden worden direct
geïnterpreteerd. Alleen bij een nieuwe entry in `sql/` (schema-wijziging) moet
je die handmatig tegen de database draaien:

```bash
mysql -u JOUW_DB_USER -p JOUW_DB_NAAM < sql/nieuwe-migratie.sql
```

## Lokale ontwikkeling

```bash
php -S localhost:8080          # PHP's ingebouwde dev-server
mysql -u root -e "CREATE DATABASE boardgames;"
mysql boardgames < sql/schema.sql
cp .env.example .env           # pas DB_* aan naar je lokale database
php bin/seed.php
```

## Projectstructuur

```
index.php, login.php, register.php, ...   Pagina's (klassieke PHP: 1 bestand = 1 URL)
includes/                                 Gedeelde logica (db, auth, bgg, image, ...)
api/                                      AJAX-endpoints (JSON in/uit, voor de zoek-terwijl-je-typt UI e.d.)
assets/                                   CSS + JS (geen build-stap)
sql/schema.sql                            Database-schema
bin/seed.php                              Eenmalig setup-script (eerste uitnodigingscode)
uploads/covers/                           Lokaal geoptimaliseerde cover art (gitignored)
```

## Tech stack

PHP 8.1+ (geen framework), MySQL/MariaDB via PDO, vanilla JavaScript (geen
build-stap), BoardGameGeek XML API2, GD voor image-optimalisatie.

## Roadmap / bewust nog niet gebouwd

- Publieke, open registratie (nu nog invite-code-only) en OAuth-login.
- Wishlists, uitbreidingen (expansions), geavanceerd filteren/zoeken.
- Mobiele app / notificaties.
