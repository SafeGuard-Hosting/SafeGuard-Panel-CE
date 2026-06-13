# SafeGuard Panel — Security & Production Go-Live

This document records the security architecture of SafeGuard Panel, the
penetration testing it has undergone, its resistance to the known exploit
classes that have hit competing control panels, and the checklist that must be
completed before opening the panel to customers.

_Last updated: 2026-06-11. Owner: SafeGuard Hosting Inc._

---

## 1. Security controls implemented

### Authentication & sessions
- **JWT (HS256)** with the signing method pinned at validation (`alg=none`,
  signature-stripping, and RS256/HS256 confusion all rejected).
- **Server-side session table**: every JWT carries a `jti` and is checked
  against the `sessions` table on **every request** — a terminated, revoked,
  or forged-but-unsessioned token is rejected immediately. (This is what makes
  token theft survivable: revoke the session and the token is dead.)
- **`JWT_SECRET`** is never a hardcoded default — the installer writes a 256-bit
  random secret; if unset, the panel generates a random per-boot secret rather
  than using a known string.
- **Password hashing**: bcrypt (cost 12) + a server-side `PASSWORD_PEPPER` that
  lives only in the environment, so a stolen database cannot be cracked offline.
- **Constant-time login**: the "user not found" path runs a dummy bcrypt at the
  same cost, closing the username-enumeration timing side-channel.
- **2FA (TOTP)** with one-time backup codes; failed second-factor attempts count
  toward the lockout tiers.
- **Brute-force defense**: 5/min login rate limit **plus** layered lockout tiers
  (5 fails → 60s, 10 → 15min, 20 → 1h), each emitting a security event.
- **Concurrent-session cap** (10); suspend revokes all sessions.
- **Support-access keys** (time-boxed, read-only/full, revocable, re-validated
  every request) so support never needs a customer's password.

### Authorization (RBAC)
- Three roles: **Owner > Reseller > User**, enforced by middleware **and** by an
  `owner_id`/`parent_id` scope on every data query.
- Verified against horizontal IDOR (cross-user/cross-tenant read/edit/delete),
  vertical escalation (self role-change, owner-only endpoints), and reseller
  subtree boundaries — all rejected.
- Mass-assignment safe: `id`, `owner_id`, `parent_id`, `role` cannot be set by
  the client; a reseller creating a user has the role force-downgraded.

### Transport & browser hardening
- **CSP** (`default-src 'self'`, no inline script), **HSTS** (preload),
  **X-Frame-Options/frame-ancestors** (clickjacking), **X-Content-Type-Options**,
  **Permissions-Policy**, **Referrer-Policy**, and `no-store` on every API
  response — including error responses.
- **CSRF**: HttpOnly `sg_session` cookie + double-submit `sg_csrf` token on all
  state-changing requests (Bearer tokens, which carry no ambient authority, are
  exempt by design).
- **CORS** pinned to the configured origin — no reflection, no credentials.
- Secure cookie flags: `HttpOnly`, `Secure` (under TLS), `SameSite=Strict`.

### Input handling & injection defense
- **SQL**: 100% parameterized queries; identifier quoting + strict name regexes
  on the DB-engine layer. No string-built SQL anywhere.
- **Command execution**: every `exec.Command` is **argv-style — no shell** — so
  shell-metacharacter injection is structurally impossible. User-influenced
  arguments (git branch, fail2ban jail, docker image, DB/domain names) are
  charset-validated so they can't be parsed as CLI flags.
- **Path traversal**: the per-user file jail rejects `..`, encoded variants,
  backslashes, and absolute paths; archive extraction blocks zip-slip and is
  size-capped against decompression bombs (2 GiB/file, 5 GiB total, 200k
  entries).
- **DNS**: record name/value reject control characters (zone-file injection)
  and are length-bounded; structured types are format-validated.
- **XSS**: API stores data verbatim; React auto-escapes on render; CSP blocks
  inline/injected script as defense-in-depth.

### Privilege separation (the root boundary)
- The panel splits into **two binaries**: `panel-core` runs **unprivileged**
  (user `safeguard`) and holds all the API/UI/DB/business logic; `panel-worker`
  runs as root, contains **zero business logic**, and performs only a small,
  immutable, strictly-typed set of system mutations.
- They communicate over a local Unix socket (`0660 root:safeguard`) gated by
  the kernel-verified peer UID (**`SO_PEERCRED`**). Every privileged file write
  resolves beneath a hardcoded base directory via **`openat2 RESOLVE_BENEATH`**,
  closing the symlink/TOCTOU class; all shelling-out is argv-only (no shell).
- This shrinks the root attack surface from "the whole panel" to ~a dozen
  reviewed operations. (The full internal design document — threat model,
  protocol, op set — is part of the private source repository; researchers
  engaged for testing can request it via support@safeguardpanel.ca.)

### Secrets, data & infrastructure
- Secrets at rest (billing credentials, Imunify license) are **AES-256-GCM**
  encrypted with a master key file (`0600`).
- **Binary integrity check** at startup (`SAFEGUARD_BINARY_HASH`) — refuses to
  run if the binary was tampered with.
- **Per-user PHP-FPM pool isolation** (`open_basedir`, exec functions disabled)
  — the boundary that makes user-uploaded `.php` safe, exactly as on cPanel/Plesk.
- **Body limits** (1 MB JSON / 512 MB multipart) and a **global 300/min rate
  limit** bound request-flood and upload DoS.
- **Audit log** (RBAC-scoped, CSV export) records every meaningful action with
  the real client IP (resolved from `X-Real-IP` only when the peer is the local
  nginx proxy, so it cannot be spoofed).
- Standalone **repair server** on a separate port with its own shared-key auth,
  so the panel can be recovered even when the main service is down.

### Dependencies
- `govulncheck` (Go) reports **zero vulnerabilities** — both reachable and in
  required modules. Run on every dependency change.
- The backend has **3 direct dependencies** (JWT, x/crypto, pure-Go SQLite);
  the shipped binary links no system libraries (CGO disabled).

### Assessed advisories (build-time only, not in the shipped product)
The production artifacts are a static Go binary plus **pre-built** static
frontend assets. Build tooling never runs on a production server.

- **GHSA-gv7w-rqvm-qjhr — esbuild (via Vite, dev dependency).** A build-time
  tool, not part of the deployed panel. The advisory concerns esbuild's **Deno**
  binary-download path; SafeGuard consumes esbuild through **npm** (integrity-
  pinned in the lockfile), so the affected code path is not used, and esbuild is
  never present in production. The patched esbuild (0.28+) is incompatible with
  the current Vite 6 build pipeline, so we hold at the working version and will
  adopt it when Vite ships it upstream. **No production or runtime impact.**

---

## 2. Penetration testing performed

SafeGuard has been subjected to multiple automated and manual assessment passes
against the live API. Findings below were all **fixed and re-verified**.

| Severity | Finding | Status |
|----------|---------|--------|
| Critical | JWT forgery via jti-less token (session check was conditional) | Fixed — session row now required for every JWT |
| High | `golang-jwt` v5.2.1 DoS (CVE GO-2025-3553) | Fixed — upgraded to v5.2.3 |
| High | Client-IP proxy-blindness → panel-wide lockout DoS | Fixed — trusted `X-Real-IP` from loopback only |
| High | Git branch argument injection (`--upload-pack`) → RCE | Fixed — charset validation |
| Medium | Logout was client-side only (cookie/session survived) | Fixed — server-side revocation |
| Medium | Support sessions could read the never-expiring repair key | Fixed — 403 for support sessions |
| Medium | DNS TXT zone-file injection via newline | Fixed — control-char guard |
| Medium | Username enumeration via login timing | Fixed — constant-time dummy bcrypt |
| Medium | Archive decompression bomb (uncapped extraction) | Fixed — size/entry caps |
| Low | fail2ban jail-name argument injection | Fixed — charset validation |
| Low | 2FA failures didn't count toward lockout | Fixed |

**Tested clean** (no findings): SQL injection (login bypass, search, blind
timing), path traversal / jail escape, SSRF, stored XSS, CSRF coverage, CORS
reflection, HTTP verb tampering, mass assignment, session fixation, open
redirect, default credentials, and the TOCTOU quota race (held under 15-way
parallel attack on SQLite).

---

## 3. Competitor CVE-class resistance

The control panels SafeGuard competes with have well-documented breach
histories. SafeGuard was explicitly tested against each major class.

### CyberPanel (PSAUX ransomware campaign, Oct 2024 — ~22,000 servers)
| CyberPanel CVE / technique | SafeGuard result |
|---|---|
| **CVE-2024-51567/51378** — auth-middleware bypass via path normalization (`..;`, suffix tricks) | **Immune.** 16 crafted-path bypasses tested — none reached a protected route. Go's `ServeMux` normalizes paths and auth middleware wraps the *entire* router, not a prefix match. |
| Header-based auth bypass (`X-Original-URL`, `X-Rewrite-URL`) | **Immune.** Middleware reads the real request; 5 header tricks rejected. |
| **CVE-2024-51568** — command injection in upgrade/status endpoints | **Immune.** All exec is argv-style (no shell); shell-metachar payloads into owner upgrade/repair endpoints produced no injection. |
| Unauthenticated access to upgrade/RCE endpoints | **Immune.** 14 sensitive endpoints all reject anon access. |
| **CVE-2023-36163** — open redirect | **Not present.** No attacker-controlled redirects. |
| Default `1234567` admin credential | **Not present.** Installer randomizes the admin password; no common defaults accepted. |

### cPanel / Plesk / DirectAdmin (historical classes)
- **Symlink race / local privilege escalation** — the highest-frequency real
  panel CVE class. Mitigated by per-user jails + isolated PHP-FPM pools, but
  the production file-operation code paths (vhost/cert/zone writes running as
  root) **must be pentested on the live AlmaLinux host** (see §4) — this is the
  one class that genuinely cannot be exercised on the dev box.
- **Authenticated file-manager traversal** — tested clean.
- **API token / session fixation** — tested clean (random `jti` per login,
  server-side revocation).

---

## 4. Production go-live checklist

### Before first deploy
- [ ] Installer sets `JWT_SECRET`, `PASSWORD_PEPPER`, `SAFEGUARD_BINARY_HASH`
      (verify on the real box — `systemctl cat safeguard`).
- [x] Panel store is **SQLite in production by design** (WAL, single writer,
      hourly snapshots, boot integrity check + auto-restore, daily encrypted
      off-site copy). SQLite's write serialization closes the quota-check
      TOCTOU window. Only if the store is ever moved to PostgreSQL (optional
      multi-node epic, not planned for launch) must resource-quota checks
      (domains, databases, FTP, email, subdomains) be wrapped in transactions
      — concurrent PG writers would reopen that window (low-severity
      overselling, not a breach).
- [ ] TLS configured; redirect HTTP→HTTPS; HSTS preload submitted.
- [ ] Database backups scheduled **and a restore drill performed**.

### Deploy-time verification (on the live AlmaLinux host)
- [ ] **Professional penetration test against the production code paths** —
      certbot, BIND zone writes, vhost generation, `systemctl`, CloudLinux
      integration. This is the single most valuable remaining action; it is the
      only way to exercise the root-privileged file operations where panels
      historically get symlink-race and LPE bugs.
- [ ] Run **SSL Labs** and **Mozilla Observatory** against the live HTTPS
      endpoint — confirm cipher suites + that the security headers survive the
      nginx hop end-to-end.
- [ ] **Load / soak test** (k6 or vegeta, 500+ concurrent) — surfaces resource
      leaks and the PG quota race.
- [ ] Confirm Imunify360/ImunifyAV + CSF + Fail2Ban are active per the chosen
      Security Engine; verify the firewall default-denies.

### Post-launch (within 90 days)
- [ ] Wire `govulncheck` + `npm audit` into CI so new CVEs fail the build.
- [ ] Stand up a vulnerability-disclosure program using the built-in
      `security.txt` feature (see §5).
- [ ] Schedule recurring dependency upgrades and an annual third-party pentest.

---

## 5. Vulnerability disclosure

SafeGuard Panel ships a per-domain `security.txt` editor (RFC 9116). Publish a
disclosure policy at `/.well-known/security.txt` with a security contact and
encryption key so researchers can report responsibly. A bug-bounty program
(HackerOne / Intigriti) is recommended once the panel is publicly hosting
customer data.

---

## 6. Security posture summary

At the application layer, SafeGuard Panel implements controls that most web
applications never reach and is **immune to every CyberPanel exploit class that
caused the 2024 mass-compromise**. The remaining distance to "fully
battle-proven" is not additional code — it is **external validation**: a live
pentest of the root-privileged production code paths, load testing, and a
running disclosure/bounty program. Complete §4 on a staging AlmaLinux server
and the panel is ready to compete with cPanel, Plesk, and DirectAdmin.
