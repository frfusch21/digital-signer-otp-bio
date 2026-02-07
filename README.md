# Digital Signer OTP + Biometric Liveness (Laravel-based Research Prototype)

This repository provides a Laravel-based prototype for evaluating digital signing assurance by combining:

1. PKI-style digital signatures (simulated)
2. OTP verification (account/channel possession)
3. Biometric liveness challenge (human presence proof)

## Is there a UI?

Yes. `GET /` renders a beginner-friendly UI where you can:

- run the 6-step signing flow,
- save per-attempt empirical records,
- run scenario experiments from stored attempts,
- export result tables to CSV.

## Workflow

1. Document Preparation
2. Signature Initiation
3. OTP Verification
4. Biometric Liveness
5. Digital Signature Application
6. Verification

## Empirical evaluation model (now implemented)

The experiment output is no longer synthetic. Metrics are computed from stored attempt-level records in `signing_attempts`.

### Per-attempt stored labels/outcomes

Each attempt stores:

- `verification_configuration` (`configuration_a_otp_only`, `configuration_b_liveness_only`, `configuration_c_otp_plus_liveness`)
- `threat_scenario` (`legitimate_user`, `photo_spoofing`, `video_replay`, `otp_channel_compromise`)
- check statuses (`otp_status`, `liveness_status`, `signature_status`)
- derived label:
  - `actor_label`: `legitimate_user` or `attacker`
  - `outcome_label`: `accepted` or `rejected`

Outcome derivation rules:

- Config A (OTP only): accepted if `otp_status && signature_status`
- Config B (Liveness only): accepted if `liveness_status && signature_status`
- Config C (OTP + Liveness): accepted if `otp_status && liveness_status && signature_status`

### Metric formulas used

For each **configuration + scenario** group:

- `TP`: legitimate accepted
- `FN`: legitimate rejected
- `FP`: attacker accepted
- `TN`: attacker rejected

Then:

- `TAR = TP / (TP + FN) * 100`
- `FAR = FP / (FP + TN) * 100`
- `Attack Success Rate = FP / (FP + TN) * 100`
- `Verification Failure Rate = FN / (TP + FN) * 100`
- `completion_time_seconds` = average completion time over attempts in the group

If denominator is zero, metric is returned as `null` (`N/A` in UI).

## Main routes

- `GET /` : UI
- `POST /signing/initiate`
- `POST /signing/otp/verify`
- `POST /signing/liveness/verify`
- `POST /signing/apply`
- `POST /signing/verify`

Empirical experiment routes:

- `POST /experiments/attempts` : store one attempt with labels/outcome computed server-side
- `GET /experiments/attempts` : list recent attempts
- `POST /experiments/run` : aggregate TAR/FAR/ASR/VFR from stored attempts

## Laravel structure completeness

Included common Laravel structure files:

- `artisan`
- `bootstrap/app.php`
- `bootstrap/cache/.gitignore`
- `public/index.php`
- `app/Console/Kernel.php`
- `app/Http/Kernel.php`
- `app/Exceptions/Handler.php`
- `config/app.php`
- `config/database.php`

## Database

Default `.env.example` is configured for MySQL:

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=digital_signer_otp_bio`
- `DB_USERNAME=root`
- `DB_PASSWORD=`

## Run locally

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```
