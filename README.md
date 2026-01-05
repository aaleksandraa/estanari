# WizFlussi - Laravel 12 + Vue.js 3

Sistem za upravljanje plaćanjima dobavljačima.

## Zahtjevi

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+ (XAMPP)

## Brza instalacija (XAMPP)

### 1. Kreiraj bazu podataka

Otvori phpMyAdmin (http://localhost/phpmyadmin) i kreiraj bazu `fleetmasterdb`.

### 2. Instaliraj zavisnosti

```bash
cd backend
composer install
npm install
```

### 3. Generiši ključ

```bash
php artisan key:generate
```

### 4. Pokreni migracije i seedere

```bash
php artisan migrate
php artisan db:seed
```

### 5. Pokreni servere

```bash
# Terminal 1 - Laravel
php artisan serve

# Terminal 2 - Vite (za frontend)
npm run dev
```

Aplikacija: http://localhost:8000

## Test korisnici

| Email | Lozinka | Uloga |
|-------|---------|-------|
| admin@wizflussi.ba | password | Administrator |
| racunovodstvo@wizflussi.ba | password | Računovodstvo |
| pregled@wizflussi.ba | password | Pregled (samo čitanje) |

## Funkcionalnosti

- ✅ Autentifikacija (login/registracija)
- ✅ RBAC (admin, accountant, viewer)
- ✅ Dashboard sa statistikama i filterima
- ✅ CRUD plaćanja sa modalima
- ✅ CRUD dobavljača i poslovnica
- ✅ Batch označavanje plaćanja
- ✅ Export u CSV
- ✅ Izvještaji (dnevni, mjesečni, po dobavljaču, po valuti)
- ✅ Postavke profila i lozinke
- ✅ Audit logovi

## Baza podataka

- Host: 127.0.0.1
- Port: 3306
- Database: fleetmasterdb
- Username: root
- Password: (prazno)
