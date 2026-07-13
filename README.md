# SMARS — Saudi HR & Attendance System

Bilingual (Arabic-first, RTL/LTR) HR/attendance platform for one organization that
manages four companies (AMNIAT, AMNIAT FACTORY, PTC, PTC Construction). Built on
Laravel 12 with a self-hosted, offline-capable UI (local Cairo font, no CDNs).

## Modules

- **Organization** — companies (with legal profiles + logos), company-scoped branches,
  global departments, positions, and shift schedules (one or two shifts per day)
- **Employees** — full CODEX schema with Saudi validation (national ID/Iqama, Saudi
  phone, IBAN + bank match, passport uniqueness, expiry rules), tabbed profile,
  per-company dashboard
- **Attendance & Biometric**
  - Biometric device fleet + **read-only ZKTeco sync**, employee enrollment copy
  - Punches: manual entry, CSV import, unmatched-punch review
  - Daily summary engine (paired sessions, worked/late/early-leave/overtime, exceptions)
  - Per-company policies, holidays, employee leaves, punch-correction workflow
  - Monthly matrix + attendance report (CSV export)
- **Payroll periods** — lock a company month (freezes attendance edits) and export
  per-employee payroll CSV incl. overtime

## Requirements

- PHP **8.2+**, Composer
- MySQL / MariaDB (XAMPP works well on Windows)
- Node.js + npm
- *(Optional, for live device sync)* Python 3 + `pip install pyzk`

## Setup

```bash
git clone https://github.com/gawhara/SMARS.git
cd SMARS

composer install
npm install
npm run build            # compile CSS/JS + local Cairo font into public/build

cp .env.example .env
php artisan key:generate
# create the database named in .env (default: smars), then:
php artisan migrate --seed
```

`migrate --seed` sets up roles, the four companies + branches, departments, positions,
shift schedules, banks, countries, system settings, and the **super-admin** user.

### Optional demo data

```bash
php artisan db:seed --class=MachineEmployeeSeeder   # ~40 employees with HR/device IDs
php artisan db:seed --class=MachinePunchSeeder      # requires the punch fixture (below)
```

`MachinePunchSeeder` imports `attendance-punches-2026-07-12.json` (a ~10 MB device export
kept out of the repo). If the file is absent the seeder skips gracefully — drop the file
in the project root to enable it.

## Run

```bash
# start MySQL (XAMPP Control Panel, or the mysqld service), then:
php artisan serve            # http://127.0.0.1:8000
```

**Default login:** `admin@smars.local` / `password` *(change before any real use).*

## Biometric device sync (optional)

Devices are managed under **Biometric Devices**. Sync is strictly read-only — the
[helper script](scripts/zkteco_readonly_sync.py) only calls `get_attendance()` and never
writes to the device.

```bash
pip install pyzk
php artisan attendance:sync-devices          # sync all auto-sync-enabled LAN devices
php artisan schedule:work                    # runs the 5-minute auto-sync in the background
```

Or click **Sync now** on a device page.

## Testing

```bash
php artisan test
```

Feature tests run against in-memory SQLite (no MySQL needed).

## Troubleshooting

- **Pages 500 with a DB connection error** → MySQL isn't running. Start it (XAMPP
  Control Panel is the most reliable) and retry.
- **UI loads unstyled** → a stale `public/hot` file points Vite at a dev server that
  isn't running. Delete `public/hot` (or run `npm run dev` and keep it running).
- **Timezone / weekend** → the app uses `Asia/Riyadh`; the weekly rest day is **Friday**,
  and the default late tolerance is **10 minutes** (configurable per company under
  Attendance → Policies).

## License

Proprietary — internal HR system.
