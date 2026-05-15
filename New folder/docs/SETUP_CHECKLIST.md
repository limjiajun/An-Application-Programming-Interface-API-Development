# Setup Checklist

Use this checklist before demonstrating the API.

## PHP/XAMPP

The code needs PHP 8+ and the PostgreSQL PDO driver.

Check enabled extensions:

```powershell
C:\xampp\php\php.exe -m | Select-String -Pattern "PDO|pdo_pgsql|pgsql"
```

If `pdo_pgsql` is missing, open `C:\xampp\php\php.ini` and enable these lines by removing the leading semicolon:

```ini
extension=pdo_pgsql
extension=pgsql
```

Restart Apache after changing `php.ini`.

## PostgreSQL/PostGIS

Install PostgreSQL with PostGIS. Confirm these tools are available:

```powershell
psql --version
shp2pgsql --version
```

If Windows cannot find them, add the PostgreSQL `bin` folder to PATH. A common location is:

```text
C:\Program Files\PostgreSQL\16\bin
```

## Project Config

Copy the database config template:

```powershell
Copy-Item config\config.example.php config\config.php
```

Edit `config\config.php` with the PostgreSQL database name, username, and password.

## Database Import

From the project folder:

```powershell
psql -U postgres -d sbe3603_assignment1 -f database/schema.sql
```

Then follow the staged import commands in:

```text
database/import.sql
```

## Quick API Smoke Test

```powershell
Invoke-RestMethod "http://localhost/sbe3603-assignment1/public/index.php/health"
Invoke-RestMethod "http://localhost/sbe3603-assignment1/public/index.php/localities?limit=5"
```

