# Changelog

All notable changes to SafeGuard Panel are documented here. The format is based
on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Each release's verifiable binaries and `SHA256SUMS` are attached to the matching
tag on the [Releases](https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/releases)
page — the installer checks every download against them.

## [Unreleased]

## [1.3.42] — 2026-06-16

### Added
- **Ticket forwarding bridge**: pipe panel support tickets out to an external
  billing/helpdesk system (e.g. WHMCS) over email, with a stable `[SGP #id]`
  subject token; staff replies flow back into the panel ticket via an inbound
  reply webhook. Configure it under Manage Tickets. **Support Tickets** is also now
  a toggleable module (disable the built-in system entirely if you only use an
  external one).
- **Help** and **API Documentation** are now available to resellers and owners,
  not just users.

### Changed
- **Adminer is now the single, universal database tool** for MySQL/MariaDB *and*
  PostgreSQL (replacing the separate phpMyAdmin/pgAdmin links). The Database
  Monitor and Databases pages open Adminer; phpMyAdmin is no longer installed by
  default. Connect any external client you prefer over the network.
- **Owner Account Manager streamlined**: a single **Add Account** form creates a
  user, reseller, or owner via a role picker; "Move Users Between Resellers" is now
  a bulk action right in **Show All Users**; removed the redundant Resellers,
  Owner Accounts, My Users and Change Passwords entries.
- Navigation tidy-ups: Support Access Keys moved under Support; removed the
  duplicate Two-Step Authentication tile (it's in the user menu).

## [1.3.41] — 2026-06-16

### Added
- **Hostname SSL** (owner): view and install a trusted TLS certificate for the
  panel's own hostname (separate from customers' domain certificates). The new
  cert is validated with `nginx -t` and rolled back automatically if it fails.
- **Perl Modules** (user): list the Perl modules available to your account and
  install CPAN modules into your `~/perl5` (requires cpanm on the server).

### Changed
- **Navigation reorganized** across owner, reseller and user views: new
  **Security** and **Integrations** categories, clearer grouping, and renamed
  integrations (Email Integration, Billing Integration, Remote Backup Integration).
  Updates & Repair moved next to Support.
- **Add Account** (owner): one form now creates a user, reseller, or owner via an
  account-type picker, replacing the separate create screens.

### Fixed
- **System Services** start/stop/restart now work — the panel runs unprivileged,
  so service control (and background auto-restart of crashed services) is routed
  through the privileged worker instead of failing silently.
- Removed duplicate and dead navigation entries (Plugin Manager, Reseller
  Statistics, Mailing Lists, Manage User Backups, CustomBuild, System Packages,
  and the redundant Change Passwords page).

## [1.3.40] — 2026-06-15

### Added
- **Email Summary** (owner/reseller): a per-domain overview of mailboxes, total/used
  mailbox storage, and forwarder/autoresponder counts, with rolled-up totals — an
  at-a-glance view of email usage across every account you manage. (The Owner Tools
  "E-mail Summary" tile now opens a real page instead of a dead link.)

## [1.3.39] — 2026-06-15

### Fixed
- **SSL page showed a blank "Issuer"** for certificates whose issuer has no Common
  Name (e.g. Cloudflare Origin certificates, which identify the CA only by
  Organization / Organizational Unit). The issuer now falls back to OU/O so it's
  always shown.

## [1.3.38] — 2026-06-15

### Added
- **Unsaved-changes navigation guard**: navigating to another page while a form
  has unsaved edits now shows a "Discard unsaved changes?" prompt (Keep editing /
  Leave page), not just on browser refresh/close. Applies app-wide to forms that
  track unsaved state.

## [1.3.37] — 2026-06-15

### Added
- **Multi-target off-site backups**: configure multiple backup destinations —
  Backblaze B2, Amazon S3 (or any S3-compatible store), and SSH/SFTP — under
  System Backup Settings. Each backup is encrypted once and uploaded to every
  enabled destination, with per-destination success/failure tracked; the first
  successful copy is used for download/restore, and deleting a backup removes
  every off-site copy. Add/edit/test/delete destinations from the UI (secrets are
  never echoed back). The legacy single-Backblaze-B2 settings still work when no
  destinations are configured.
- **Throttled backup queue**: a configurable global *max concurrent jobs* (default
  2) keeps a burst of manual or scheduled backups from overloading the server;
  extra jobs queue.

## [1.3.36] — 2026-06-15

### Added
- **Server Statistics — per-disk breakdown + SMART**: lists every real filesystem
  (device, type, mount, used/total). Click a disk to view its SMART report —
  overall health, model, serial, capacity, temperature, power-on hours, and the
  full `smartctl` output. Read via the privileged worker; shown where the hardware
  exposes SMART, and reported as unavailable on virtualized/cloud disks rather
  than failing.
- **Resource Limits — alerts & thresholds**: click an account to set per-account
  disk/bandwidth usage alert thresholds (checked hourly, raised as in-panel
  notifications with a 24h cooldown per metric), or send a one-off info/warning/
  critical alert straight to a user. (Per-user CPU/I/O *enforcement* still
  requires CloudLinux LVE on the host.)

## [1.3.35] — 2026-06-15

### Fixed
- **Security page performance**: no longer waits ~4 seconds on every load — the
  security rating is now cached for 5 minutes.

## [1.3.34] — 2026-06-15

### Added
- **Owner PHP Settings page**: choose which PHP versions (8.0–8.5, tagged
  supported/recommended/end-of-life) are allowed server-wide and set the default;
  enabled versions appear in every account's per-site PHP selector.

## [1.3.33] — 2026-06-15

### Improved
- **Bandwidth Enforcement**: the warning threshold (% of quota) is now configurable.
- **Owner Settings**: the auto-restart Services field explains it expects a JSON
  array of systemd service names.
- **Unsaved-changes warning** before refreshing/closing/leaving a settings form
  with unsaved edits.

## [1.3.32] — 2026-06-15

### Improved
- **Message All Users** asks for confirmation before sending a broadcast.
- **Modules** page links each enabled module to its management page (⚙️).
- **Updates**: the changelog can be popped out into a larger view, with a link
  to the full release history on GitHub.

## [1.3.31] — 2026-06-15

### Improved
- **Process Monitor** shows each process's owning user and can filter by
  user / process / PID.
- **Maintenance** explains, in plain language, what each internal task does.
- **Notifications**: clicking one in the bell expands it to the full message.

## [1.3.30] — 2026-06-15

### Improved
- **Server Statistics** tiles (Accounts, Domains, Open Tickets) are now clickable
  and navigate to their pages.
- **Services**: stopping a service now asks for confirmation first.
- **CSF firewall** shows your own IP and refuses to add it to the deny list,
  preventing an accidental self-lockout.
- Renamed **"DB Monitor"** to **"Database Monitor"**.

## [1.3.29] — 2026-06-15

### Added
- **More real one-click installs.** Matomo, Nextcloud, Joomla, MediaWiki and
  phpBB now install for real — the panel stages the app into the document root,
  provisions a dedicated database, and hands the credentials to the app's own web
  installer to finish setup. Apps that require Composer/Node/Ruby/Docker show a
  clear "manual setup" notice instead of a fake success, and removing an install
  drops its database and files.

## [1.3.28] — 2026-06-15

### Added
- **Real one-click WordPress install.** Installing WordPress now provisions it
  for real: a dedicated database and user are created, WordPress core is
  downloaded and staged into the document root via the privileged worker, and a
  working `wp-config.php` is written; WordPress finishes its first-run setup on
  first visit. Removing the install also drops its database and files. (More
  catalog apps will follow on this pipeline.)

### Fixed
- **File uploads and app installs** could fail with a spool-directory permission
  error; the privileged worker now ensures the shared spool directory is owned
  by the panel user on startup (self-heals on update).

## [1.3.25] — 2026-06-15

### Fixed
- **File Manager download** no longer fails with a permission error.
- **Disk Usage** now reports real numbers for hosting accounts (measured by the
  privileged worker, which can read locked-down tenant homes).
- **phpMyAdmin / Adminer** embed loads again instead of showing "not configured".

### Changed
- **Themes** management is consolidated to one page at **Settings → Theme**
  (reached from the user menu), scoped by role; the separate sidebar link was
  removed. The personal theme picker in the user menu is unchanged.
- The owner **"Change Passwords"** link is now **"Change User Passwords"**.

## [1.3.24] — 2026-06-15

### Fixed
- **Fixed a panel-wide hang when managing feature sets and packages.** The
  feature-sets list issued a per-row database query while the list's own result
  set was still open; with the panel's single-writer database that self-
  deadlocked, so every database-backed page (login, dashboard, …) stopped
  responding while only the health check stayed up. It appeared right after
  creating a feature set and reopening the list. The list now reads its rows
  first and looks up package memberships afterward.

## [1.3.23] — 2026-06-14

### Fixed
- **Database management now works on AlmaLinux.** Creating or deleting a MySQL/
  MariaDB database or user failed with “Access denied” because the panel tried to
  authenticate as the database superuser directly (which the OS only allows for
  root). Database administration now runs through the privileged root worker via
  a structured, validated operation — the worker builds the SQL itself from
  checked identifiers and never executes SQL handed to it by the panel, keeping
  the panel's privilege-separation guarantees intact.

## [1.3.22] — 2026-06-14

### Added
- **Privacy Notice (GDPR transparency).** A plain-language privacy notice is now
  built into the panel at `/privacy`, linked from the login screen and from
  Profile → Privacy & your data. It explains what data the panel stores, the
  legal bases, retention periods, subprocessor categories and your rights, using
  your own branding and contact details.
- **Telemetry opt-out.** Administrator Settings → Privacy & Telemetry can turn
  off the automatic update check. While off, the panel makes no network call to
  the update server on its own; a manual "Check Now" still works on demand.
- **Configurable log retention.** Audit-log and sign-in-history retention periods
  are now settings (sign-in history was previously fixed at 90 days).
- **Privacy contact email** and an optional **external privacy-policy URL** (set
  it to redirect the in-panel notice to your own published policy).

### Fixed
- **Disk Usage, System Stats and Cron Output no longer error for owner accounts.**
  These pages returned a 500 ("could not open home directory") for an account
  without a writable hosting home; they now show an empty state instead. The
  per-user file jail is unchanged.

## [1.3.21] — 2026-06-14

### Added
- **Download my data (GDPR).** Profile → "Download my data" exports a
  machine-readable copy of all personal data the panel holds for your account
  (right of access / data portability); secrets are excluded.

### Changed
- **Erasure hardening.** Deleting an account now also clears IP addresses from
  retained audit-log entries, so no network identifier lingers.

## [1.3.20] — 2026-06-14

### Added
- **Support Access for owners.** The support-access keys feature (generate a code
  that lets support log into your account with limited, time-boxed access) is now
  linked in the owner Support section, not just the user view.

## [1.3.19] — 2026-06-14

### Removed
- **Panel licensing module.** SafeGuard Panel is free — there is no panel license
  to manage — so the licensing screen, its dashboard tile, and panel-license
  verification were removed. Billing-system integration (managed hosting) and the
  security engine's own licensing (e.g. Imunify360) are unaffected.

## [1.3.18] — 2026-06-14

### Added
- **Real disk & inode usage.** Account disk usage is now measured by the privileged
  worker (which can read tenant homes) and inode counts are tracked too, so the
  dashboard shows real numbers instead of zero.

### Changed
- **Unlimited limits show the ∞ symbol** (e.g. `0 / ∞`) instead of "Unlimited".
- **Header CPU/RAM/Disk/Inodes is scoped to your view.** Owner sees server-wide
  figures; Reseller sees disk + inodes across managed accounts; User sees their
  own. Whole-server CPU/RAM are shown to the owner only.

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
