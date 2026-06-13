# BoxBilling server manager

> ℹ️ BoxBilling is no longer actively maintained — if you're starting fresh,
> use **[FOSSBilling](../fossbilling/)** (its maintained successor) instead.

## Structure

```
boxbilling/
└── Safeguardpanel/
    └── Safeguardpanel.php     ← copy to {boxbilling}/bb-library/Server/Manager/Safeguardpanel.php
```

## Install

1. Copy the manager into BoxBilling:

   ```sh
   cp Safeguardpanel/Safeguardpanel.php /path/to/boxbilling/bb-library/Server/Manager/
   ```

2. Generate the provisioning key in SafeGuard Panel:
   **Owner → Billing System → Provisioning API Key → Generate Key**.

3. In BoxBilling: **Configuration → Servers → New server**
   - **Server manager**: `SafeGuard Panel`
   - **Hostname**: your panel host
   - **Access Hash / API key field**: paste the `sgb_…` key

4. Create a hosting plan whose **name exactly matches** a user package in
   SafeGuard Panel (**Owner → Packages**) and attach it to the server.

Status: 🧪 reference implementation — test in staging before production.
