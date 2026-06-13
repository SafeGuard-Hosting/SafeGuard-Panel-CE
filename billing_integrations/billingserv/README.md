# BillingServ integration

BillingServ is a hosted billing platform — there is no module file to copy.
Instead, point its server-automation settings at SafeGuard Panel's REST hooks.

## Setup

1. Generate the provisioning key in SafeGuard Panel:
   **Owner → Billing System → Provisioning API Key → Generate Key**.

2. In BillingServ, configure a custom server/provisioning integration with
   these endpoints (Bearer-token auth, JSON bodies):

| BillingServ event | Method + endpoint | Body |
|---|---|---|
| Account created / order paid | `POST https://PANEL:2087/api/billing/provision` | `{"username","email","password","domain","package"}` |
| Suspend on non-payment | `POST …/api/billing/suspend` | `{"username"}` |
| Reactivate on payment | `POST …/api/billing/unsuspend` | `{"username"}` |
| Cancellation | `POST …/api/billing/terminate` | `{"username"}` |
| Plan change | `POST …/api/billing/change-package` | `{"username","package"}` |
| Client "log in to panel" | `GET …/api/billing/sso?username=…` | — |

   Auth header on every call: `Authorization: Bearer sgb_YOUR_KEY`

3. `package` must exactly match a user package in SafeGuard Panel
   (**Owner → Packages**).

Responses use the panel envelope `{"success": bool, "data": …, "error": str}`.
Full schema: panel **Owner → API Docs**, category *Billing Integration*.
