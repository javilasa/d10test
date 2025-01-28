# Drupal Project with DDEV

This project is a Drupal-based website using DDEV for local development.

## Requirements

- [DDEV](https://ddev.readthedocs.io/en/stable/) installed
- Docker installed and running

## Setup Instructions

### 1. Clone the repository
```bash
git clone https://github.com/javilasa/d10test.git
cd d10test
```

### 2. Start DDEV
```bash
ddev start
```

### 3. Import or create the database
If you have a database backup, you can import it using:
```bash
ddev import-db --src=backup.sql.gz
```

Alternatively, if you need to set up a fresh database, you can install Drupal using:
```bash
ddev drush site-install standard --account-name=admin --account-pass=admin --db-url=mysql://db:db@db/db -y
```

### 4. Access the project
- Visit the site in your browser:
  ```
  https://d10test.ddev.site
  ```
- Log in as admin:
  ```bash
  ddev drush uli
  ```
  Copy and paste the generated login link into your browser.

### 5. Stop the project when done
```bash
ddev stop
```

## Additional Commands
- **View logs:** `ddev logs`
- **Restart DDEV:** `ddev restart`
- **Export the database:** `ddev export-db --file=db_backup.sql.gz`


