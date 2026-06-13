# HostBill hosting module

## Structure

```
hostbill/
└── safeguardpanel/
    └── class.safeguardpanel.php   ← copy directory to {hostbill}/includes/modules/Hosting/safeguardpanel/
```

## Install

1. Copy the module into HostBill:

   ```sh
   cp -r safeguardpanel /path/to/hostbill/includes/modules/Hosting/
   ```

2. Generate the provisioning key in SafeGuard Panel:
   **Owner → Billing System → Provisioning API Key → Generate Key**.

3. In HostBill: **Settings → Apps → Add new App**
   - **Application**: `SafeGuard Panel`
   - **Hostname**: your panel host
   - **Hash**: paste the `sgb_…` key

4. Create a product, connect it to the app, and set **Panel package name**
   to exactly match a user package in SafeGuard Panel (**Owner → Packages**).

> 💡 **Alternative:** HostBill can also run WHMCS-format modules through its
> compatibility layer — the production-ready [`../whmcs/`](../whmcs/) module
> is an option if you prefer it.

Status: 🧪 reference implementation — test in staging before production.
