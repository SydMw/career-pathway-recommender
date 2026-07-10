# AI-Powered Career and Pathway Recommendation System

Final year project (BIT/2024/58087) — implementation matching the architecture
described in the project report: PHP + MySQL + HTML/CSS/JS web app, with a
hybrid Python (Scikit-learn) recommendation engine served over an HTTP API.

## Architecture

- `database/schema.sql` — MySQL schema (users, pathways, academic_records, recommendations)
- `ml/generate_data.py` — generates a synthetic ~3,000-row baseline training dataset (swap for real KNEC/school data later)
- `ml/train_model.py` — trains & benchmarks Decision Tree, Random Forest, KNN; saves the Decision Tree (content-based) and a scaled KNN (collaborative) as production models
- `ml/api.py` — Flask API: `POST /predict` (hybrid content-based + collaborative recommendation with explanation), `POST /retrain` (FR8), `GET /health`
- `src/config/` — DB connection, session + CSRF helpers, ML API client
- `src/controllers/` — auth, student, admin logic
- `public/` — pages (login, register, student dashboard, admin dashboard) + assets
- `tests/` — pytest suite covering the ML API and the full PHP web flow (register → submit → recommend → admin visibility)
- `database/migrations/` — incremental schema changes for databases already imported from an earlier `schema.sql`

## Recommendation engine

Hybrid model per report Objective 2 / Section 2.4.3:
- **Content-based (70% weight):** Decision Tree on the student's own scores + stated interest — interpretable, drives the explanation text (FR5).
- **Collaborative (30% weight):** scaled KNN (k=25) over the training population — surfaces what similar students' profiles led to, e.g. "62% of students with a similar academic profile also pursued STEM."
- Both probability distributions are blended; the response includes the blended result plus each component's individual prediction for transparency.

## Setup (XAMPP)

1. Start Apache and MySQL in the XAMPP control panel.
2. Import the schema:
   ```
   C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql
   ```
3. Create an admin account (students self-register via the UI):
   ```powershell
   $hash = C:\xampp\php\php.exe -r "echo password_hash('YourPassword', PASSWORD_DEFAULT);"
   "INSERT INTO career_system.users (full_name, email, password_hash, role) VALUES ('Admin','admin@career.local','$hash','admin');" | C:\xampp\mysql\bin\mysql.exe -u root
   ```
4. Install Python deps and train the baseline model:
   ```
   pip install scikit-learn pandas numpy joblib flask pymysql pytest requests
   python ml\generate_data.py
   python ml\train_model.py
   ```
5. Start the ML API (must be running for recommendations and retraining to work):
   ```
   python ml\api.py
   ```
6. Visit `http://localhost/career_system/public/login.php`

## Running tests

With Apache, MySQL, and `ml\api.py` all running:
```
pytest tests\ -v
```
Covers: ML predict/retrain endpoints, input validation, CSRF rejection, role-based access control (student blocked from admin dashboard), and the full register → submit → recommend → admin-report flow over real HTTP requests.

## Security

- Passwords hashed with `password_hash` (bcrypt), verified with `password_verify`.
- All POST forms (login, register, student submission, admin retrain) require a per-session CSRF token, validated with `hash_equals`.
- All SQL is parameterized via PDO prepared statements.
- Role-based access enforced server-side (`require_role`) on every protected page, not just hidden in the UI.
- Login lockout: 5 failed attempts locks the account for 5 minutes (`failed_login_attempts` / `locked_until` columns). Lock times are written/read as explicit UTC so PHP and MySQL clock/timezone settings can't desync the lockout window.

Still not implemented: encryption at rest for stored academic records, and a formal Data Protection Act compliance review — flagged as outstanding against the report's security requirements (Section 2.8.2 / 4.5.2). These are intentionally left for the next phase, see "Known gaps" below.

## Model performance (current run, see `ml/model/metrics.json` for latest)

| Model | Accuracy | Precision (macro) | Recall (macro) | F1 (macro) |
|---|---|---|---|---|
| Decision Tree (content-based, production) | ~84-85% | ~84-85% | ~84% | ~84% |
| Random Forest (benchmark) | ~88-89% | ~88-89% | ~88% | ~88% |
| KNN (benchmark, also reused as the collaborative component) | ~67% | ~68% | ~67% | ~67% |

Decision Tree is used as the content-based half of the hybrid for
interpretability (FR5, Section 3.4.2); the standalone KNN benchmark above is
unweighted distance voting, distinct from the scaled k=25 collaborative
model used in `/predict`.

## FR8: admin-triggered retraining

The admin dashboard has an "Update the AI Model Now" button. It calls `POST /retrain`,
which pulls all accumulated real student submissions (joined with the
recommendation they were given) from MySQL, combines them with the synthetic
baseline dataset, retrains both the Decision Tree and collaborative KNN, and
hot-reloads them into the running Flask process — no restart required.

## Admin module

The admin dashboard ("Administrator Module", FR7) shows:
- Usage totals (registered students, recommendations issued)
- Pathway breakdown across all recommendations
- **Full registered-student roster** — every student, including those who haven't submitted any data yet, with submission count and latest pathway
- Recent recommendations feed
- The FR8 retrain trigger

## Known gaps / next steps

- **Real dataset** — model is trained on synthetic data plus whatever real submissions accumulate through the app; not yet validated against actual KNEC/school records (Section 3.3.3).
- **UAT tooling** — no in-app feedback/survey mechanism for the user acceptance testing described in Section 3.5.
- **No deployment/hosting setup** — runs locally via XAMPP only; report describes phased deployment to schools.
- **Diagrams** (DFDs, ERD, use case diagrams) exist only in the report, not as generated artifacts in this repo.
- **Security hardening incomplete** — see Security section above.
