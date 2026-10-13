# Published JS package and wcSettings contract

**Source:**

- #68271: restored a `createElement` import.
- #55795: restored a removed `@woocommerce/data` export.
- #55876: navigation store restored and deprecated.
- #55706: restored `wcSettings.admin.onboarding`.
- #59693: `registerCheckoutFilters` compat in the iAPI Mini Cart.
- Earlier review findings: `Form.setValue` behavior for zero-segment names; `createElement` / `Fragment` import removed while JSX stayed, crashing the published package; importing `@woocommerce/date` changed the global moment locale.

**What broke:** Published `@woocommerce/*` packages and `wcSettings` keys are used by extensions. Removed exports, keys, and stores broke them, and the published build crashed when JSX lost its runtime import.

**Pattern to catch:** In `packages/js` or blocks packages: removing or renaming exports, props, store selectors, or `wcSettings` / AssetDataRegistry keys. JSX without the classic runtime import. Import-time side effects.

**How to confirm:** Check the package `index` exports and changelog. Does the published build use the classic JSX runtime? Grep `getSetting( 'key' )` readers.

**Don't flag when:** Private (non-exported) modules. Additive exports.
