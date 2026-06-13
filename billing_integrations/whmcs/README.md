# WHMCS server module

Lets WHMCS automatically create, suspend, terminate and log in to SafeGuard
Panel accounts when customers order, lapse or cancel.

## Structure

```
whmcs/
└── safeguardpanel/
    └── safeguardpanel.php     ← the server module (folder + file names are
                                  required by WHMCS to match the module name)
```

## Install

1. **Copy the module into WHMCS** (on your WHMCS server):

   ```sh
   cp -r safeguardpanel /path/to/whmcs/modules/servers/
   ```

2. **Generate the provisioning key** in SafeGuard Panel:
   **Owner → Billing System → Provisioning API Key → Generate Key**
   (copy the `sgb_…` key — it is shown once).

3. **Add the server in WHMCS**: *System Settings → Servers → Add New Server*
   - **Module**: `SafeGuard Panel`
   - **Hostname**: your panel host (e.g. `panel.example.com`)
   - **Access Hash**: paste the `sgb_…` provisioning key
   - **Secure**: ✅ tick (the panel terminates TLS on port 2087)
   - Username/password fields: leave empty — the key is the credential.

4. **Create a product**: *System Settings → Products/Services → Create a New Product*
   - **Module Settings** tab → Module Name: `SafeGuard Panel`
   - **Panel Package Name**: must exactly match a *user* package name that
     exists in SafeGuard Panel (**Owner → Packages**), e.g. `Starter`.
   - Recommended: "Automatically setup the product as soon as the first
     payment is received".

5. **Test**: place a test order and mark it paid — the account appears in
   SafeGuard Panel (**Owner → User Accounts**) with the domain and package
   applied. The client's *Login to Control Panel* button uses SSO.

## What each WHMCS action calls

| WHMCS event | Panel endpoint |
|---|---|
| Create | `POST /api/billing/provision` |
| Suspend / Unsuspend | `POST /api/billing/suspend` · `/unsuspend` |
| Terminate | `POST /api/billing/terminate` |
| Change Package | `POST /api/billing/change-package` |
| Client/Admin SSO button | `GET /api/billing/sso` |

## Troubleshooting

- **"Provision failed: invalid or missing billing integration key"** — the
  Access Hash doesn't match the panel's current key. Re-generate and update.
- **Account created but no package applied** — the product's *Panel Package
  Name* doesn't exactly match a user package in the panel.
- All module calls are recorded in the panel's **Audit Log** with
  `billing_*` actions.
