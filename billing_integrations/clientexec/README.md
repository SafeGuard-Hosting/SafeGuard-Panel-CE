# ClientExec server plugin

## Structure

```
clientexec/
└── safeguardpanel/
    └── PluginSafeguardpanel.php   ← copy directory to {clientexec}/plugins/server/safeguardpanel/
```

## Install

1. Copy the plugin into ClientExec:

   ```sh
   cp -r safeguardpanel /path/to/clientexec/plugins/server/
   ```

2. Generate the provisioning key in SafeGuard Panel:
   **Owner → Billing System → Provisioning API Key → Generate Key**.

3. In ClientExec: **Settings → Plugins → Server Plugins → SafeGuard Panel**
   - **Hostname**: your panel host
   - **Provisioning API Key**: paste the `sgb_…` key

4. Assign the plugin to a server, then to a product. The product's
   *name on server* must exactly match a user package in SafeGuard Panel
   (**Owner → Packages**).

Status: 🧪 reference implementation — test in staging before production.
