# Epidemi Dashboard

This project is a small Symfony application to manage epidemic monitoring data. It lets agents create countries, zones and surveillance points (which correspond to hospitals) and view their status on a map.

## Requirements

- PHP 8.2 or higher
- Composer
- PostgreSQL (or another database supported by Doctrine)

## Installation

1. Install PHP dependencies:

   ```bash
   composer install
   ```

2. Copy `.env` to `.env.local` and adjust the database connection string if necessary.

3. Run the database migrations to create/update the schema:

   ```bash
   php bin/console doctrine:migrations:migrate
   ```

The latest migration adds surveillance point statistics columns (`population`, `symptomatic`, `positive`). Run it after pulling new code so the list and map pages work correctly.

4. Start the local server:

   ```bash
   symfony serve
   ```

   or use the built‑in PHP server:

   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

Log in with the credentials defined in `config/packages/security.yaml` (by default an in‑memory user with email `agent@gmail.com` and password `password`).

## Updating Zone Statistics

Whenever you add, edit or delete a surveillance point (hôpital), the application recalculates the statistics for the related zone automatically. Zone statistics are the **sum** of the population, symptomatic cases and confirmed cases from all hospitals in the zone. The zone's color depends on the cumulative positive case rate across its surveillance points:

* **Green** – less than 5% positive
* **Orange** – 5–15%
* **Red** – 15% or more

Each zone can contain at most four surveillance points. Any attempt to add an extra point will be rejected.

## Tests

The repository includes PHPUnit configuration. After installing development dependencies you can run the test suite with:

```bash
vendor/bin/phpunit --configuration phpunit.dist.xml
```

