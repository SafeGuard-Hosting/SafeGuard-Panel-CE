# Contributing to SafeGuard Panel

Thanks for your interest in SafeGuard Panel! This is a **closed-source** product
(see [LICENSE](LICENSE)), but this repository is where the community and the
maintainers meet — and several kinds of contribution are genuinely welcome.

## What we welcome here

✅ **Bug reports.** Found something broken? [Open an issue](https://github.com/SafeGuard-Hosting/SafeGuard-Panel-CE/issues/new/choose)
using the bug template. Real installs on real servers are the best testing we
get — a good report helps everyone.

✅ **Feature requests & feedback.** Tell us what would make the panel better for
your hosting workflow.

✅ **Billing-integration modules.** The [`billing_integrations/`](billing_integrations)
directory is open. PRs that add or improve a module for a billing platform
(WHMCS, Blesta, HostBill, FOSSBilling, Paymenter, …) are very welcome — that
code is meant to be community-extensible.

✅ **Documentation fixes.** Typos, clearer wording, better examples in the README
or `docs/` — small PRs are appreciated.

✅ **Translations.** Want the panel in your language? Let us know in an issue and
we'll point you at the strings.

## What isn't accepted here

❌ **Pull requests against the panel's core source.** The panel, worker, and
frontend are closed source and aren't published in this repository, so there's
nothing to PR against. Please use issues for core bugs and feature requests
instead — we triage and fix them in the private repository and ship the result
in a release.

❌ **Redistributing or repackaging the panel** without a written license. Running
a hosting company and selling reseller hosting is free; *selling or distributing
the panel itself* requires a license first — see [LICENSE](LICENSE) §11 and
contact support@safeguardpanel.ca.

## Reporting a bug well

1. Use the **Bug report** issue template.
2. Include your OS (AlmaLinux/Rocky/CloudLinux 9), the panel version
   (Owner → About, or the release tag you installed), and steps to reproduce.
3. Paste the relevant log lines (`journalctl -u safeguard`, the installer log,
   or the browser console) — redact anything sensitive.

## Security issues

**Do not** open a public issue for a vulnerability. Follow the private
disclosure process in [SECURITY.md](SECURITY.md).

## Submitting a PR (docs / billing modules / translations)

1. Fork, branch from `main`, keep the change focused.
2. Match the surrounding style; for a billing module, follow the structure of
   the existing modules and include its install notes.
3. Open the PR with the template filled in. By contributing you confirm you have
   the right to license your contribution to us under this project's terms.

## Getting help

Not a bug, just a question? See [SUPPORT.md](SUPPORT.md).
