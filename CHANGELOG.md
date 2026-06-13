# Changelog

All notable changes to SafeGuard Panel are documented here. The format is based
on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Each release's verifiable binaries and `SHA256SUMS` are attached to the matching
tag on the [Releases](https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/releases)
page — the installer checks every download against them.

## [Unreleased]

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
