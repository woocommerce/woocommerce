# Class loads during in-place upgrade

**Source:**

- #65337 (10.8.1): the `DefaultCustomerAddress` enum, referenced from `class-wc-settings-general.php` in the update.php footer, fatalled on the stale classmap.
- #66081 / #66214 (10.9.2): settings SDK classes needed guards; a removed controller needed a stub.
- #67136: stubs for two removed REST controllers.
- #65338, #65630, #65674: PSR-4 fallback autoloader.
- #57257 (9.9), #56982 (constant during install), #54418 (constants used before the class loaded), #63066.

**What broke:** During the in-place update request, the old autoloader classmap is still active. Code that touched a class new in this version, or a file this version removed, fatalled mid-upgrade. This shipped in 5+ hotfix releases.

**Pattern to catch:** A file that runs during the update request references a class, enum, or constant that is new in this version, or still references a class whose file this version removes. Such files include settings pages, AssetDataRegistry, REST route registration, and `admin_print_footer_scripts` callbacks.

**How to confirm:** Is the new symbol referenced from legacy `includes/` files or REST registration? Is a referenced class removed in this PR? Look for `class_exists` guards and compat stubs.

**Don't flag when:** New classes used only from other new classes, or only after `woocommerce_loaded` on a fresh request.
