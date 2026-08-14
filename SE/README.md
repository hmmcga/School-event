# Smart School Event Management System — "School Event" Module

PHP + PostgreSQL prototype covering the 9 modules from your scope
(Section 1.3.2), styled to match your existing clinic-system UI
(dark sidebar, cream content, badge pills, table layout).

## What's included

| Module (from your manuscript)              | Files                                   |
|----------------------------------------------|------------------------------------------|
| User Roles and Access Control                | `login.php`, `includes/auth.php`, `modules/users.php` |
| Event Planning & Creation                    | `modules/list.php` / `form.php` (`module=events`) |
| Participant Registration & Management        | `module=registrations` |
| Venue & Resource Scheduling (+ conflict check)| `module=venues` |
| Invitation & Communication System             | `module=invitations` |
| Attendance Tracking & Verification            | `module=attendance` |
| Event Budget & Expense Tracking               | `module=budget` |
| Program Flow and Activity Monitoring          | `module=program` |
| Multimedia & Documentation Portal             | `module=media` |
| Feedback & Evaluation System                  | `module=feedback` |
| Event Report & Analytics                      | `modules/reports.php` |

**Design note:** modules 2–10 above share one generic engine
(`modules/list.php`, `modules/form.php`, `modules/delete.php`), driven
by the field/table definitions in `config/modules.php`. This keeps the
9 modules consistent and avoids 30+ near-duplicate files — add a new
module by adding one array entry, not by writing new CRUD code.

The NLP AI assistant itself (conflict-recommendation + sentiment
classification) is **not implemented** here — that's a separate
service per your Chapter 3 architecture (Section 3.2, "AI/NLP
services"). This prototype gives that service somewhere to write to:
`venue_bookings` (for conflict suggestions) and
`feedback_entries.sentiment` (for classified sentiment), and the
Reports page already displays whatever lands in `sentiment`.

## Setup

1. **Create the database and load the schema:**
   ```bash
   createdb sms_event
   psql -U postgres -d sms_event -f database/schema.sql
   ```

2. **Set your DB credentials** in `config/database.php`
   (`DB_HOST`, `DB_USER`, `DB_PASS`).

3. **Generate real password hashes** for the seeded users — the ones
   in `schema.sql` are placeholders and won't work as-is. Run once:
   ```php
   <?php echo password_hash('Password123!', PASSWORD_DEFAULT);
   ```
   and `UPDATE users SET password_hash = '<result>' WHERE ...;`
   for each seeded row (or just register through `modules/users.php`
   after logging in as admin some other way / inserting one row by hand).

4. **Serve the app** (needs `pdo_pgsql` extension enabled):
   ```bash
   php -S localhost:8000
   ```
   Visit `http://localhost:8000` → redirects to `/login.php`.

## Folder structure

```
sms-event/
├── config/
│   ├── database.php      PDO connection
│   └── modules.php       field/table config for all 9 modules
├── includes/
│   ├── auth.php          session, login/logout, RBAC, CSRF
│   ├── header.php / footer.php / sidebar.php
│   └── form_fields.php   field renderer + badge pill renderer
├── modules/
│   ├── list.php          generic listing + search (all modules)
│   ├── form.php          generic add/edit + venue conflict check
│   ├── delete.php        generic delete
│   ├── reports.php       Event Report & Analytics dashboard
│   └── users.php         User Access Control (admin only)
├── assets/css/style.css
├── database/schema.sql
├── dashboard.php
├── login.php / logout.php
└── index.php
```

## Things to build next
- Wire the actual NLP microservice (Section 2.4.1/3.4) to call an API
  that writes back into `venue_bookings.status` / `feedback_entries.sentiment`.
- File uploads for the Multimedia module (`media_files.file_path`
  currently expects a path/URL you provide manually).
- Email/SMS sending for Invitations (`invitations` table just logs intent).
- QR-code generation/scanning for Attendance check-in.

## Vercel deployment

To deploy this PHP app to Vercel:

1. Add these files (already included): `vercel.json` and `.vercelignore`.
2. Initialize git, commit your code, and push to a GitHub repository.

   ```bash
   git init
   git add .
   git commit -m "Prepare app for Vercel deployment"
   git branch -M main
   git remote add origin <your-github-repo-url>
   git push -u origin main
   ```

3. In Vercel, import the GitHub repository and create a new Project. Vercel will use `vercel.json` to build.

Notes:
- The project uses the `@vercel/php` builder so PHP files are served via Vercel's PHP runtime.
- Local folders like `database/` and `uploads/` are ignored by `.vercelignore` — move any production data to external storage before deployment.

