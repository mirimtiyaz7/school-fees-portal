# School Fees and Collections Portal

A Laravel and PostgreSQL portfolio project by Imtiyaz Ahmad Mir. Accountants record payments against student fees. Parents can see their own children's balances and download numbered plain text receipts.

## Run locally

Use PHP 8.2 or newer (PHP 8.4 recommended), Composer 2 and PostgreSQL. Enable the PHP extensions required by Laravel, plus pdo_pgsql. For XAMPP, use `php --ini` to locate the configuration used by the command line, then enable `extension=pdo_pgsql`. This app uses Blade and plain CSS and needs no Node build.

1. Extract the archive. Open a terminal inside the folder containing `artisan`.
2. Create a PostgreSQL database named `school_fees` using pgAdmin or `createdb -U postgres school_fees`.
3. Copy `.env.example` to `.env`. In Windows PowerShell, run `Copy-Item .env.example .env`. On macOS or Linux, use `cp .env.example .env`.
4. Set `DB_USERNAME`, `DB_PASSWORD`, `DB_HOST` and `DB_PORT` in `.env` to match your local PostgreSQL instance. Quote passwords containing spaces or a hash sign.
5. Run these commands from the project folder.

```sh
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open http://127.0.0.1:8000 in your browser.

## Try the demo

All seed data is fictional. The shared demo password is `SchoolDemo2026!`.

| School | Accountant | Parent |
| --- | --- | --- |
| Cedar Valley School | accountant@cedar.test | parent@cedar.test |
| Willow Grove School | accountant@willow.test | parent@willow.test |

1. Sign in as the Cedar accountant. Mina Shah starts with ₹12,000 due.
2. Enter 5000 under Mina and choose **Record payment**. Her balance becomes ₹7,000. Download the receipt from **Payment receipts**.
3. Sign out and sign in as the Cedar parent. You can download the same receipt. Mina is the only student visible to this parent.
4. Sign in as the Willow accountant. You see a separate set of students. Opening Mina's receipt URL returns 404.

Use `parent2@cedar.test` to try the second Cedar family. Run the demo in a local or isolated environment with fictional data.

## How payment recording works

* The database stores amounts in integer paise. Balances are calculated from the fee and posted payments.
* A PostgreSQL transaction locks the student account before checking the balance and inserting a payment. Different payment requests for the same account are serialized.
* Each rendered payment form has a UUID. A repeated POST with the same account, UUID and amount returns the original receipt number. Reusing that UUID with a different amount returns 409.
* Queries scope records to the signed in user's school. Parent queries also require the matching parent ID. Composite foreign keys prevent cross school links between payments, accounts and staff.
* This version records money received outside the portal. It generates a numbered text receipt on download.

## Run the tests

Create a separate, disposable database named `school_fees_test` on your PostgreSQL server, using the same credentials as the local app. PHPUnit uses this database for migrations and test fixtures.

```sh
php vendor/bin/phpunit
```

Tests cover login, logout, partial payments, repeated submissions, conflicting retries, overpayments, fractional rupees, parent permissions and school isolation. The GitHub Actions workflow starts a PostgreSQL 18 service and runs this suite on pushes and pull requests.

## Put it on GitHub

After installing dependencies, include the generated `composer.lock` when you commit. The included ignore rules exclude credentials, dependencies and runtime data. Create an empty GitHub repository named `school-fees-portal`. From the extracted project folder, run the following, substituting your own GitHub username.

```sh
git init
git add .
git commit -m "Add school fee collections portal"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/school-fees-portal.git
git push -u origin main
```

Open the repository's Actions tab to see the test result.
