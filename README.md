# Digital Signer OTP + Biometric Liveness (Laravel-based Research Prototype)

This repository provides a Laravel-based prototype to support your paper on strengthening digital signing by combining:

1. PKI-style digital signatures (simulated)
2. OTP verification (account/channel possession)
3. Biometric liveness challenge (human presence proof)

## Is there a UI?

Yes. The root route (`GET /`) now renders a simple beginner-friendly web UI (`resources/views/signing/index.blade.php`) that:

- explains the 6-step signing workflow,
- provides a **Run Full Flow** button,
- calls each backend endpoint in order,
- prints every API response so you can show the end-to-end behavior during demos/paper writing.

If you request JSON (`Accept: application/json`), the same route returns workflow JSON for API usage.

## Beginner Flow Explanation

Think of the system as checking **three things** before saying a signature is trustworthy:

1. **Do you control the account/channel?** → OTP check
2. **Are you physically present right now?** → liveness challenge check
3. **Is the document cryptographically consistent?** → signature + hash verification

### 6 Workflow Steps

1. **Document Preparation**  
   The document to be signed is loaded.

2. **Signature Initiation**  
   Backend prepares the process: generates OTP and liveness challenge.

3. **OTP Verification**  
   User enters OTP; system checks channel/account possession.

4. **Biometric Liveness**  
   User performs challenge (e.g., blink, turn head, smile) within time limit.

5. **Digital Signature Application**  
   System applies digital signature metadata and document hash.

6. **Verification**  
   System re-checks the hash to ensure integrity/non-repudiation logic.

## Architecture Components

- **User Interface Module**: Blade UI at `/`
- **OTP Service Module**: `OtpService`
- **Biometric Liveness Module**: `LivenessService`
- **Signature Module**: `SignatureService`
- **Audit Logging**: `SigningAttempt` model + migration
- **Experiment Engine**: `ExperimentService` + `ExperimentController`

## Experimental Methodology Support

- Config A: OTP only
- Config B: Liveness only
- Config C: OTP + Liveness

Threat scenarios represented:
- Legitimate user
- Photo spoofing
- Video replay
- OTP channel compromise

Metrics available in experiment outputs:
- TAR
- FAR
- Attack Success Rate
- Signing Completion Time
- Verification Failure Rate

## Scenario experiment UI (for your paper tables)

The home page now includes a **Run Scenario Experiments** section:

- choose one or more configurations:
  - `configuration_a_otp_only`
  - `configuration_b_liveness_only`
  - `configuration_c_otp_plus_liveness`
- click **Run Experiments** to call `POST /experiments/run`
- view metrics in a table (TAR, FAR, attack success rate, completion time, verification failure rate)
- filter by scenario (`legitimate_user`, `photo_spoofing`, `video_replay`, `otp_channel_compromise`)
- click **Export CSV** to download results and include them in your paper analysis

## Troubleshooting: "Cannot read properties of undefined (reading 'otp')"

If you see this together with `CSRF token mismatch`, the first POST (`/signing/initiate`) failed with HTTP 419, so the frontend never received `init.otp`.

Why it happens:
- Laravel web POST routes require a valid CSRF token.
- If fetch requests do not send `X-CSRF-TOKEN`, Laravel rejects the request.

What is fixed in this repo:
- The Blade UI now includes `<meta name="csrf-token" content="{{ csrf_token() }}">`.
- The JavaScript now sends `X-CSRF-TOKEN` and `credentials: 'same-origin'` on all POST requests.
- Added response guards, so if a request fails, the UI shows a readable error instead of crashing at `init.otp`.

## Routes

- `GET /` : beginner UI (or workflow JSON)
- `POST /signing/initiate`
- `POST /signing/otp/verify`
- `POST /signing/liveness/verify`
- `POST /signing/apply`
- `POST /signing/verify`
- `GET /experiments`
- `POST /experiments/run`


## Laravel structure completeness

To address missing-core-file feedback, this repository now includes commonly expected Laravel files/directories:

- `bootstrap/cache/.gitignore`
- `app/Console/Kernel.php`
- `app/Http/Kernel.php`
- `app/Exceptions/Handler.php`
- `config/app.php`
- `config/database.php`

> Note: this is still a lightweight research prototype scaffold; once dependencies are installed in a normal environment, these files support standard Laravel command/runtime expectations.

## About artisan

Laravel does **not** have an "artisan folder". It has an executable file named `artisan` in the project root.

- Run commands like `php artisan serve`, `php artisan migrate`, `php artisan route:list`.
- In this repository, I added the standard root `artisan` file plus `bootstrap/app.php` and `public/index.php` so the project layout now follows normal Laravel expectations.

Default database in `.env.example` is now **MySQL** (`DB_CONNECTION=mysql`).

## Run locally

```bash
composer install
php artisan migrate
php artisan serve
```

Then open `http://127.0.0.1:8000`.

## Note about this environment

Dependency fetch from Packagist may be blocked in this execution environment. If install fails here, run the above commands in a normal network-enabled machine.
