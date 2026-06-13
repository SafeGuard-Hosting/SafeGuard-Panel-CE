# Changelog

All notable changes to SafeGuard Panel are documented here. The format is based
on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Each release's verifiable binaries and `SHA256SUMS` are attached to the matching
tag on the [Releases](https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/releases)
page — the installer checks every download against them.

## [Unreleased]

### Added
- **Authoritative DNS.** Creating a domain now renders a real BIND zone (SOA,
  NS, A, MX, SPF, …) and the server answers DNS for it; the DNS editor updates
  the live zone on every change, and deleting a domain removes it.

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
