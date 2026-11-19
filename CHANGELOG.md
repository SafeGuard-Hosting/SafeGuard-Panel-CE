# Changelog

All notable changes to SafeGuard Panel are documented here. The format is based
on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Each release's verifiable binaries and `SHA256SUMS` are attached to the matching
tag on the [Releases](https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/releases)
page — the installer checks every download against them.

## [Unreleased]

### Changed
- **Host firewall is now firewalld, managed by the panel.** The deny and allow
  lists on the Host Firewall page (formerly the CSF page) become firewalld
  ipsets bound to the drop and trusted zones: they apply immediately, survive
  reboots, take IPv4 and IPv6 addresses or CIDR ranges, and the panel refuses
  to deny-list the address you are connected from. ConfigServer (CSF) closed in
  2025 and its downloads no longer exist, so the installer no longer tries to
  fetch it; a server that still has CSF from an older install is reported and
  left alone.
- **Fail2Ban is the brute-force layer.** The installer sets it up with SSH, FTP
  and repeat-offender jails that block through fail2ban's own nftables rules
  (so a firewall reload never drops a ban). It runs next
  to ImunifyAV by default and pauses only while Imunify360 (which blocks IPs
  itself) is active. Jails, current bans, unban and the never-ban list are
  managed on the Security Engine page.
- Security Engine choices are now **ImunifyAV + Fail2Ban** (default),
  **Fail2Ban only**, or **Imunify360**.
- API: `/api/firewall`, `/api/firewall/toggle` and `/api/firewall/lists`
  replace `/api/csf*`; the old paths keep working for one release.
- Installer: supported-OS list now tracks what cPanel and Plesk ship for Linux
  (EL 9-10 including Virtuozzo Linux, Ubuntu 22.04/24.04/26.04 LTS, Debian
  12/13). EL 8 is deliberately not supported: its kernel predates the
  `openat2` syscall the worker's path jail is built on.

### Fixed
- Account backups failed on every production server ("permission denied" on
  the tenant home); the worker now creates and restores the archives.
- PostgreSQL databases could not be created, listed or dropped on production
  (peer authentication); all PostgreSQL admin now runs through the worker, and
  the database user owns its database.
- Deleting an account could leave it half-deleted (OS account gone, still
  listed in the panel) whenever a pointer, an application or bandwidth history
  referenced it. Deletion is now all-or-nothing and also removes DNS zones,
  FTP logins and engine databases. Billing-initiated terminations now remove
  the OS account too.
- The panel owner's own file manager failed on Ubuntu images that ship an
  `admin` group; the account now gets a private group instead.
- New domains and their DNS zones pointed at 127.0.0.1: the server's real IP
  is detected on start and the IPs page can set the main address.
- Site redirects, custom error pages, hotlink protection and domain pointers
  were saved but never reached the web server. They now do, and unknown
  `.php` URLs return a proper 404 instead of PHP-FPM's "File not found.".
- Staging environments could not be created on production.
- A cron schedule with an out-of-range value (e.g. minute 99) silently took
  every job of the account offline; schedules are validated per field.
- Changing an FTP account's directory from the UI was refused.
- Services page: sshd is recognised on Debian/Ubuntu (unit `ssh`).
- ImunifyAV's idle (socket-activated) state no longer shows the engine as
  "Fail2Ban only".
- Installer: wp-cli is installed, so the WordPress Toolkit works.
- Installer: root password SSH login is now really disabled after a key-based
  install even when a provider or cloud-init drop-in sets `PermitRootLogin yes`
  (the panel ships a higher-priority drop-in and verifies the effective value).
- Installer: PHP 8.5 was always skipped because `php8.5-opcache` does not exist
  (opcache is built in); the PHP stage no longer asks for it, retries once after
  a fresh package index, waits for the apt lock, and keeps apt's errors in
  `/root/safeguard-install-apt.log`.
- Installer: ImunifyAV never installed because its deploy script requires a
  hosting-panel integration file; the installer now writes one first.
- The panel database is created owner-only on first start (it was readable by
  the service group on a fresh install).

## [1.3.57] — 2026-11-15

Security release. Update as soon as practical: it closes two local
root-escalation paths on multi-tenant servers and a secret-exposure issue in
older installs. No action is needed after updating — the worker re-applies file
ownership and permissions on start.

### Security
- Worker: creating a domain seeded its placeholder page with calls that followed
  symlinks inside the tenant's own folder, so a tenant could make root create
  and hand them ownership of an arbitrary file. The seed now goes through the
  worker's kernel-enforced path jail.
- Worker: self-update and rollback helper scripts were staged in the
  panel-writable install directory before being executed as root. They now
  live in a root-only runtime directory.
- Worker: migration dumps were imported as the database superuser. Imports now
  run as a temporary user limited to the target database, with client-side
  commands disabled.
- Worker: on start, re-asserts root ownership of the install tree and binaries,
  closes the data directory and panel database to other users, and locks down
  unit files; refuses to start from a location a non-root user could replace.
- Installer: the JWT secret and initial admin password are no longer written
  into the world-readable systemd unit; they move to a root-only environment
  file. Existing installs get their unit file locked down automatically.
- Sessions: a token is now bound to the user its session row belongs to.
- Support access keys: support sessions can no longer create API keys, SSH
  keys, refresh into a plain session, change 2FA or account credentials,
  impersonate accounts, or read the billing or ticket-bridge secrets.
- The "must change password" flag is now enforced by the API, not just the UI.
- Two-step authentication: setup cannot overwrite an active second factor;
  disabling requires the current password and a valid code; codes are
  single-use.
- Resellers can only assign user-type packages they created or the owner
  published, and only rebind feature sets on their own packages.
- Billing-initiated suspensions revoke live sessions; staff-set passwords follow
  the login password policy and sign the account out everywhere; impersonation
  of suspended accounts is refused.
- Hardened: inbound ticket webhook (header secret, rate limit), Secure cookies
  behind the bundled nginx, migration source probe limited to public hosts,
  SFTP backup paths restricted to a safe character set, FTP username validation.

### Fixed
- Downloading or restoring a managed account's backup as staff used the staff
  member's own directory instead of the account's.

### Dependencies
- Built with Go 1.26.11 (18 standard-library HTTP/2, TLS and certificate
  advisories) and golang.org/x/crypto 0.56.
- Frontend tooling updated (vite 6.4.6, postcss 8.5.33, browserslist 4.29.5,
  nanoid 3.3.20, source-map-js 1.2.2).

## [1.3.56] — 2026-11-14

Cumulative release covering 1.3.55 and 1.3.56.

### Added
- FTP accounts created in the panel are now real pure-ftpd virtual users,
  mapped to the owning account's system user and chrooted to its home.
- CVE advisory matcher: version-range matching against the server's component
  inventory, so advisories only surface for components actually in the
  affected range.

### Fixed
- Login password changes now enforce strength rules and reject reusing the
  current password.
- Eleven "Fix" buttons on the Security page pointed at routes that did not
  exist; all now open the right screen.
- Worker on Debian/Ubuntu: restored the tenant privilege drop (run-as-tenant
  operations such as wp-cli, git and app management had been failing) and made
  package-manager detection pick dnf/apt by what is installed, self-healing if
  an early read of os-release failed. OS security scans work on Ubuntu again.

### Changed
- Release bundles are built, GPG-signed and published from a version tag.

## [1.3.54] — 2026-07-14

### Added
- **SafeGuard Glass** — the panel's own signature theme: a frosted-glass
  sidebar shell with a soft blurred backdrop, colourful app icons, a gauge
  dashboard and rounded cards, in light or dark. It is now **first in the theme
  list and the factory default** (competitor themes stay selectable, and
  existing accounts keep whatever they chose). Activating a theme is now
  WYSIWYG — it applies to your own view immediately.
- **Real multi-panel migration.** The Migration Wizard now imports full accounts,
  not just files: databases (with dump import), DNS zones, email accounts +
  forwarders, FTP accounts, cron jobs and SSL certificates — from cPanel,
  DirectAdmin, Plesk, HestiaCP/Vesta, and a generic files+SQL fallback. Imported
  domains are provisioned with a real web vhost so migrated sites serve
  immediately. The summary reports per-component results. (Mailbox/DB/FTP
  passwords aren't portable between panels, so those are created fresh and
  flagged for reset.)
- **Faithful theme previews** on the Themes page — each card renders a miniature
  of that shell drawn from its own palette, with proper layout labels.
- **Branded error page** at the router level: theme-styled fallback for any
  crash (not just per-page), plus automatic one-time reload recovery when a
  browser holds a stale build after a panel update.

### Changed
- The **Updates** page checks for a new version on load (honouring the privacy
  opt-out).
- Glass theme guardrails: data tables and embedded tools (Adminer, file manager,
  webmail) render on solid surfaces (readability + performance); mobile gets a
  slide-over navigation drawer.

### Fixed
- Restored theme accent colours and hovers on the HestiaCP/ISPConfig/DirectAdmin
  Standard themes (the `brand`/`surface-hover` tokens were undefined, so accents
  and the ISPConfig logout button rendered invisibly).

## [1.3.53] — 2026-06-25

### Fixed
- **Docker container name conflicts.** Deleting a container now removes the real
  engine container by both id and its deterministic name, and running a container
  is idempotent (any leftover with the same name is cleared first) — so a failed
  delete or a stub entry can no longer leave an orphan that blocks recreating the
  same name ("container name … is already in use").

### Internal
- Refactored the Docker run path into a single idempotent helper (used by both
  create and recreate), made the engine-status probe accurate, aligned the local
  CLI fallback with the worker's hardened flags, and added status-reconciliation
  unit tests.

## [1.3.52] — 2026-06-25

### Added
- **Edit containers** — every container now has an Edit action to change its
  image, port mappings and environment variables. The Run/Edit dialog gained a
  proper **environment-variables** editor (add/remove rows) and **multiple port
  mappings**; editing recreates the container under the same name.
- **Live container status** — the container list now reflects the real Docker
  state instead of a stored value, so a crash-looping container shows **error**
  (with the live "Restarting (1)…" detail) rather than a false "running". The
  list auto-refreshes every 5s and the logs view tails every few seconds.
- **Owner Docker Engine management** — Administrator Settings has a Docker Engine
  card showing install status/version and an **Uninstall** action (background
  job; image data under /var/lib/docker is preserved). Install stays on the
  Docker page while the engine is absent.

### Changed
- The **Updates** page now checks for a new version automatically on load
  (honouring the privacy opt-out — no network call when automatic checks are
  disabled). "Check Now" remains the explicit, forced check.

### Fixed
- The Docker install card no longer briefly shows the Install button again if the
  install finished while the page was navigated away.

## [1.3.51] — 2026-06-25

### Added
- **Owner-initiated Docker Engine install.** When the engine isn't present, the
  Docker page now shows the owner an **Install Docker Engine** button instead of
  only the "simulated" notice. It installs Docker CE on the host (RHEL/AlmaLinux 9,
  via `dnf`) through the privileged worker as a background job with a live
  progress log, then enables the daemon — no shell access needed. Docker is still
  never bundled into the base install, keeping the default attack surface small.

### Fixed
- GHCR image search now accepts a pasted full reference — a `:tag` or `@digest`
  suffix (e.g. `ghcr.io/owner/image:latest`) is stripped before the tag lookup
  instead of failing validation.

## [1.3.50] — 2026-06-25

### Added
- **GitHub Container Registry (GHCR) support** in the Docker module. The Run
  Container dialog now has a **Docker Hub / GHCR** registry toggle. Because GHCR
  has no global search API, GHCR mode takes an exact `owner/image` and lists its
  available **tags** via the Docker Registry v2 API (works for any public image)
  so you can pick a version, then runs `ghcr.io/owner/image:tag`.
- Images from any registry can be pulled/run by full reference (e.g.
  `ghcr.io/owner/image:tag`); private GHCR images run by exact reference once the
  host is logged in to ghcr.io.

## [1.3.49] — 2026-06-17

### Added
- **Real OS vulnerability scanning & patching**: the server scans installed
  packages against the CVE database (via `dnf updateinfo`, mapping packages to
  CVEs + NVD severity) and can apply security updates. Daily automatic scan with
  owner alerts on new critical/important CVEs, plus an opt-in **Auto-patch**.
- New **"OS Security Updates (CVE)"** card on the Vulnerability Scan page —
  severity breakdown, per-CVE NVD links, Scan/Apply, Auto-patch toggle.

### Fixed
- The scanner's package-CVE and kernel checks were always-pass placeholders;
  they now reflect the real scan and report pending security updates.

## [1.3.48] — 2026-06-17

### Fixed
- **Cron jobs now actually run** — installed into the tenant's real crontab and
  executed as that user (previously stored but never run).
- **Cache purge is real** — runs the actual per-layer tool (Varnish ban / Valkey
  flush / PHP-FPM reload); disabling a layer clears it; not-installed layers are
  reported honestly.

### Security
- Removed the DevTools blocking/detection (no real protection; it reported user
  behavior). Tightened the internal wp-cli `db` boundary.

### Docs
- Added a PCI-DSS & DORA compliance-readiness document (the panel is out of
  cardholder-data scope). GDPR transparency/export/erasure/telemetry-opt-out are
  already in place.

## [1.3.47] — 2026-06-17

### Added
- **App Manager — serve apps on their domain**: a new **Expose** toggle points the
  domain's nginx vhost at the app (reverse-proxy to its port) instead of static
  files — SSL-aware, with WebSocket support and config rollback. Toggling off
  restores the static site. App hosting is now complete end-to-end.
- **Ticket management**: staff can set status (open/answered/closed) and priority
  (low/medium/high/urgent) from the ticket thread.

## [1.3.46] — 2026-06-17

### Fixed
- **System Services** start/stop/restart now works: the PHP-FPM control targeted
  the distro `php-fpm.service` (left disabled) instead of the panel's own
  `safeguard-php`, so it always failed. Verified on a live server.
- Services not installed on the host (ClamAV, Memcached, …) now show as
  "not installed" with actions hidden, instead of failing with "Unit not found".

## [1.3.45] — 2026-06-17

### Added
- **App hosting that runs**: App Manager starts/stops/restarts apps as real
  per-app services with dependency install and live logs; Git, Docker and the
  Website Builder's publish run on the server as the account owner.
- **WordPress Toolkit** fully wired: update core/plugins/themes, integrity check,
  plugin/theme inventory, maintenance mode, plugin/theme sets, one-click harden,
  **Clone**, **Copy Data**, and a true one-time admin login.
- **Joomla/Drupal** toolkits report the real installed version + update-available.
- **Cache Manager**: per-layer enable/disable, with correct PHP/Varnish/Valkey
  detection.
- **API keys**: per-key IP allow-list and expiry date.
- **Repair page rollback** to the previous version (restores prior build +
  database and restarts).

### Changed
- New **Files** group (File Manager, FTP, Password-Protected Directories, Git,
  Backup & Restore); **Domains**/**Databases** groups; Hotlink Protection under
  Security; WordPress Toolkit moved to `/wordpress`.
- Lowercase breadcrumbs; removed the top-bar domain link; right-click re-enabled.
- **Updates & Repair** split — updates on the Updates page, **Repair** is repair +
  rollback only.
- **Import History** renamed (upload lives in the Migration Wizard).
- Support keys auto-delete on expiry or removal.

### Fixed
- **System Services** surfaces the real error on a failed start/stop/restart.
- **Security Advisor** loads instantly (stale-while-revalidate cache).
- Reliable scroll restoration; staging preview URLs use the server's own hostname.

## [1.3.44] — 2026-06-16

### Added
- **Adminer password auto-fill (owner opt-in)**: a new owner setting (off by
  default) lets the embedded Adminer login fill in completely — including the
  password — so picking a database logs you straight in and switching between
  databases is seamless. Off keeps passwords one-way hashed (the secure default);
  on stores newly created/changed DB passwords encrypted at rest, and turning it
  back off wipes every stored copy.
- **Change a database user's password**: the Databases page now has a Change
  Password action that updates the password on the engine (MySQL/MariaDB and
  PostgreSQL) and keeps the panel in sync.

### Changed
- **Cleaner embedded Adminer**: the stray language selector and the redundant
  "permanent login" checkbox are hidden inside the panel.
- **Reorganised navigation**: new **Domains** group (Domains, Subdomain
  Management, DNS Management, Domain Pointers, Site Redirects) and **Databases**
  group (Databases, Adminer); Webmail and Catch-All E-mail moved under E-Mail
  Manager; Perl Modules moved under Websites & Apps.
- **One "Manage Packages" module**: user and reseller packages are managed in one
  place. Owners manage both kinds (and choose the type when creating); resellers
  manage user packages only. Creating reseller packages remains owner-only.

### Removed
- **Message Templates**: the feature only ever saved templates (nothing applied
  them), so it has been removed.

## [1.3.43] — 2026-06-16

### Added
- **Adminer login prefill**: pick a database in the embed and Adminer's login is
  pre-filled (driver from the engine, server, username, database) — you enter only
  the password, and Adminer's permanent login keeps you in after that.
- **Deep-link login**: opening a panel URL while logged out now returns you to
  that exact page after you sign in, instead of the dashboard.

### Fixed
- **Scroll position is restored** when you navigate back to the dashboard (it no
  longer jumps to the top); new navigations still start at the top.

### Changed
- **User menu cleanup**: removed Change Password and Two-Step Auth from the
  top-right menu — both now live on the **Profile** page, where 2FA is fully
  managed (setup, backup codes, disable) inline.
- App Manager moved into the **Websites & Apps** group.

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

[Unreleased]: https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/compare/v1.3.57...HEAD
[1.3.57]: https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/compare/v1.3.56...v1.3.57
[1.3.56]: https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/compare/v1.3.54...v1.3.56
[1.1.0]: https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/releases/tag/v1.1.0
[1.0.1]: https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/releases/tag/v1.0.1
[1.0.0]: https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/releases/tag/v1.0.0
