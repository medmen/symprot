# Symprot — Data Flow, Processing Steps, and Security Measures

Last updated: 2025-12-16 22:38

This document explains how data moves through the system from the moment a client requests processing of a protocol file to when the final output is displayed. It also documents the security measures currently implemented and recommended hardening steps.


## High-level Overview

- A client triggers processing via the controller action `ProtocolController::index()` on route `/process_upload` (HTTP GET with query params).
- The controller validates the input path (in the configured `app.uploads_dir`) and creates a background job via `JobManager` under `var/procjobs/<jobId>`.
- A background Symfony Console command `app:process-protocol-job <jobId>` picks up the job, reads the payload, converts and formats the protocol, and writes the final HTML to `output.html` within the job directory.
- The UI page displays a progress bar and polls the status endpoint `/process_status/{id}` until the job is complete; then it fetches `/process_output/{id}` and injects the resulting HTML into the page.

```
Client
  │
  │ 1. GET /process_upload?path=<file>&geraet=<...>&format=html|md
  ▼
ProtocolController::index()
  ├─ Resolve uploads dir + file path
  ├─ Create job in var/procjobs/<jobId>
  ├─ Start background process: php bin/console app:process-protocol-job <jobId>
  └─ Render protocol/index.html.twig with jobId
  
Browser (CSP-safe external JS module)
  ├─ Polls GET /process_status/{id}
  └─ When done: GET /process_output/{id} → injects HTML

Background worker: app:process-protocol-job
  ├─ Read payload.json
  ├─ ConverterContext::handle(...) → serialized data (reports progress 20-60%)
  ├─ FormatterContext::handle(...) → final HTML/MD (progress 60-90%)
  ├─ Write output.html
  └─ JobManager::complete(...)
```


## Components and Responsibilities

- Controller: `App\Controller\ProtocolController`
  - Route: `/process_upload` (GET)
  - Creates job; starts background process using `Symfony\Component\Process\Process` and `PhpExecutableFinder`.
  - Writes initial status (1%).

- Job lifecycle service: `App\Service\JobManager`
  - Job directory: `var/procjobs/<jobId>/`
  - Files:
    - `payload.json` — input parameters (device, filename, mimetype, format)
    - `status.json` — current status, message, percent, timestamps, error
    - `output.html` — final HTML content

- Background worker command: `App\Command\ProcessProtocolJobCommand`
  - Route name: `app:process-protocol-job` (Console)
  - Reads payload, validates source file in uploads dir, converts and formats.
  - Periodically updates status via `JobManager::update()`; on success, calls `complete()`; on failure, calls `fail()`.

- Job API controller: `App\Controller\ProtocolJobController`
  - `/process_status/{id}` → returns JSON with `status`, `percent`, `message`, `error` (and includes a watchdog fallback for stuck jobs)
  - `/process_output/{id}` → returns `text/html` with the contents of `output.html` (202 if not yet available)

- Frontend: `assets/protocol-polling.js` (CSP-safe, no inline JS)
  - Auto-initialized by `assets/app.js` on pages including `#job-root` marker.
  - Updates progress bar; loads final HTML and injects it into `#protocol_output`.


## Detailed Processing Steps

1. Client request
   - Endpoint: `GET /process_upload?path=<relative-path>&geraet=<name>&format=html|md`.
   - The controller resolves the absolute path: `<uploads_dir>/<path>` (from `app.uploads_dir`).

2. Input checks
   - If the `path` is empty or the file is missing/unreadable, the controller logs an error and renders an error response.
   - A short retry loop (up to ~500ms) accommodates slow filesystems.

3. Job creation
   - `JobManager::createJob(payload)` creates `var/procjobs/<jobId>/` and writes `payload.json` and initial `status.json` (status `queued`, percent `0`).

4. Start background worker
   - Controller resolves the PHP CLI binary via `PhpExecutableFinder` and starts:
     - `php bin/console app:process-protocol-job <jobId>`
   - Sets timeout to `null` (worker enforces its own deadline) and writes an early status update (1%).

5. Conversion and formatting (worker)
   - Loads payload, validates source file exists inside `app.uploads_dir`.
   - Updates status to 5% (`Starte Verarbeitung`), then to 20% (`Datei eingelesen`).
   - Calls `ConverterContext::handle(..., progressCallback)`; callback maps converter progress to overall 20–60%.
   - Calls `FormatterContext::handle(...)` and updates to 90%.
   - Writes `output.html` and marks job `done` (100%).

6. Displaying the result
   - The client page polls `/process_status/{id}` until `status === 'done'` (or `failed`).
   - On `done`, the page calls `/process_output/{id}` and injects the returned HTML into the page.

7. Cleanup
   - The worker currently logs that it would delete the input XML; actual deletion is commented out and can be enabled as a policy choice.


## Data Stored on Disk

- `var/procjobs/<jobId>/payload.json`
- `var/procjobs/<jobId>/status.json`
- `var/procjobs/<jobId>/output.html`

Retention is not currently enforced; consider a scheduled cleanup task for old jobs.


## Endpoints Summary

- `/process_upload` (GET) — starts processing for a file found under uploads dir, returns a page with progress bar.
- `/process_status/{id}` (GET, JSON) — returns job state: `queued` | `running` | `done` | `failed` plus percentage and message.
- `/process_output/{id}` (GET, HTML) — returns final HTML; `202 Accepted` until the output is available.


## Security Measures (Implemented)

- Content Security Policy (CSP)
  - No inline JavaScript in the processing page; an external ES module `assets/protocol-polling.js` is loaded via `{{ importmap('app') }}` with a nonce in `templates/base.html.twig`.

- Background execution hardening
  - Uses `Symfony\Component\Process\Process` with explicit PHP binary resolution.
  - Defensive logging around `start()` and immediate status nudge for UI visibility.

- Uploads and file path handling
  - Absolute path built from configured `app.uploads_dir`.
  - Existence and readability checks with a short retry loop.

- Job isolation on disk
  - Per-job directory under `var/procjobs/<jobId>` to isolate payload, status, and output.

- Watchdog for stuck jobs
  - `/process_status/{id}` marks long-stuck jobs as `failed` after defined grace periods (e.g., queued > 60s; <5% for > 5min).

- Logging
  - Structured logging across controller and worker; dev/prod Monolog config tuned to reduce noise (e.g., filtering `request` channel in dev).


## Security Recommendations (Further Hardening)

1. Input validation and authorization
   - Ensure only authenticated/authorized users can trigger processing and access results.
   - Validate `path` strictly as a filename relative to uploads dir; reject sequences attempting traversal (e.g., `..`, leading `/`).

2. HTTP method and CSRF
   - Consider changing `/process_upload` to POST (form or XHR) and include CSRF protection for state-changing operations.

3. MIME/type checks
   - Enforce a whitelist of allowed MIME types/extensions (e.g., XML only) before job creation.

4. Output sanitization
   - `output.html` is injected as trusted HTML. If user-provided content can appear inside, sanitize at generation and/or use a sanitizer before injecting.

5. Rate limiting and quotas
   - Add rate limiting per user/IP to prevent abuse (DoS) and disk exhaustion.

6. Retention and cleanup
   - Implement automated cleanup of old job directories (e.g., cron or Messenger scheduled task) and limit total disk usage.

7. Permissions and execution environment
   - Ensure the web server/FPM user has only the minimum permissions required to execute PHP CLI and write to `var/`.
   - Verify `proc_open` and related functions are allowed for FPM; if disabled, background processing will fail.

8. Observability
   - Add correlation IDs (e.g., include `jobId` in log context everywhere) and dashboards for queued/running/failed jobs.

9. Error handling UX
   - Return clearer error pages and user-facing guidance when jobs fail; include a support code (the `jobId`).

10. Transport security
   - Enforce HTTPS and set strict CSP headers (script-src 'self' plus nonces), `X-Content-Type-Options: nosniff`, `Referrer-Policy`, and `Permissions-Policy`.


## Operations & Troubleshooting

- To verify CLI:
  - `php -v`
  - `php bin/console -V`
  - `php bin/console app:process-protocol-job <jobId>`

- Check logs:
  - `var/log/dev.log` or `var/log/prod.log` depending on environment.

- Inspect job directory:
  - `var/procjobs/<jobId>/payload.json`
  - `var/procjobs/<jobId>/status.json`
  - `var/procjobs/<jobId>/output.html`


## References (Code Paths)

- Controller: `src/Controller/ProtocolController.php`
- Background worker: `src/Command/ProcessProtocolJobCommand.php`
- Job service: `src/Service/JobManager.php`
- Job APIs: `src/Controller/ProtocolJobController.php`
- Frontend module: `assets/protocol-polling.js` and `assets/app.js`
- Template: `templates/protocol/index.html.twig`
- Monolog config: `config/packages/monolog.yaml`
