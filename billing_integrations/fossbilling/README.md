# FOSSBilling server manager

## Structure

```
fossbilling/
└── Safeguardpanel/
    └── Safeguardpanel.php     ← copy to {fossbilling}/library/Server/Manager/Safeguardpanel.php
```

## Install

1. Copy the manager into FOSSBilling:

   ```sh
   cp Safeguardpanel/Safeguardpanel.php /path/to/fossbilling/library/Server/Manager/
   ```

2. Generate the provisioning key in SafeGuard Panel:
   **Owner → Billing System → Provisioning API Key → Generate Key**.

3. In FOSSBilling: **System → Hosting plans and servers → New server**
   - **Manager**: `SafeGuard Panel`
   - **Hostname**: your panel host
   - **Provisioning API key (sgb_…)**: paste the key

4. Create a hosting plan whose **name exactly matches** a user package in
   SafeGuard Panel (**Owner → Packages**) and attach it to the server.

5. Place a test order → the account appears in the panel.

Status: 🧪 reference implementation — test in staging before production.
