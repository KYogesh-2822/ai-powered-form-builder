# Engineering Decisions

## Assumptions

- React satisfies the brief's "Livewire and/or React" requirement. The owner explicitly asked for the latest Laravel/React stack, so this uses Laravel 13 and React 19 rather than freezing the assignment's older Laravel 10/11 examples.
- MySQL 8 is the production database. SQLite is used only for fast isolated tests; Docker pins MySQL 8.4 and migrations avoid database-specific JSON functions.
- A form is owned by one user in this scope. Every authenticated query and mutation is owner-scoped through relationships or policies. An organization/tenant table can be added without changing public form IDs.
- AI and imports are asynchronous even for small inputs. Consistent behavior and auditability are worth the small queue latency.
- Imported source files may contain sensitive data, so they are private. Public submission files are intentionally public in this assignment to keep download handling small; production can replace this disk with signed object-storage URLs.
- The Git remote, repository owner, production host, and deployment credentials are deliberately unknown. No remote or live URL is invented.

## Core architecture choices

### JSON schema as the domain boundary

The same normalized schema drives the canvas, raw editor, AI contract, import result, public renderer, and Laravel validation rules. This prevents the common failure where browser validation and server validation drift. The trade-off is that changing the schema contract requires deliberate migration/version support; the explicit `version` property provides that extension point.

### Laravel service boundaries instead of a separate AI service

The brief lists FastAPI as a positive signal, not a requirement. A second service would add deployment, tracing, authentication, and failure modes without meaningful model-specific computation. `OpenAiFormGenerator` is already a REST boundary and can be extracted behind the same interface when independent scaling, Python-only parsing, OCR, or provider routing justifies it.

### Deterministic import before AI inference

DOCX headings/lists and documented XLSX columns are deterministic and inexpensive. They are parsed without sending potentially sensitive documents to an LLM. Users review ambiguous mappings before apply. The current trade-off is lower recall for visually complex documents; a future confidence score can selectively queue only ambiguous blocks for AI classification.

### Queue all slow and unreliable external work

AI and imports use dedicated Horizon queues, retry/failure states, bounded timeouts, and audit metadata. Web requests only validate, persist an automation record, and dispatch. Separate automation workers have more memory and longer timeouts so normal jobs are not starved.

## Part D improvements

### 1. Immutable version history and rollback

**User problem:** AI edits, imports, and manual changes can remove valuable work. A mutable `version` integer alone does not provide recovery.

**Implementation:** Every content change locks the form row, increments its monotonic version, and stores a complete snapshot with actor, source, summary, and timestamp. Restoring never rewrites history; it creates a new version from the selected snapshot. AI and reviewed imports go through the same service.

**Trade-offs:** Full JSON snapshots use more storage than diffs, but retrieval and rollback are simple, reliable, and fast for the 200-field schema limit. The UI intentionally shows only the latest 15.

**With more time:** Add paginated history, visual schema diffs, retention controls, named releases, and optimistic concurrent-edit tokens.

### 2. Conditional branching with matching server validation

**User problem:** Long forms become irrelevant and frustrating when everyone sees every question. Client-only hiding is unsafe because hidden required fields can still fail server validation or malicious clients can bypass logic.

**Implementation:** The inspector configures a source field, operator, and expected value. Conditions are normalized only when their source key exists and is not self-referential. Both React rendering and server rule compilation evaluate the condition; hidden answers and hidden file uploads are excluded.

**Trade-offs:** The initial operators are equals, not-equals, and contains. Conditions address fields by stable key, so deleting or changing the source key clears an invalid condition on the next validated save.

**With more time:** Add AND/OR groups, numeric/date comparisons, section-level branching, dependency visualization, and cycle detection for calculated fields.

### 3. Idempotent, abuse-resistant public submissions

**User problem:** Double-clicks, network retries, and simple bots inflate response counts and may duplicate uploaded files.

**Implementation:** Each browser render creates a UUID submission token protected by a database unique index. Replays return the existing success result. Public posts also have route throttling, a honeypot, a minimum-human-time check, strict schema validation, HMAC-only IP storage, and normal CSRF protection.

**Trade-offs:** Timing and honeypots stop low-effort automation, not determined attackers. IPs are not reversible, which improves privacy but limits manual abuse investigation.

**With more time:** Add Turnstile behind a risk threshold, upload malware scanning, per-form rate policy, signed public API tokens, and an abuse-review queue.

### 4. Redis-cached published forms with automatic invalidation

**User problem:** Public form reads can be much higher than builder traffic, and most published schemas are unchanged between requests.

**Implementation:** Public render payloads are cached for five minutes behind a hashed slug key. A model observer invalidates current and previous slug keys after save/delete. Submission writes always query the authoritative database and re-normalize the schema.

**Trade-offs:** This is a small cache-aside layer rather than a full compiled-schema cache. A Redis outage follows the configured Laravel cache behavior and operational monitoring is required.

**With more time:** Cache compiled rule metadata, add tagged tenant invalidation, collect hit ratios, and serve immutable versioned public payloads through a CDN.

## Security and privacy decisions

- API keys stay in environment variables and all AI calls are server-side. OpenAI storage is disabled for requests.
- The provider receives a one-way HMAC safety identifier rather than an email or database ID.
- Automation and import routes require a verified authenticated owner; bound automation IDs are re-authorized through their form.
- Imported documents live on the private disk. File type and size are validated before queuing.
- Public submission IPs are HMAC hashes, not plaintext. User agents are length-limited.
- Horizon access is deny-by-default in production unless the authenticated email appears in `HORIZON_ALLOWED_EMAILS`.
- Raw schema input is normalized with field-count and length limits before persistence. Unsupported types and invalid references fail closed.

## Operational trade-offs

- Docker uses separate app, Nginx, Horizon, scheduler, MySQL, and Redis services. This is more verbose than a single container but mirrors production responsibilities and lets workers scale separately.
- The compose fallback application key is explicitly local-only so first boot works from `.env.example`; production must inject a unique secret.
- CI uses SQLite for deterministic speed. MySQL-specific confidence comes from real migration runs, the generated SQL export, and the pinned Docker service; a future CI matrix should run the feature suite against MySQL 8 as well.
- CSV export streams and chunks records, avoiding memory growth, while full-text answer search uses a denormalized text column. At very large scale this should move to MySQL FULLTEXT or a search service.

## What I would build with two more weeks

1. Organization workspaces with invitations, roles, per-tenant quotas, and tenant-keyed cache/queue tags.
2. Autosave with optimistic locking, conflict UI, undo/redo, and visual diffs between versions.
3. Completion/drop-off analytics using privacy-aware view, step, and completion events.
4. Webhooks and signed public submission APIs with delivery retries, replay tools, and secret rotation.
5. Private S3-compatible uploads, signed downloads, antivirus scanning, retention policies, and deletion workflows.
6. OCR and confidence-scored import classification for scans and irregular tables, with selective AI fallback.
7. Accessibility audit, keyboard drag-and-drop, screen-reader announcements, localization, and translated AI editing.
8. A MySQL/Redis CI service matrix, browser end-to-end tests, load tests, tracing, queue alerts, backup/restore drills, and deployment smoke checks.
