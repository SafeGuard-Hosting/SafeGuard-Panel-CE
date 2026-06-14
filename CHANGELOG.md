# Changelog

All notable changes to SafeGuard Panel are documented here. The format is based
on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Each release's verifiable binaries and `SHA256SUMS` are attached to the matching
tag on the [Releases](https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/releases)
page — the installer checks every download against them.

## [Unreleased]

## [1.3.17] — 2026-06-14

### Fixed
- **Dashboard sidebar statistics are now role-correct.** The Owner, Reseller and
  User dashboard views previously all showed the logged-in account's own stats.
  Each view now shows the right scope: User sees their own account, Reseller sees
  aggregates across the accounts they manage, and Owner sees server-wide totals.

## [1.3.16] — 2026-06-13

### Changed
- **Auto-rollback now restores the database too.** When an update is rolled back
  because the new build is unhealthy, the panel also restores the database backup
  taken just before the update, so a faulty release — including a bad schema
  migration — is fully undone, not just the program files.

## [1.3.15] — 2026-06-13

### Changed
- **Updates never downgrade.** An update is only offered when the published version
  is genuinely newer than the running build, so a bad or rolled-back release
  manifest cannot push an older version onto the panel.
- **Rollback covers the privileged worker too.** The post-update health check now
  requires both the panel and its worker to come up healthy; a release that breaks
  the worker auto-rolls-back instead of leaving the panel up with a dead worker.

## [1.3.12] — 2026-06-13

### Fixed
- **“Check Now” on the Updates page now performs a real, immediate check.** It
  previously only re-read the last daily check, so a newly published release did
  not appear until the next scheduled check; it now queries the update server on
  demand (with a loading indicator).

## [1.3.11] — 2026-06-13

### Fixed
- **Updates page changelog no longer runs off-screen.** Release notes on the
  Updates page were rendered in a non-wrapping block that overflowed the card;
  they now appear as a clean, labeled “What’s new” block that wraps to fit.

## [1.3.10] — 2026-06-13

### Fixed
- **Self-update could fail with a checksum mismatch behind a CDN.** A CDN in front
  of the update host could serve a stale cached `frontend.tar.gz` from a previous
  release under the same URL, failing the SHA-256 check (the update aborted safely
  but could not proceed). Artifact downloads are now cache-busted so they are
  always fetched fresh; signature and checksum verification remain the source of
  trust.
- **Some owner notifications were silently dropped** (update finished/aborted,
  scheduled-restart completion, critical security events) due to a wrong column
  name; they now record in the notification bell.

## [1.3.9] — 2026-06-13

### Added
- **Maintenance overlay during updates.** Proceeding with a panel update now shows
  a full-screen overlay that blocks the panel while it verifies, swaps and
  restarts — so nothing is submitted against a backend mid-restart — then reloads
  automatically onto the new version when it is back.

### Changed
- **No more scrollbars.** Vertical and horizontal scrollbars are hidden panel-wide
  (content stays scrollable), the page no longer scrolls sideways, and wide
  content is kept within the viewport so it fits the screen and stays visible.

## [1.3.8] — 2026-06-13

### Added
- **Actions are logged in the notification bell.** Successes, warnings and errors
  are now recorded in the notifications menu — not only shown as a passing toast —
  so you keep a running log of what occurred.

### Changed
- **Buttons show a loading state automatically.** A button that starts work greys
  out and shows a spinner until it finishes, giving clear feedback and preventing
  double-submits. Toasts continue to appear in the bottom-right.

## [1.3.7] — 2026-06-13

### Changed
- **The Updates page now runs the real, installer-style update flow.** Its
  “Install” button previously called a placeholder that only described the steps
  and took no action. It now works like the first-time setup installer: clicking
  Install reveals pre-flight checks (disk, memory, database integrity, schema,
  service health), and only once they pass can you proceed to the staged,
  GPG-verified pipeline — download → verify → swap (previous binary kept for
  rollback) → migrate → restart — with live progress.

## [1.3.6] — 2026-06-13

### Fixed
- **System Information reports the real datastore.** The dashboard's
  “Database” field showed a hardcoded placeholder; it now reports the actual
  engine — the panel's embedded SQLite store — and its version. (PostgreSQL and
  MariaDB are for *tenant* databases, not the panel's own store.)

## [1.3.5] — 2026-06-13

### Fixed
- **The dashboard shows the running panel version.** The System Information
  endpoint returned a hardcoded development value, so the version shown never
  matched the installed build even though the panel already tracked its real
  build version internally. The endpoint now reports the real build version.

## [1.3.4] — 2026-06-13

### Added
- **Reassign a domain to another account.** Owners (and resellers, for accounts
  they manage) can move a site to a different account from the Domains page: the
  document root is relocated into the new owner's home, the vhost is rebuilt to
  serve it as them, and any installed certificate is re-applied under the new
  owner.

### Fixed
- **The File Manager now works for owners (and any account without a site yet).**
  An account with no hosting home couldn't create files or directories; the File
  Manager now provisions the account's home on first use.

## [1.3.3] — 2026-06-13

### Fixed
- **The panel now reports its real version.** The version shown in the UI was a
  database seed (`1.0.0`) that nothing ever updated, so the panel kept showing
  `1.0.0` regardless of the running build. The binary now carries its own version
  and syncs it on boot, independent of the update server's reachability.

## [1.3.2] — 2026-06-13

### Added
- **The panel now terminates HTTPS itself.** Installing a certificate (custom,
  Let's Encrypt, or wildcard) writes it to disk and rebuilds the domain's nginx
  vhost with a real `:443` server block, so the box serves the site over HTTPS
  directly (with an optional HTTP→HTTPS redirect). Previously a cert was only
  recorded in the panel and never served. Behind Cloudflare with a wildcard or
  origin cert you can now use **Full (strict)**. Removing a cert reverts the
  domain to HTTP.

## [1.3.1] — 2026-06-13

### Fixed
- **SSL page no longer breaks when installing a custom certificate.** A
  certificate whose hostnames live only in the Common Name (no SAN extension) —
  common for Cloudflare Origin and other manually-issued certs — could blank out
  the SSL page after install. Certificates now always display correctly, and
  pasted PEM with stray whitespace is tolerated.

### Added
- **Per-domain navigation in the File Manager.** A Site selector jumps to a
  domain's document root (or the whole home), and each Domains-page row has a
  Files button that opens the file manager at that site's document root.

## [1.3.0] — 2026-06-13

### Added
- **Complete File Manager for hosting accounts.** Upload, download, copy,
  compress (`.zip`/`.tar.gz`), extract, and search now work for every hosting
  account — joining the editor, create, rename, and permissions operations
  shipped earlier. The file manager is now fully usable by customers, not just
  the owner.
- **Verified one-click updates.** The panel downloads a signed release, verifies
  the GPG signature and every checksum before installing, swaps the binaries
  (keeping the previous build for rollback), and restarts automatically. A bad
  or missing signature aborts the update.

### Security & hardening
- Every file operation for a hosting account runs through the isolated privileged
  worker, confined to that account's home directory by the kernel
  (`openat2 RESOLVE_BENEATH`): no symlink escape, no `..` traversal, and archive
  extraction is guarded against zip-slip and decompression bombs.
- The updater re-verifies the release signature and checksums inside the
  privileged component itself before swapping any binary, so a compromised panel
  process cannot install an unsigned or tampered build.

## [1.2.0] — 2026-06-13

### Added
- **Authoritative DNS.** Creating a domain now renders a real BIND zone (SOA,
  NS, A, MX, SPF, …) and the server answers DNS for it; the DNS editor updates
  the live zone on every change, and deleting a domain removes it.

### Security & hardening
*(From an extensive live penetration test — OWASP Top 10 plus the CVE classes
that have breached cPanel/Plesk/DirectAdmin/Webmin/VestaCP.)*
- **Per-tenant resource isolation now actually enforces.** Each tenant's PHP-FPM
  runs as its own systemd instance with bound cgroup CPU/memory/task caps, so one
  tenant can no longer starve the box or its neighbours.
- **CSP + HSTS** now cover the panel's HTML document, not just API responses.
- **phpMyAdmin** is no longer exposed on the bare `:80` host outside panel auth.
- **MariaDB/PostgreSQL bind to localhost** — remote DB access is opt-in, so a
  firewall slip can't expose customer databases.
- Self-healing BIND master include; safer default-server handling.

## [1.1.0] — 2026-06-13

### Added
- **Real OS provisioning.** Creating a hosting account now creates a locked
  system user with a home directory, a dedicated PHP-FPM pool, and cgroup
  CPU/memory limits. Creating a domain creates its document root and nginx
  vhost and serves the site immediately.
- **Full teardown on delete.** Removing a domain or account cleans up its
  vhosts, system user, home, pool, and limits — no orphans left behind.

### Notes
- All system actions run through the isolated privileged worker over a local
  socket; the panel itself stays unprivileged. A failed system action rolls the
  panel's own record back so its view never diverges from the server.

## [1.0.1] — 2026-06-13

### Fixed
- Installer no longer aborts when Apache and Nginx both claimed port 80.
- Prevented an SSH-lockout where an OpenSSL upgrade left `sshd` unable to start;
  OpenSSH and OpenSSL are now kept in lockstep and `sshd` is verified before the
  installer finishes.
- Fixed an over-broad Nginx rule that caused the panel to return 404 for every
  request.
- Fixed a single-connection database stall that could freeze the panel under
  normal use.
- The standalone repair server (`:2085`) is now reachable through the firewall,
  and the repair key is created with the correct ownership.
- Root password SSH login is only disabled when an SSH key is present, so a
  key-less one-line install can never lock you out.
- Plain HTTP to the panel's HTTPS port now redirects instead of erroring.

## [1.0.0] — 2026-06-12

### Added
- First public release of SafeGuard Panel: domains, DNS, SSL, per-domain
  PHP-FPM, MariaDB + PostgreSQL customer databases, FTP, cron, file manager,
  email, six full UI themes, website builder, the security stack (CSF /
  ImunifyAV / Fail2Ban / Imunify360, ModSecurity, ClamAV, 2FA, Security
  Advisor), encrypted off-site backups, migration importers, and the standalone
  repair server.

[Unreleased]: https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/releases/tag/v1.1.0
[1.0.1]: https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/releases/tag/v1.0.1
[1.0.0]: https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/releases/tag/v1.0.0
