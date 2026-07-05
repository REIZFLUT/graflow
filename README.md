# Graflow

> **Alpha-Version** — Graflow befindet sich in aktiver Entwicklung. APIs, Datenmodelle und Oberflächen können sich noch ändern; für den produktiven Einsatz ist die Anwendung noch nicht vorgesehen.

Graflow ist eine redaktionelle Workflow-Anwendung zum Erstellen und Verwalten von Artikeln, Publikationen und Editor-Einstellungen. Die App basiert auf dem Laravel + React Starter Kit und erweitert es um ein vollständiges Publikations- und Artikel-Management mit einem strukturierten Rich-Text-Editor.

## Funktionen

- **Artikel** — Erstellen, Bearbeiten und Verwalten von Artikeln mit TipTap-Editor (Formatierung, Tabellen, Bilder, Fußnoten, Marginalien, Mathematik, Info-Boxen)
- **Versionierung** — Automatische Artikelversionen mit Wiederherstellung früherer Stände
- **Medien** — Bild-Upload mit Staging-Bereich, Vorschau-Varianten und Metadaten
- **Publikationen** — Organisation von Artikeln in Publikationen mit Ausgaben (Issues) und Kategorien
- **Editor-Einstellungen** — Konfigurierbare Typografie- und Theme-Sets pro Publikation
- **Benutzerrollen** — Admin, Produktmanager, Editor und Autor
- **Authentifizierung** — Laravel Fortify mit Zwei-Faktor-Authentifizierung und Passkeys
- **Mehrsprachigkeit** — Deutsch und Englisch

## Tech Stack

| Bereich | Technologie |
|---------|-------------|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Inertia.js v3, React 19, TypeScript |
| Editor | TipTap |
| Styling | Tailwind CSS v4, shadcn/ui |
| Auth | Laravel Fortify, Passkeys |
| Routing (Frontend) | Laravel Wayfinder |

## Voraussetzungen

- [DDEV](https://ddev.com/) (empfohlen für lokale Entwicklung)
- PHP 8.4+
- Composer 2
- Node.js 22+ und npm

## Installation mit DDEV

```bash
git clone git@github.com:REIZFLUT/graflow.git
cd graflow

ddev start
ddev composer install
ddev npm install
ddev exec cp .env.example .env
ddev exec php artisan key:generate
ddev exec php artisan migrate --seed
ddev npm run build
```

Die Anwendung ist danach unter **https://graflow.ddev.site** erreichbar.

Für die Entwicklung mit Hot Reload:

```bash
ddev composer run dev
```

## Installation ohne DDEV

```bash
git clone git@github.com:REIZFLUT/graflow.git
cd graflow

composer run setup
php artisan db:seed
```

Passe die Datenbankverbindung in `.env` an, bevor du migrierst und seedest.

## Demo-Zugangsdaten

Nach `php artisan db:seed` stehen folgende Testbenutzer zur Verfügung (Passwort jeweils `password`):

| Rolle | E-Mail |
|-------|--------|
| Administrator | admin@example.com |
| Produktmanager | productmanager@example.com |
| Editor | editor@example.com |
| Autor | pia.maier@example.com |

Der Demo-Seeder legt zusätzlich Beispiel-Publikationen und Artikel an.

## Entwicklung

```bash
# PHP-Code formatieren
composer lint

# Frontend linten und formatieren
npm run lint
npm run format

# TypeScript prüfen
npm run types:check

# Alle CI-Checks lokal ausführen
composer ci:check
```

## Tests

```bash
php artisan test

# Einzelne Testdatei
php artisan test --compact tests/Feature/Articles/ArticleCrudTest.php
```

## Lizenz

MIT
