# Wasabi dual storage & order documents — reference

Use this doc to pick up context in another chat or for onboarding. It summarizes what was implemented and how to configure/troubleshoot Wasabi in this Laravel app.

## Goals

1. **Supporting documents** uploaded during the **finalisation workflow** (Finalize page, collection `supporting_documents`, order status `documents_required` or `finalized`) are stored in **two places**:
   - **Wasabi** — primary copy (`disk = wasabi`).
   - **Local private disk** — short-lived cache (`local_disk = local`, `local_path = …`) for ~14 days.

2. A **scheduled job** removes **only the local copy** after retention rules are met; **Wasabi is not deleted** by this job.

3. **Composer**: `league/flysystem-aws-s3-v3` (and AWS SDK) is required for the S3 driver.

## Code touchpoints

| Area | Purpose |
|------|---------|
| `config/filesystems.php` | `wasabi` disk: S3-compatible driver, `WASABI_*` env vars. |
| `app/Http/Controllers/MediaController.php` | Dual-write for qualifying uploads; validates non-empty path after `storeAs`; delete local + cloud on destroy. |
| `app/Models/Media.php` | `local_disk`, `local_path`, `local_cached_at`, `local_deleted_at` fillable + datetime casts. |
| `app/Jobs/CleanupLocalOrderMediaJob.php` | Deletes local cached files when eligible; sets `local_deleted_at`. |
| `routes/console.php` | `Schedule::job(new CleanupLocalOrderMediaJob)->daily();` |
| `database/migrations/*_add_local_cache_columns_to_media_table.php` | Adds local cache columns on `media`. |
| `phpunit.xml` | Test DB: `DB_HOST=mysql`, `DB_USERNAME=sail`, `DB_PASSWORD=password` (Sail). |
| Tests | `tests/Feature/MediaDualStorageTest.php`, `tests/Feature/CleanupLocalOrderMediaJobTest.php`, `tests/Unit/WasabiFilesystemDiskTest.php` |

## When dual storage runs

Dual storage applies when **all** are true:

- `collection === 'supporting_documents'`
- Order `status` is `documents_required` **or** `finalized`

Otherwise uploads use `config('filesystems.default')` only (typically `local`).

## Retention logic (cleanup job)

`CleanupLocalOrderMediaJob` processes rows with `local_path` set and `local_deleted_at` null.

For each row it compares **now − 14 days** to:

- `local_cached_at` must be **older than** that cutoff, and
- **Activity window**: the later of `order.updated_at` (if after media `created_at`) vs `media.created_at` must also be **older than** that cutoff.

Then it deletes the file on `local_disk` / `local_path` and sets `local_deleted_at`.

## `.env` — Wasabi (correct shape)

**Region** is an AWS-style code (e.g. `eu-central-1`), **not** a hostname.

**Endpoint** must be a full URL including `https://`.

**Bucket** must match an **existing** bucket name in that region. If Laravel fails to read the value, quoting helps Dotenv parse reliably:

```env
WASABI_ACCESS_KEY_ID=...
WASABI_SECRET_ACCESS_KEY=...
WASABI_DEFAULT_REGION=eu-central-1
WASABI_BUCKET="your-bucket-name"
WASABI_ENDPOINT=https://s3.eu-central-1.wasabisys.com
WASABI_URL=https://s3.eu-central-1.wasabisys.com
WASABI_USE_PATH_STYLE_ENDPOINT=true
```

After changing `.env`:

```bash
vendor/bin/sail artisan config:clear
vendor/bin/sail artisan cache:clear
```

If queue workers run Wasabi-related jobs, restart them:

```bash
vendor/bin/sail artisan queue:restart
```

## Smoke test (Tinker)

```bash
vendor/bin/sail artisan tinker --execute='use Illuminate\Support\Facades\Storage; Storage::disk("wasabi")->put("smoke-test.txt", "ok");'
```

Then confirm the object appears in the Wasabi console for that bucket/region.

## Troubleshooting (what we hit)

| Symptom | Likely cause |
|---------|----------------|
| `Custom endpoint ... was not a valid URI` | `WASABI_ENDPOINT` missing `https://` or wrong format. |
| `Argument #2 ($bucket) must be of type string, null given` | `WASABI_BUCKET` not loaded (try quoting: `WASABI_BUCKET="name"`); run `config:clear`. |
| `NoSuchBucket` | Bucket name wrong, bucket in another region, or bucket not created. Endpoint must match bucket region. |
| `media.path = "0"` / bad rows | Wasabi write failed; upload path should now throw instead of saving invalid paths (verify current `MediaController`). |
| Tests fail with MySQL `Connection refused` to `127.0.0.1` | Use Sail MySQL host in `phpunit.xml` (`DB_HOST=mysql`) and ensure DB exists. |

## Security note

Never commit real access keys or secrets. Rotate keys if they were pasted into chat or committed. Use `.env` locally and secret management in production.

## Related UI

Finalize page uploads supporting docs via `route('media.upload')` with `collection: supporting_documents` (`resources/js/Pages/Orders/Finalize.jsx`).
