# Paymenter server extension

## Structure

```
paymenter/
└── SafeguardPanel/
    └── SafeguardPanel.php     ← copy directory to {paymenter}/extensions/Servers/SafeguardPanel/
```

## Install

1. Copy the extension into Paymenter:

   ```sh
   cp -r SafeguardPanel /path/to/paymenter/extensions/Servers/
   ```

2. Generate the provisioning key in SafeGuard Panel:
   **Owner → Billing System → Provisioning API Key → Generate Key**.

3. In Paymenter admin: **Extensions → Servers → SafeGuard Panel → Enable**
   - **Panel hostname**: your panel host
   - **Provisioning API key (sgb_…)**: paste the key

4. On each product, set **Panel package name** to exactly match a user
   package in SafeGuard Panel (**Owner → Packages**).

> ⚠️ Paymenter's extension API evolves between versions — if a method
> signature has changed in your release, the adjustments are small: every
> action is one HTTP call to `/api/billing/*` (see
> [the generic hooks](../README.md)).

Status: 🧪 reference implementation — test in staging before production.
