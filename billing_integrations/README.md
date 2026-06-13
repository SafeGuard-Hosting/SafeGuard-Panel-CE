# 💳 Billing panel integrations

Connect any billing platform to SafeGuard Panel so that **paid order → hosting
account** happens automatically: provision on payment, suspend on non-payment,
terminate on cancellation, and one-click client login (SSO).

Every integration here is a thin translator between the billing platform's
plugin format and the panel's universal provisioning hooks.

## 🔑 One-time setup (panel side, all platforms)

1. Log in to SafeGuard Panel as the **Owner**.
2. Go to **Billing System** (`/owner/billing`).
3. In the **Provisioning API Key** card, click **Generate Key** and copy the
   `sgb_…` key — it is shown once.
4. Paste that key into your billing platform's module configuration (the field
   name per platform is listed in each module's README below).

## 📦 Available modules

| Platform | Folder | Status |
|---|---|---|
| **WHMCS** | [`whmcs/`](whmcs/) | ✅ Ready |
| **Blesta** | [`blesta/`](blesta/) | 🧪 Reference implementation |
| **HostBill** | [`hostbill/`](hostbill/) | 🧪 Reference implementation |
| **ClientExec** | [`clientexec/`](clientexec/) | 🧪 Reference implementation |
| **FOSSBilling** | [`fossbilling/`](fossbilling/) | 🧪 Reference implementation |
| **BoxBilling** | [`boxbilling/`](boxbilling/) | 🧪 Reference implementation |
| **Paymenter** | [`paymenter/`](paymenter/) | 🧪 Reference implementation |
| **BillingServ** | [`billingserv/`](billingserv/) | 📖 API guide (no module needed) |

> 🧪 **Reference implementation** = complete, reviewed code that follows the
> platform's documented module format, but not yet exercised against a live
> install of that platform. Test in staging before production and report
> anything that needs adjusting.

## 🌐 No module for your platform? Use the REST hooks directly.

The panel's provisioning API is six plain HTTP calls. Authenticate every call
with the provisioning key as a Bearer token:

```sh
# Create an account (and optionally its first domain)
curl -X POST https://panel.example.com:2087/api/billing/provision \
  -H "Authorization: Bearer sgb_YOUR_KEY" -H "Content-Type: application/json" \
  -d '{"username":"newcustomer","email":"c@example.com","password":"S3cret!pw","domain":"example.com","package":"Starter"}'

# Lifecycle
curl -X POST .../api/billing/suspend        -d '{"username":"newcustomer"}'   # + auth headers
curl -X POST .../api/billing/unsuspend      -d '{"username":"newcustomer"}'
curl -X POST .../api/billing/terminate      -d '{"username":"newcustomer"}'
curl -X POST .../api/billing/change-package -d '{"username":"newcustomer","package":"Pro"}'

# One-click client login — returns {"url": "/sso?token=..."}
curl ".../api/billing/sso?username=newcustomer" -H "Authorization: Bearer sgb_YOUR_KEY"
```

Responses use the panel envelope `{"success": bool, "data": …, "error": str}`.
The full schema is in the panel's built-in API explorer (**Owner → API Docs**,
category *Billing Integration*).

## ↔️ The other direction

These modules let the billing panel drive SafeGuard Panel. The reverse —
SafeGuard Panel reading tickets and verifying licenses *from* your billing
platform — is built into the panel itself (**Owner → Billing System**) for all
eight platforms above; no extra files needed.
