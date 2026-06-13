# Blesta module

## Structure

```
blesta/
└── safeguard_panel/
    ├── safeguard_panel.php            ← the module class
    ├── config.json                    ← module metadata (Blesta 4.7+)
    └── language/
        └── en_us/
            └── safeguard_panel.php    ← language strings
```

## Install

1. Copy the module into Blesta:

   ```sh
   cp -r safeguard_panel /path/to/blesta/components/modules/
   ```

2. In Blesta staff area: **Settings → Modules → Available → SafeGuard Panel → Install**.

3. Generate the provisioning key in SafeGuard Panel:
   **Owner → Billing System → Provisioning API Key → Generate Key**.

4. **Add a server** under the module:
   - **Panel hostname**: your panel host
   - **Provisioning API key (sgb_…)**: paste the key

5. Create a package using the module; set **Panel package name** to exactly
   match a user package in SafeGuard Panel (**Owner → Packages**).

Status: 🧪 reference implementation — Blesta's module surface is the largest
of the eight platforms; the service lifecycle (provision/suspend/unsuspend/
terminate) is implemented, while the management views (`manage.pdt`,
`add_row.pdt`) use Blesta's default scaffolding and may need cosmetic
adjustment for your Blesta version. Test in staging before production.
