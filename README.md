# For the Love of Boardgames

Zelf-gehoste bordspellencollectie voor jou en je speelgroep. Zoek spellen op via
BoardGameGeek (BGG), houd je eigen collectie bij, deel 'm met een of meerdere
"playgroups", en krijg suggesties welke spellen je kan spelen op een speelavond
op basis van wie er aanwezig is.

## Features

- Inloggen/registreren met een uitnodigingscode (bcrypt-gehashte wachtwoorden, sessies via Auth.js).
- Spellen zoeken en toevoegen via de BoardGameGeek API — naam, cover art, beschrijving,
  spelersaantal, speeltijd, complexiteit, BGG-rating, categorieën/mechanics, ontwerpers en
  illustratoren (met link naar hun BGG-pagina).
- "How to play"-knop die doorlinkt naar een YouTube-zoekopdracht voor dat spel.
- Playgroups: maak een groep aan of join er een met een uitnodigingscode. Iedereen in een
  groep ziet elkaars collectie in een gecombineerd overzicht.
- Speelavond-tool: kies een groep, vink aan wie er is, en krijg een gefilterde/gesorteerde
  lijst met spellen die geschikt zijn voor dat aantal spelers.

## Draaien met Docker (Synology NAS)

### 1. Bestanden op de NAS zetten

Zet deze repository ergens onder bijvoorbeeld `/volume1/docker/boardgames` op je NAS (via
File Station, git clone over SSH, of `Task Scheduler` met een git-commando).

### 2. `.env` aanmaken

Kopieer `.env.example` naar `.env` en vul in:

```bash
cp .env.example .env
```

- `POSTGRES_PASSWORD`: een sterk wachtwoord.
- `AUTH_SECRET`: genereer met `openssl rand -base64 32`.
- `AUTH_URL`: de URL waarop de app straks bereikbaar is (bv. `http://<nas-ip>:3000` of je
  eigen domeinnaam als je een reverse proxy gebruikt).
- `SEED_INVITE_CODE` (optioneel): zet een vaste eerste uitnodigingscode, anders wordt er een
  willekeurige gegenereerd en getoond in de logs bij de eerste start.

### 3. Starten via Container Manager

**Optie A — Container Manager UI:**
1. Open **Container Manager** → **Project** → **Create**.
2. Kies de map met deze bestanden als project-map (die bevat `docker-compose.yml`).
3. Container Manager herkent `docker-compose.yml` automatisch — bevestig en start het project.

**Optie B — SSH:**
```bash
cd /volume1/docker/boardgames
docker compose up -d --build
```

Bij de eerste start:
- draait de app automatisch de database-migraties (`prisma migrate deploy`);
- wordt er een eerste uitnodigingscode aangemaakt als die nog niet bestaat — check de logs:

```bash
docker compose logs app | grep "invite code"
```

Deel die code met je vrienden zodat zij een account kunnen aanmaken op `http://<nas-ip>:3000/register`.

### 4. Toegang van buiten je thuisnetwerk

Zet in Synology **Control Panel → Login Portal → Advanced → Reverse Proxy** (of gebruik
Traefik/Nginx Proxy Manager) een reverse proxy op poort `APP_PORT` (standaard 3000) met een
eigen domein/subdomein en HTTPS-certificaat. Zet `AUTH_URL` in `.env` dan op die publieke URL.

## Lokale ontwikkeling (zonder Docker)

```bash
npm install
docker compose up -d db          # alleen de database-container
cp .env.example .env             # en pas DATABASE_URL/AUTH_SECRET aan
npx prisma migrate dev
npm run db:seed                  # maakt een eerste uitnodigingscode aan
npm run dev
```

## Tech stack

Next.js (App Router, TypeScript), Tailwind CSS, PostgreSQL + Prisma, Auth.js (Credentials
provider), BoardGameGeek XML API2.

## Roadmap / bewust nog niet gebouwd

- Publieke, open registratie (nu nog invite-code-only) en OAuth-login.
- Eigen image-caching/proxy voor BGG-afbeeldingen.
- Wishlists, uitbreidingen (expansions), geavanceerd filteren/zoeken.
- Mobiele app / notificaties.
