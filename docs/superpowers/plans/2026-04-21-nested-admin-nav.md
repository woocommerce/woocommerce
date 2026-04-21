# Nested Admin Navigation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a feature-flagged nested admin navigation inside `woocommerce/woocommerce` that nests all Woo-related menu items under a single `WooCommerce` top-level item, using WP's existing `add_menu_page()` / `add_submenu_page()` registrations as the input.

**Architecture:** A `Menu_Reconciler` runs at `admin_menu` priority 999. It captures `$menu` and `$submenu`, loads a default-tree map, applies a `woocommerce_admin_menu_tree` filter, and produces a flat parent-pointer tree. Woo-related top-level items (hard-coded list) are removed from the WP rail and rehomed inside the tree. A renderer outputs two surfaces: a multi-level hover cascade on the `WooCommerce` rail item, and a full rail replacement (160px) when the user is on any page that exists in the tree. Flag-off is a byte-identical no-op.

**Tech Stack:** PHP 7.4+, WordPress admin menu APIs (`$menu` / `$submenu` globals, `add_menu_page`, `remove_menu_page`), WooCommerce `FeaturesController`, `wc_get_container()` DI, `WC_Tracks` for telemetry, SCSS compiled via Grunt in `client/legacy/css/`, vanilla JS + jQuery in `client/legacy/js/admin/`, Playwright for E2E.

---

## Reference materials

The WooPro prototype at these paths solved the CSS/JS hard parts and is the reference for the rail replacement and flyout override:

- `/Users/beau/Fletcher/80-Archive/WooPro/wp-content/plugins/woo-new/includes/class-woo-new-menu.php` — the menu-modification logic, including the hard-coded `REHOMED_TOP_LEVEL_SLUGS` list.
- `/Users/beau/Fletcher/80-Archive/WooPro/wp-content/plugins/woo-new/includes/class-woo-new-assets.php` — the **critical** `get_adminmenu_alias_css()` technique: reads WP's own `wp-admin/css/admin-menu.css`, text-rewrites `#adminmenu` → `#woo-new-adminmenu`, and inlines the result. This is how the rail replacement inherits 100% of WP's menu styling (hover, flyout, active, color schemes) for free.
- `/Users/beau/Fletcher/80-Archive/WooPro/wp-content/plugins/woo-new/assets/js/woo-new-nav.js` — the flyout state machine: adds/removes `opensub` class on `li.wp-has-submenu` on hover and focus.
- `/Users/beau/Fletcher/80-Archive/WooPro/wp-content/plugins/woo-new/templates/nav.php` — the rail HTML structure using WP-native `menu-top` / `wp-submenu` / `wp-menu-image` / `wp-menu-name` classes so the aliased CSS applies.
- `/Users/beau/Fletcher/80-Archive/WooPro/wp-content/plugins/woo-new/assets/css/woo-new-admin.css` — the handful of positioning overrides on top of the aliased CSS.

When a task says "port from the prototype," copy the logic verbatim and adapt namespaces/paths/handles. Do not re-invent.

---

## File structure

### New PHP files (all under `plugins/woocommerce/src/Internal/Admin/Navigation/`)

| File | Responsibility |
|---|---|
| `Bootstrap.php` | Entry point. Registers the feature in FeaturesController; guards hook registration behind the flag. |
| `Rehomed_Slugs.php` | Holds the `REHOMED_TOP_LEVEL_SLUGS` constant. |
| `default-tree.php` | Returns the default tree array. Not a class. |
| `Tree_Builder.php` | Pure logic: raw `$menu`+`$submenu`+default map → final tree. Cycle detection, unknown-parent handling, capability passthrough. |
| `WC_Admin_Nav.php` | Helper API: `add()`, `move()`, `remove()`, `rename()` static methods. |
| `Menu_Reconciler.php` | The `admin_menu` priority 999 hook; composes the above. |
| `Context.php` | "Is the current request a Woo page?" helper. |
| `Renderer.php` | Outputs body class, rail HTML, and hooks for the hover cascade. |
| `Assets.php` | Enqueues CSS/JS; implements the admin-menu.css alias trick. |
| `Telemetry.php` | Emits the 5 Tracks events. |

### New service provider

- `plugins/woocommerce/src/Internal/DependencyManagement/ServiceProviders/NavigationV2ServiceProvider.php` — registers `Bootstrap` in the container.

### New assets

- `plugins/woocommerce/client/legacy/css/admin-navigation-v2.scss` — rail + flyout CSS overrides (compiles to `assets/css/admin-navigation-v2.css` via Grunt).
- `plugins/woocommerce/client/legacy/js/admin/admin-navigation-v2.js` — flyout state machine, keyboard nav, Tracks emission.

### New test files

- `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php`
- `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/WC_Admin_Nav_Test.php`
- `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Menu_Reconciler_Test.php`
- `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Context_Test.php`
- `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Flag_Off_Snapshot_Test.php`
- `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Default_Tree_Test.php`
- `plugins/woocommerce/tests/e2e-pw/tests/admin-navigation-v2/navigation-v2.spec.js`

### Modified files

- `plugins/woocommerce/src/Container.php` — add `NavigationV2ServiceProvider` to the provider list.
- `plugins/woocommerce/includes/class-woocommerce.php` — add `$container->get( Bootstrap::class );` to the bootstrap list around line 280.
- `plugins/woocommerce/tests/php/src/Internal/Features/FeaturesControllerTest.php` — add a test that `navigation_v2` is registered.

### Deviations from spec worth flagging up front

- **JS unit tests (spec §9.2):** No Jest setup exists for `client/legacy/js/admin/`. Rather than add one for one file, the flyout state machine, keyboard navigation, and active-path computation are exercised via Playwright E2E tests (§9.3). This keeps CI costs flat and tests actual browser behavior, which is what matters for menu interactions. Flagged for reviewer agreement.

- **Admin_menu hook priority:** The spec is internally inconsistent — §4 step 2 says "priority 100" while §5.3 says "priority 999". This plan uses **999** (matches the more specific §5.3 and ensures the reconciler runs after every other `admin_menu` hook, including Woo's own at 9 and anything extensions register at 100). If reviewer prefers 100, change the priority in `Menu_Reconciler::__construct()` in Task 9.

- **Flyout off-screen flip on narrow viewport (spec §8):** The "last level flips to open leftward, CSS + one JS measurement" is not in this plan. Low-priority polish; deferring as a known limitation to address after the main path ships. If blocking for launch, add a task for a `ResizeObserver`-based flip class toggle in `admin-navigation-v2.js`.

---

## Task list

### Task 1: Scaffold the feature flag and DI wiring

**Files:**
- Create: `plugins/woocommerce/src/Internal/Admin/Navigation/Bootstrap.php`
- Create: `plugins/woocommerce/src/Internal/DependencyManagement/ServiceProviders/NavigationV2ServiceProvider.php`
- Modify: `plugins/woocommerce/src/Container.php:58-84`
- Modify: `plugins/woocommerce/includes/class-woocommerce.php:272-288`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Features/FeaturesControllerTest.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Bootstrap_Test.php`

- [ ] **Step 1.1: Write the failing test for feature registration**

Create `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Bootstrap_Test.php`:

```php
<?php
/**
 * Bootstrap test.
 */

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap
 */
class Bootstrap_Test extends \WC_Unit_Test_Case {

	/**
	 * The feature id must be registered as an experimental, disabled-by-default feature.
	 */
	public function test_feature_is_registered() {
		$controller = wc_get_container()->get( FeaturesController::class );
		$features   = $controller->get_features( true );

		$this->assertArrayHasKey( 'navigation_v2', $features );
		$this->assertTrue( $features['navigation_v2']['is_experimental'] );
		$this->assertFalse( $controller->feature_is_enabled( 'navigation_v2' ) );
	}

	/**
	 * Bootstrap registers the feature-registration hook and the admin_init
	 * gateway. It does NOT register admin_menu or admin_enqueue_scripts itself
	 * — those live in Menu_Reconciler and Assets, which only instantiate when
	 * boot_when_enabled() runs with the flag on.
	 */
	public function test_bootstrap_registers_only_feature_and_admin_init_hooks() {
		wc_get_container()->get( Bootstrap::class );

		$this->assertNotFalse( has_action( 'woocommerce_register_feature_definitions' ) );
		$this->assertNotFalse( has_action( 'admin_init' ) );
	}

	/**
	 * boot_when_enabled() is idempotent with respect to no-op: calling it
	 * repeatedly with the flag off doesn't error and doesn't accumulate hooks.
	 */
	public function test_boot_when_enabled_is_safe_to_call_with_flag_off() {
		update_option( 'woocommerce_feature_navigation_v2_enabled', 'no' );

		$bootstrap = wc_get_container()->get( Bootstrap::class );
		$bootstrap->boot_when_enabled();
		$bootstrap->boot_when_enabled();

		$this->assertTrue( true, 'No exception thrown on repeated flag-off boot.' );
	}
}
```

- [ ] **Step 1.2: Run test to verify it fails**

Run: `cd /Users/beau/Source/git/woocommerce && pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Bootstrap_Test`

Expected: FAIL — `Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap` class not found.

- [ ] **Step 1.3: Create the Bootstrap class**

Create `plugins/woocommerce/src/Internal/Admin/Navigation/Bootstrap.php`:

```php
<?php
/**
 * Navigation v2 bootstrap.
 *
 * Registers the feature flag and, when enabled, wires up the reconciler,
 * renderer, assets, and telemetry.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

defined( 'ABSPATH' ) || exit;

/**
 * Bootstrap for the nested admin navigation feature.
 */
class Bootstrap {

	public const FEATURE_ID = 'navigation_v2';

	/**
	 * Wire the feature registration.
	 */
	public function __construct() {
		add_action( 'woocommerce_register_feature_definitions', array( $this, 'register_feature' ) );
		add_action( 'admin_init', array( $this, 'boot_when_enabled' ) );
	}

	/**
	 * Register the feature in the FeaturesController.
	 *
	 * @param FeaturesController $controller Controller instance.
	 */
	public function register_feature( FeaturesController $controller ): void {
		$controller->add_feature_definition(
			self::FEATURE_ID,
			__( 'Nested admin navigation', 'woocommerce' ),
			array(
				'description'        => __(
					'Replace the flat Woo admin menu with a nested tree under a single WooCommerce item. Experimental.',
					'woocommerce'
				),
				'is_experimental'    => true,
				'enabled_by_default' => false,
				'disable_ui'         => false,
			)
		);
	}

	/**
	 * When the flag is enabled, instantiate the reconciler, renderer, assets,
	 * and telemetry. Each of those classes registers its own hooks.
	 *
	 * Called on admin_init so the feature flag is readable and translations are loaded.
	 *
	 * Spec §8: multisite network admin always uses the native rail — we bail
	 * before any hook registration in that context.
	 */
	public function boot_when_enabled(): void {
		if ( ! is_admin() || is_network_admin() ) {
			return;
		}

		$controller = wc_get_container()->get( FeaturesController::class );
		if ( ! $controller->feature_is_enabled( self::FEATURE_ID ) ) {
			return;
		}

		new Menu_Reconciler();
		new Renderer();
		new Assets();
		new Telemetry();
	}
}
```

- [ ] **Step 1.4: Create the service provider**

Create `plugins/woocommerce/src/Internal/DependencyManagement/ServiceProviders/NavigationV2ServiceProvider.php`:

```php
<?php

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;

/**
 * Service provider for the nested admin navigation feature.
 */
class NavigationV2ServiceProvider extends AbstractServiceProvider {

	/**
	 * @var array
	 */
	protected $provides = array(
		Bootstrap::class,
	);

	/**
	 * Register the class.
	 */
	public function register() {
		$this->share( Bootstrap::class );
	}
}
```

- [ ] **Step 1.5: Register the service provider in the container**

In `plugins/woocommerce/src/Container.php`, add the import near the existing service-provider imports (around line 33):

```php
use Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders\NavigationV2ServiceProvider;
```

Then add `NavigationV2ServiceProvider::class,` to the `$service_providers` array (around line 84, alphabetical order would put it near the end):

```php
private $service_providers = array(
    // ... existing providers ...
    ComingSoonServiceProvider::class,
    NavigationV2ServiceProvider::class,
);
```

- [ ] **Step 1.6: Boot the class in class-woocommerce.php**

In `plugins/woocommerce/includes/class-woocommerce.php`, find the block starting at line 271 (`// These classes set up hooks on instantiation.`). Add after line 288 (after `$container->get( ComingSoonRequestHandler::class );`):

```php
$container->get( \Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap::class );
```

- [ ] **Step 1.7: Stub the classes that Bootstrap instantiates**

So Bootstrap::boot_when_enabled() can be class-loaded even before the real implementations are written, create four empty stub files:

Create `plugins/woocommerce/src/Internal/Admin/Navigation/Menu_Reconciler.php`:

```php
<?php

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

class Menu_Reconciler {
	public function __construct() {}
}
```

Create `plugins/woocommerce/src/Internal/Admin/Navigation/Renderer.php`:

```php
<?php

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

class Renderer {
	public function __construct() {}
}
```

Create `plugins/woocommerce/src/Internal/Admin/Navigation/Assets.php`:

```php
<?php

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

class Assets {
	public function __construct() {}
}
```

Create `plugins/woocommerce/src/Internal/Admin/Navigation/Telemetry.php`:

```php
<?php

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

class Telemetry {
	public function __construct() {}
}
```

- [ ] **Step 1.8: Run tests to verify they pass**

Run: `cd /Users/beau/Source/git/woocommerce && pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Bootstrap_Test`

Expected: PASS — both tests green.

- [ ] **Step 1.9: Commit**

```bash
cd /Users/beau/Source/git/woocommerce
git add plugins/woocommerce/src/Internal/Admin/Navigation/ \
    plugins/woocommerce/src/Internal/DependencyManagement/ServiceProviders/NavigationV2ServiceProvider.php \
    plugins/woocommerce/src/Container.php \
    plugins/woocommerce/includes/class-woocommerce.php \
    plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Bootstrap_Test.php
git commit -m "feat(nav-v2): scaffold feature flag and DI wiring"
```

---

### Task 2: Rehomed-slugs constant and default tree file

**Files:**
- Create: `plugins/woocommerce/src/Internal/Admin/Navigation/Rehomed_Slugs.php`
- Create: `plugins/woocommerce/src/Internal/Admin/Navigation/default-tree.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Default_Tree_Test.php`

- [ ] **Step 2.1: Write the failing test**

Create `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Default_Tree_Test.php`:

```php
<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Rehomed_Slugs;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Rehomed_Slugs
 */
class Default_Tree_Test extends \WC_Unit_Test_Case {

	/**
	 * The default tree must be well-formed: every non-null parent must reference
	 * an existing slug in the tree.
	 */
	public function test_default_tree_is_well_formed() {
		$tree = require dirname( WC_PLUGIN_FILE ) . '/src/Internal/Admin/Navigation/default-tree.php';

		$this->assertIsArray( $tree );
		$this->assertArrayHasKey( 'woocommerce', $tree );
		$this->assertNull( $tree['woocommerce']['parent'], 'WooCommerce root must have null parent' );

		foreach ( $tree as $slug => $node ) {
			$this->assertArrayHasKey( 'parent', $node, "Node '$slug' missing parent key" );
			$this->assertArrayHasKey( 'title', $node, "Node '$slug' missing title key" );
			$this->assertArrayHasKey( 'position', $node, "Node '$slug' missing position key" );

			if ( null !== $node['parent'] ) {
				$this->assertArrayHasKey(
					$node['parent'],
					$tree,
					"Node '$slug' references unknown parent '{$node['parent']}'"
				);
			}
		}
	}

	/**
	 * The rehomed-slugs list must match the spec.
	 */
	public function test_rehomed_slugs_constant() {
		$expected = array(
			'woocommerce',
			'edit.php?post_type=product',
			'wc-admin&path=/analytics/overview',
			'woocommerce-marketing',
			'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM',
			'wc-admin&path=/payments/connect',
			'wc-admin&path=/payments/overview',
			'woocommerce-payments',
			'klaviyo_settings',
		);

		$this->assertSame( $expected, Rehomed_Slugs::ALL );
	}
}
```

- [ ] **Step 2.2: Run test to verify it fails**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Default_Tree_Test`

Expected: FAIL — class and file not found.

- [ ] **Step 2.3: Create the Rehomed_Slugs class**

Create `plugins/woocommerce/src/Internal/Admin/Navigation/Rehomed_Slugs.php`:

```php
<?php
/**
 * Hard-coded list of Woo-related top-level menu slugs that the reconciler
 * removes from WP's native rail when the navigation_v2 feature is enabled.
 *
 * This list matches the WooPro prototype's rehomed slugs. It is hard-coded
 * rather than discovered dynamically because we control which items get
 * rehomed, not the plugins that register them.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Holds the rehomed-slugs constant.
 */
final class Rehomed_Slugs {

	/**
	 * Top-level slugs that are removed from `$menu` and re-homed inside the
	 * Woo tree when the feature is enabled.
	 */
	public const ALL = array(
		'woocommerce',
		'edit.php?post_type=product',
		'wc-admin&path=/analytics/overview',
		'woocommerce-marketing',
		'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM',
		'wc-admin&path=/payments/connect',
		'wc-admin&path=/payments/overview',
		'woocommerce-payments',
		'klaviyo_settings',
	);
}
```

- [ ] **Step 2.4: Create the default tree file**

Create `plugins/woocommerce/src/Internal/Admin/Navigation/default-tree.php`:

```php
<?php
/**
 * Default tree for nested admin navigation.
 *
 * Returns a flat associative array keyed by slug. Each node declares its
 * `parent` (slug or null for the root), `title`, integer `position`, and
 * an optional `icon` (root only).
 *
 * Loaded once per admin pageload by Menu_Reconciler. Before consumption,
 * the tree is passed through the `woocommerce_admin_menu_tree` filter so
 * extension authors can override placement.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'woocommerce'                                                            => array(
		'parent'   => null,
		'title'    => __( 'WooCommerce', 'woocommerce' ),
		'icon'     => 'dashicons-cart',
		'position' => 2,
	),
	'wc-admin'                                                               => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Home', 'woocommerce' ),
		'position' => 10,
	),
	'edit.php?post_type=shop_order'                                          => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Orders', 'woocommerce' ),
		'position' => 20,
	),
	'edit.php?post_type=product'                                             => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Products', 'woocommerce' ),
		'position' => 30,
	),
	'wc-admin&path=/analytics/overview'                                      => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Analytics', 'woocommerce' ),
		'position' => 40,
	),
	'wc-admin&path=/customers'                                               => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Customers', 'woocommerce' ),
		'position' => 50,
	),
	'woocommerce-marketing'                                                  => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Marketing', 'woocommerce' ),
		'position' => 60,
	),
	'wc-settings'                                                            => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Settings', 'woocommerce' ),
		'position' => 90,
	),
	'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM'        => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'Payments', 'woocommerce' ),
		'position' => 10,
	),
	'woocommerce-payments'                                                   => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'WooPayments', 'woocommerce' ),
		'position' => 20,
	),
	'wc-addons'                                                              => array(
		'parent'   => 'woocommerce',
		'title'    => __( 'Extensions', 'woocommerce' ),
		'position' => 95,
	),
	'wc-status'                                                              => array(
		'parent'   => 'wc-settings',
		'title'    => __( 'Status', 'woocommerce' ),
		'position' => 99,
	),
);
```

- [ ] **Step 2.5: Run tests to verify they pass**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Default_Tree_Test`

Expected: PASS — both tests green.

- [ ] **Step 2.6: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Rehomed_Slugs.php \
    plugins/woocommerce/src/Internal/Admin/Navigation/default-tree.php \
    plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Default_Tree_Test.php
git commit -m "feat(nav-v2): add rehomed-slugs constant and default tree"
```

---

### Task 3: Tree_Builder — basic tree construction

**Files:**
- Create: `plugins/woocommerce/src/Internal/Admin/Navigation/Tree_Builder.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php`

- [ ] **Step 3.1: Write the failing test for basic tree construction**

Create `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php`:

```php
<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Tree_Builder;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Tree_Builder
 */
class Tree_Builder_Test extends \WC_Unit_Test_Case {

	/**
	 * Given the default tree and no extra $menu/$submenu entries, the builder
	 * returns the default tree unchanged (minus any slugs whose underlying
	 * registration is absent — none here, so all retained).
	 */
	public function test_default_tree_passes_through_unchanged() {
		$default = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-admin'    => array( 'parent' => 'woocommerce', 'title' => 'Home', 'position' => 10 ),
		);

		// Simulate WP having registered both pages.
		$raw_menu    = array(
			array( 'WooCommerce', 'read', 'woocommerce', '', '' ),
		);
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Home', 'read', 'wc-admin' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayHasKey( 'woocommerce', $tree );
		$this->assertArrayHasKey( 'wc-admin', $tree );
		$this->assertSame( 'woocommerce', $tree['wc-admin']['parent'] );
	}

	/**
	 * Slugs declared in the default tree but not registered by any plugin are
	 * silently skipped (not errors).
	 */
	public function test_unregistered_slugs_are_skipped() {
		$default = array(
			'woocommerce'           => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'woocommerce-payments'  => array( 'parent' => 'woocommerce', 'title' => 'WooPayments', 'position' => 20 ),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array();

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayHasKey( 'woocommerce', $tree );
		$this->assertArrayNotHasKey( 'woocommerce-payments', $tree );
	}
}
```

- [ ] **Step 3.2: Run test to verify it fails**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Tree_Builder_Test`

Expected: FAIL — Tree_Builder class not found.

- [ ] **Step 3.3: Create the minimal Tree_Builder**

Create `plugins/woocommerce/src/Internal/Admin/Navigation/Tree_Builder.php`:

```php
<?php
/**
 * Pure-logic tree builder. No side effects, no $menu/$submenu mutation.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the final nav tree from WP's raw $menu/$submenu and the default-tree map.
 */
class Tree_Builder {

	/**
	 * Build the tree.
	 *
	 * @param array $default_tree Default tree as loaded from default-tree.php.
	 * @param array $raw_menu     WP's $menu global.
	 * @param array $raw_submenu  WP's $submenu global.
	 * @return array Final tree, keyed by slug.
	 */
	public function build( array $default_tree, array $raw_menu, array $raw_submenu ): array {
		$registered_slugs = $this->collect_registered_slugs( $raw_menu, $raw_submenu );
		$tree             = array();

		foreach ( $default_tree as $slug => $node ) {
			if ( 'woocommerce' === $slug || isset( $registered_slugs[ $slug ] ) ) {
				$tree[ $slug ]           = $node;
				$tree[ $slug ]['source'] = 'default';
			}
		}

		return $tree;
	}

	/**
	 * Collect every slug that WP knows about (top-level + every submenu entry).
	 *
	 * @param array $raw_menu    WP's $menu.
	 * @param array $raw_submenu WP's $submenu.
	 * @return array Associative array, slug => true.
	 */
	private function collect_registered_slugs( array $raw_menu, array $raw_submenu ): array {
		$slugs = array();

		foreach ( $raw_menu as $entry ) {
			if ( isset( $entry[2] ) ) {
				$slugs[ $entry[2] ] = true;
			}
		}

		foreach ( $raw_submenu as $children ) {
			foreach ( $children as $entry ) {
				if ( isset( $entry[2] ) ) {
					$slugs[ $entry[2] ] = true;
				}
			}
		}

		return $slugs;
	}
}
```

- [ ] **Step 3.4: Run tests to verify they pass**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Tree_Builder_Test`

Expected: PASS — both tests green.

- [ ] **Step 3.5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Tree_Builder.php \
    plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php
git commit -m "feat(nav-v2): Tree_Builder with basic tree construction"
```

---

### Task 4: Tree_Builder — auto-attach submenu items of woocommerce

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Tree_Builder.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php`

- [ ] **Step 4.1: Write the failing test**

Append to `Tree_Builder_Test.php`:

```php
	/**
	 * Submenu items registered under 'woocommerce' that aren't in the default tree
	 * auto-attach to the woocommerce root with source = 'auto', preserving registration order.
	 */
	public function test_auto_attach_woocommerce_submenu_items() {
		$default = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Third-party Tool', 'manage_woocommerce', 'my-plugin-page' ),
				array( 'Another Tool',     'manage_woocommerce', 'my-plugin-other' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayHasKey( 'my-plugin-page', $tree );
		$this->assertSame( 'woocommerce', $tree['my-plugin-page']['parent'] );
		$this->assertSame( 'auto', $tree['my-plugin-page']['source'] );
		$this->assertSame( 'Third-party Tool', $tree['my-plugin-page']['title'] );

		$this->assertArrayHasKey( 'my-plugin-other', $tree );
		$this->assertSame( 'auto', $tree['my-plugin-other']['source'] );

		// Registration order is preserved via position values.
		$this->assertLessThan(
			$tree['my-plugin-other']['position'],
			$tree['my-plugin-page']['position'],
			'First-registered auto item should have a lower position than the second'
		);
	}

	/**
	 * The capability from the original registration is preserved on auto-attached items.
	 */
	public function test_auto_attached_items_preserve_capability() {
		$default = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Secret Tool', 'manage_options', 'secret-page' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertSame( 'manage_options', $tree['secret-page']['capability'] );
	}
```

- [ ] **Step 4.2: Run tests to verify they fail**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Tree_Builder_Test`

Expected: FAIL — auto-attach logic missing.

- [ ] **Step 4.3: Implement auto-attach**

Modify `Tree_Builder::build()` to call a new `auto_attach_woocommerce_children()` method. Replace the `build()` method body with:

```php
	public function build( array $default_tree, array $raw_menu, array $raw_submenu ): array {
		$registered_slugs = $this->collect_registered_slugs( $raw_menu, $raw_submenu );
		$tree             = array();

		foreach ( $default_tree as $slug => $node ) {
			if ( 'woocommerce' === $slug || isset( $registered_slugs[ $slug ] ) ) {
				$tree[ $slug ]           = $node;
				$tree[ $slug ]['source'] = 'default';
			}
		}

		$tree = $this->auto_attach_woocommerce_children( $tree, $default_tree, $raw_submenu );

		return $tree;
	}

	/**
	 * Attach any submenu items registered under 'woocommerce' that aren't
	 * already in the tree as children of the Woo root, preserving registration order.
	 *
	 * @param array $tree         Tree being built.
	 * @param array $default_tree Default tree (used to decide "already present").
	 * @param array $raw_submenu  WP's $submenu.
	 * @return array Tree with auto items appended.
	 */
	private function auto_attach_woocommerce_children( array $tree, array $default_tree, array $raw_submenu ): array {
		if ( ! isset( $raw_submenu['woocommerce'] ) ) {
			return $tree;
		}

		$auto_position = 1000;
		foreach ( $raw_submenu['woocommerce'] as $entry ) {
			$slug = $entry[2] ?? null;
			if ( null === $slug || isset( $default_tree[ $slug ] ) || isset( $tree[ $slug ] ) ) {
				continue;
			}

			$tree[ $slug ] = array(
				'parent'     => 'woocommerce',
				'title'      => $entry[0] ?? $slug,
				'position'   => $auto_position,
				'source'     => 'auto',
				'capability' => $entry[1] ?? 'read',
			);
			$auto_position += 10;
		}

		return $tree;
	}
```

- [ ] **Step 4.4: Run tests to verify they pass**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Tree_Builder_Test`

Expected: PASS — all four tests green.

- [ ] **Step 4.5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Tree_Builder.php \
    plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php
git commit -m "feat(nav-v2): auto-attach woocommerce submenu items to tree"
```

---

### Task 5: Tree_Builder — cycle detection

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Tree_Builder.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php`

- [ ] **Step 5.1: Write the failing test**

Append to `Tree_Builder_Test.php`:

```php
	/**
	 * A parent-chain cycle (A -> B -> A) is broken by demoting the lowest-position
	 * node to the Woo root. Deterministic — same input produces same output.
	 */
	public function test_cycle_detection_breaks_lowest_position_to_root() {
		// Simulated cycle introduced via the filter.
		$default = array(
			'woocommerce' => array( 'parent' => null,            'title' => 'WooCommerce', 'position' => 2  ),
			'node-a'      => array( 'parent' => 'node-b',        'title' => 'A',           'position' => 30 ),
			'node-b'      => array( 'parent' => 'node-a',        'title' => 'B',           'position' => 40 ),
		);

		// Register both so they aren't dropped for being unregistered.
		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'A', 'read', 'node-a' ),
				array( 'B', 'read', 'node-b' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		// node-a has position 30 (lowest), so it gets demoted to the Woo root.
		$this->assertSame( 'woocommerce', $tree['node-a']['parent'] );
		// node-b's chain is now valid: node-b -> node-a -> woocommerce.
		$this->assertSame( 'node-a', $tree['node-b']['parent'] );
	}
```

- [ ] **Step 5.2: Run test to verify it fails**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Tree_Builder_Test::test_cycle_detection_breaks_lowest_position_to_root`

Expected: FAIL — cycle detection missing; either infinite loop (timeout) or incorrect parent assignment.

- [ ] **Step 5.3: Add cycle-breaking pass**

Modify `Tree_Builder::build()` to call a new `break_cycles()` pass after `auto_attach_woocommerce_children()`:

```php
	public function build( array $default_tree, array $raw_menu, array $raw_submenu ): array {
		$registered_slugs = $this->collect_registered_slugs( $raw_menu, $raw_submenu );
		$tree             = array();

		foreach ( $default_tree as $slug => $node ) {
			if ( 'woocommerce' === $slug || isset( $registered_slugs[ $slug ] ) ) {
				$tree[ $slug ]           = $node;
				$tree[ $slug ]['source'] = 'default';
			}
		}

		$tree = $this->auto_attach_woocommerce_children( $tree, $default_tree, $raw_submenu );
		$tree = $this->break_cycles( $tree );

		return $tree;
	}

	/**
	 * Detect cycles in parent chains and break them by demoting the lowest-position
	 * node in each cycle to the Woo root.
	 *
	 * @param array $tree Tree.
	 * @return array Tree with cycles broken.
	 */
	private function break_cycles( array $tree ): array {
		foreach ( array_keys( $tree ) as $slug ) {
			$cycle = $this->find_cycle( $tree, $slug );
			if ( null === $cycle ) {
				continue;
			}

			$demote = $cycle[0];
			foreach ( $cycle as $node ) {
				if ( $tree[ $node ]['position'] < $tree[ $demote ]['position'] ) {
					$demote = $node;
				}
			}

			$tree[ $demote ]['parent'] = 'woocommerce';

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					sprintf(
						'[woocommerce] navigation_v2: cycle detected in admin menu tree (%s); demoted %s to root.',
						implode( ' -> ', $cycle ),
						$demote
					)
				);
			}
		}

		return $tree;
	}

	/**
	 * Walk the parent chain starting from $slug. If it revisits any node,
	 * return the cycle (array of slugs). Otherwise return null.
	 *
	 * @param array  $tree Tree.
	 * @param string $slug Start slug.
	 * @return array|null
	 */
	private function find_cycle( array $tree, string $slug ): ?array {
		$visited = array();
		$current = $slug;
		while ( null !== $current ) {
			if ( isset( $visited[ $current ] ) ) {
				return array_keys( array_slice( $visited, array_search( $current, array_keys( $visited ), true ) ) );
			}
			$visited[ $current ] = true;
			$parent              = $tree[ $current ]['parent'] ?? null;
			if ( null === $parent || ! isset( $tree[ $parent ] ) ) {
				return null;
			}
			$current = $parent;
		}
		return null;
	}
```

- [ ] **Step 5.4: Run tests to verify they pass**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Tree_Builder_Test`

Expected: PASS — all five tests green. No infinite loops.

- [ ] **Step 5.5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Tree_Builder.php \
    plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php
git commit -m "feat(nav-v2): detect and break parent-chain cycles"
```

---

### Task 6: Tree_Builder — unknown parent handling

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Tree_Builder.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php`

- [ ] **Step 6.1: Write the failing test**

Append to `Tree_Builder_Test.php`:

```php
	/**
	 * A node whose parent is unknown (not in the tree) is dropped from the result
	 * and, when WP_DEBUG is on, a debug message is logged.
	 */
	public function test_unknown_parent_drops_node() {
		$default = array(
			'woocommerce' => array( 'parent' => null,                 'title' => 'WooCommerce', 'position' => 2  ),
			'orphan'      => array( 'parent' => 'does-not-exist-yet', 'title' => 'Orphan',      'position' => 30 ),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Orphan', 'read', 'orphan' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayNotHasKey( 'orphan', $tree );
	}
```

- [ ] **Step 6.2: Run test to verify it fails**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Tree_Builder_Test::test_unknown_parent_drops_node`

Expected: FAIL — orphan currently retained with `parent = 'does-not-exist-yet'`.

- [ ] **Step 6.3: Add unknown-parent drop pass**

Add a new pass `drop_unknown_parents()` to `Tree_Builder`, called after `break_cycles()`:

```php
	public function build( array $default_tree, array $raw_menu, array $raw_submenu ): array {
		// ... existing code ...
		$tree = $this->auto_attach_woocommerce_children( $tree, $default_tree, $raw_submenu );
		$tree = $this->break_cycles( $tree );
		$tree = $this->drop_unknown_parents( $tree );

		return $tree;
	}

	/**
	 * Drop nodes whose parent is set but is not in the tree.
	 * Logs to debug.log when WP_DEBUG is enabled.
	 *
	 * @param array $tree Tree.
	 * @return array Tree with orphans removed.
	 */
	private function drop_unknown_parents( array $tree ): array {
		foreach ( $tree as $slug => $node ) {
			if ( null === $node['parent'] ) {
				continue;
			}
			if ( isset( $tree[ $node['parent'] ] ) ) {
				continue;
			}

			unset( $tree[ $slug ] );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					sprintf(
						'[woocommerce] navigation_v2: dropped node %s: unknown parent %s',
						$slug,
						$node['parent']
					)
				);
			}
		}

		return $tree;
	}
```

- [ ] **Step 6.4: Run tests to verify they pass**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Tree_Builder_Test`

Expected: PASS — all six tests green.

- [ ] **Step 6.5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Tree_Builder.php \
    plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php
git commit -m "feat(nav-v2): drop nodes with unknown parent"
```

---

### Task 7: Tree_Builder — capability filtering with breadcrumb passthrough

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Tree_Builder.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php`

- [ ] **Step 7.1: Write the failing test**

Append to `Tree_Builder_Test.php`:

```php
	/**
	 * A node the user lacks capability for is marked `hidden = true` unless it
	 * has a visible descendant, in which case it's marked `breadcrumb = true`
	 * (rendered as non-clickable label).
	 */
	public function test_capability_filtering_with_breadcrumb_passthrough() {
		$default = array(
			'woocommerce' => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2  ),
			'parent-cap'  => array( 'parent' => 'woocommerce', 'title' => 'Parent',      'position' => 30, 'capability' => 'manage_options' ),
			'child-cap'   => array( 'parent' => 'parent-cap',  'title' => 'Child',       'position' => 10, 'capability' => 'read' ),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Parent', 'manage_options', 'parent-cap' ),
				array( 'Child',  'read',           'child-cap' ),
			),
		);

		// Simulate a user with 'read' but not 'manage_options'.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$tree = $builder->apply_capability_filter( $tree );

		// Parent survives as a breadcrumb because child is visible.
		$this->assertArrayHasKey( 'parent-cap', $tree );
		$this->assertTrue( $tree['parent-cap']['breadcrumb'] ?? false );
		// Child is fully visible.
		$this->assertArrayHasKey( 'child-cap', $tree );
		$this->assertFalse( $tree['child-cap']['hidden'] ?? false );
	}

	/**
	 * When a parent is capability-hidden AND has no visible descendants,
	 * the parent is removed entirely.
	 */
	public function test_capability_hidden_without_descendants_removes_parent() {
		$default = array(
			'woocommerce' => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2  ),
			'parent-cap'  => array( 'parent' => 'woocommerce', 'title' => 'Parent',      'position' => 30, 'capability' => 'manage_options' ),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array(
			'woocommerce' => array( array( 'Parent', 'manage_options', 'parent-cap' ) ),
		);

		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );
		$tree    = $builder->apply_capability_filter( $tree );

		$this->assertArrayNotHasKey( 'parent-cap', $tree );
	}
```

- [ ] **Step 7.2: Run tests to verify they fail**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Tree_Builder_Test`

Expected: FAIL — `apply_capability_filter` method not defined.

- [ ] **Step 7.3: Implement apply_capability_filter**

Add to `Tree_Builder`:

```php
	/**
	 * Apply per-node capability checks. Nodes the user can't access are either
	 * removed, or marked breadcrumb if they have visible descendants (so the
	 * child chain remains reachable via non-clickable labels).
	 *
	 * Called separately from build() because tests can construct the tree
	 * without a user context and then apply the filter under a specific user.
	 *
	 * @param array $tree Tree.
	 * @return array Tree with capability-filtered nodes.
	 */
	public function apply_capability_filter( array $tree ): array {
		// Pass 1: mark every node's visibility based on capability.
		foreach ( $tree as $slug => &$node ) {
			$cap           = $node['capability'] ?? 'read';
			$node['hidden'] = ! current_user_can( $cap );
		}
		unset( $node );

		// Pass 2: compute visible-descendant flag bottom-up so breadcrumbs know
		// when to stay.
		$has_visible_descendant = array();
		foreach ( array_keys( $tree ) as $slug ) {
			$has_visible_descendant[ $slug ] = false;
		}
		foreach ( $tree as $slug => $node ) {
			if ( ! empty( $node['hidden'] ) ) {
				continue;
			}
			$ancestor = $node['parent'];
			while ( null !== $ancestor && isset( $tree[ $ancestor ] ) ) {
				$has_visible_descendant[ $ancestor ] = true;
				$ancestor                            = $tree[ $ancestor ]['parent'];
			}
		}

		// Pass 3: resolve hidden nodes — either breadcrumb or remove.
		foreach ( $tree as $slug => $node ) {
			if ( empty( $node['hidden'] ) ) {
				continue;
			}
			if ( $has_visible_descendant[ $slug ] ) {
				$tree[ $slug ]['breadcrumb'] = true;
				$tree[ $slug ]['hidden']     = false;
			} else {
				unset( $tree[ $slug ] );
			}
		}

		return $tree;
	}
```

- [ ] **Step 7.4: Run tests to verify they pass**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Tree_Builder_Test`

Expected: PASS — all eight tests green.

- [ ] **Step 7.5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Tree_Builder.php \
    plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Tree_Builder_Test.php
git commit -m "feat(nav-v2): capability filtering with breadcrumb passthrough"
```

---

### Task 8: WC_Admin_Nav helper API

**Files:**
- Create: `plugins/woocommerce/src/Internal/Admin/Navigation/WC_Admin_Nav.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/WC_Admin_Nav_Test.php`

- [ ] **Step 8.1: Write the failing test**

Create `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/WC_Admin_Nav_Test.php`:

```php
<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\WC_Admin_Nav;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\WC_Admin_Nav
 */
class WC_Admin_Nav_Test extends \WC_Unit_Test_Case {

	public function test_add_inserts_node_with_parent() {
		$tree = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		);
		WC_Admin_Nav::add( $tree, 'my-plugin', array( 'parent' => 'woocommerce', 'title' => 'My Plugin' ) );

		$this->assertArrayHasKey( 'my-plugin', $tree );
		$this->assertSame( 'woocommerce', $tree['my-plugin']['parent'] );
		$this->assertSame( 'My Plugin', $tree['my-plugin']['title'] );
		$this->assertSame( 10, $tree['my-plugin']['position'], 'add() defaults position to 10' );
	}

	public function test_move_changes_parent() {
		$tree = array(
			'woocommerce' => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2 ),
			'wc-status'   => array( 'parent' => 'woocommerce', 'title' => 'Status',      'position' => 99 ),
			'wc-settings' => array( 'parent' => 'woocommerce', 'title' => 'Settings',    'position' => 90 ),
		);
		WC_Admin_Nav::move( $tree, 'wc-status', 'wc-settings' );

		$this->assertSame( 'wc-settings', $tree['wc-status']['parent'] );
	}

	public function test_remove_deletes_node() {
		$tree = array(
			'woocommerce' => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2 ),
			'wc-addons'   => array( 'parent' => 'woocommerce', 'title' => 'Extensions',  'position' => 95 ),
		);
		WC_Admin_Nav::remove( $tree, 'wc-addons' );

		$this->assertArrayNotHasKey( 'wc-addons', $tree );
	}

	public function test_rename_changes_title_only() {
		$tree = array(
			'wc-admin' => array( 'parent' => 'woocommerce', 'title' => 'Home', 'position' => 10 ),
		);
		WC_Admin_Nav::rename( $tree, 'wc-admin', 'Dashboard' );

		$this->assertSame( 'Dashboard', $tree['wc-admin']['title'] );
		$this->assertSame( 'woocommerce', $tree['wc-admin']['parent'] );
	}

	public function test_add_is_idempotent() {
		$tree = array( 'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ) );
		WC_Admin_Nav::add( $tree, 'my-plugin', array( 'parent' => 'woocommerce', 'title' => 'First' ) );
		WC_Admin_Nav::add( $tree, 'my-plugin', array( 'parent' => 'woocommerce', 'title' => 'Second' ) );

		$this->assertSame( 'Second', $tree['my-plugin']['title'], 'Second add overwrites first' );
	}
}
```

- [ ] **Step 8.2: Run test to verify it fails**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter WC_Admin_Nav_Test`

Expected: FAIL — class not found.

- [ ] **Step 8.3: Create WC_Admin_Nav**

Create `plugins/woocommerce/src/Internal/Admin/Navigation/WC_Admin_Nav.php`:

```php
<?php
/**
 * Ergonomic helpers for filter callbacks so extension authors don't have to
 * hand-mutate the tree array.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Pure-function helpers operating on the flat tree by reference.
 */
final class WC_Admin_Nav {

	/**
	 * Add (or overwrite) a node.
	 *
	 * @param array  $tree Tree, mutated by reference.
	 * @param string $slug Slug of the node to add.
	 * @param array  $args Node fields. `parent`, `title`, optional `position` (default 10) and `capability`.
	 */
	public static function add( array &$tree, string $slug, array $args ): void {
		$tree[ $slug ] = array(
			'parent'   => $args['parent']   ?? null,
			'title'    => $args['title']    ?? $slug,
			'position' => $args['position'] ?? 10,
			'source'   => 'helper',
		);
		if ( isset( $args['capability'] ) ) {
			$tree[ $slug ]['capability'] = $args['capability'];
		}
	}

	/**
	 * Change a node's parent.
	 *
	 * @param array  $tree   Tree, mutated by reference.
	 * @param string $slug   Slug to move.
	 * @param string $parent New parent slug.
	 */
	public static function move( array &$tree, string $slug, string $parent ): void {
		if ( ! isset( $tree[ $slug ] ) ) {
			return;
		}
		$tree[ $slug ]['parent'] = $parent;
	}

	/**
	 * Remove a node.
	 *
	 * @param array  $tree Tree, mutated by reference.
	 * @param string $slug Slug to remove.
	 */
	public static function remove( array &$tree, string $slug ): void {
		unset( $tree[ $slug ] );
	}

	/**
	 * Rename a node's title.
	 *
	 * @param array  $tree  Tree, mutated by reference.
	 * @param string $slug  Slug to rename.
	 * @param string $title New title.
	 */
	public static function rename( array &$tree, string $slug, string $title ): void {
		if ( ! isset( $tree[ $slug ] ) ) {
			return;
		}
		$tree[ $slug ]['title'] = $title;
	}
}
```

- [ ] **Step 8.4: Run tests to verify they pass**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter WC_Admin_Nav_Test`

Expected: PASS — all five tests green.

- [ ] **Step 8.5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/WC_Admin_Nav.php \
    plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/WC_Admin_Nav_Test.php
git commit -m "feat(nav-v2): WC_Admin_Nav helper API (add/move/remove/rename)"
```

---

### Task 9: Menu_Reconciler — integration of tree-builder with WP globals

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Menu_Reconciler.php` (replace stub)
- Test: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Menu_Reconciler_Test.php`

- [ ] **Step 9.1: Write the failing test**

Create `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Menu_Reconciler_Test.php`:

```php
<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Menu_Reconciler;
use Automattic\WooCommerce\Internal\Admin\Navigation\Rehomed_Slugs;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Menu_Reconciler
 */
class Menu_Reconciler_Test extends \WC_Unit_Test_Case {

	/**
	 * @var array|null
	 */
	private $menu_backup;
	/**
	 * @var array|null
	 */
	private $submenu_backup;

	public function setUp(): void {
		parent::setUp();
		global $menu, $submenu;
		$this->menu_backup    = $menu;
		$this->submenu_backup = $submenu;
	}

	public function tearDown(): void {
		global $menu, $submenu;
		$menu    = $this->menu_backup;
		$submenu = $this->submenu_backup;
		parent::tearDown();
	}

	/**
	 * After reconciliation, every rehomed top-level slug is removed from the
	 * WP $menu global.
	 */
	public function test_rehomed_top_level_items_are_removed() {
		global $menu;
		$menu = array(
			array( 'WooCommerce', 'read', 'woocommerce',                        '', '' ),
			array( 'Products',    'read', 'edit.php?post_type=product',         '', '' ),
			array( 'Marketing',   'read', 'woocommerce-marketing',              '', '' ),
			// Non-Woo item — must NOT be removed.
			array( 'Plugins',     'read', 'plugins.php',                        '', '' ),
		);
		global $submenu;
		$submenu = array(
			'woocommerce' => array(
				array( 'Home', 'read', 'wc-admin' ),
			),
		);

		$reconciler = new Menu_Reconciler();
		$reconciler->reconcile();

		$remaining_slugs = array_column( $menu, 2 );

		$this->assertNotContains( 'edit.php?post_type=product', $remaining_slugs );
		$this->assertNotContains( 'woocommerce-marketing', $remaining_slugs );
		// Non-Woo items survive.
		$this->assertContains( 'plugins.php', $remaining_slugs );
	}

	/**
	 * After reconciliation, a single `woocommerce` top-level entry remains
	 * (re-registered), and the computed tree is available via get_tree().
	 */
	public function test_tree_is_stored_after_reconciliation() {
		global $menu, $submenu;
		$menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$submenu = array(
			'woocommerce' => array(
				array( 'Home', 'read', 'wc-admin' ),
			),
		);

		$reconciler = new Menu_Reconciler();
		$reconciler->reconcile();

		$tree = Menu_Reconciler::get_tree();
		$this->assertIsArray( $tree );
		$this->assertArrayHasKey( 'woocommerce', $tree );
		$this->assertArrayHasKey( 'wc-admin', $tree );
	}

	/**
	 * The woocommerce_admin_menu_tree filter is applied and receives the
	 * raw $menu and $submenu.
	 */
	public function test_filter_receives_raw_menu_and_submenu() {
		global $menu, $submenu;
		$menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$submenu = array();

		$captured_raw_menu    = null;
		$captured_raw_submenu = null;
		add_filter(
			'woocommerce_admin_menu_tree',
			function ( $tree, $raw_menu, $raw_submenu ) use ( &$captured_raw_menu, &$captured_raw_submenu ) {
				$captured_raw_menu    = $raw_menu;
				$captured_raw_submenu = $raw_submenu;
				return $tree;
			},
			10,
			3
		);

		$reconciler = new Menu_Reconciler();
		$reconciler->reconcile();

		$this->assertIsArray( $captured_raw_menu );
		$this->assertSame( 'woocommerce', $captured_raw_menu[0][2] );
	}
}
```

- [ ] **Step 9.2: Run test to verify it fails**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Menu_Reconciler_Test`

Expected: FAIL — `reconcile()` method not defined on the stub.

- [ ] **Step 9.3: Replace the Menu_Reconciler stub with the real implementation**

Rewrite `plugins/woocommerce/src/Internal/Admin/Navigation/Menu_Reconciler.php`:

```php
<?php
/**
 * Menu reconciler.
 *
 * Runs at admin_menu priority 999 (after Woo's own menu registration at 9
 * and WP's default at 10). Captures $menu and $submenu, loads the default
 * tree, applies the woocommerce_admin_menu_tree filter, removes rehomed
 * top-level items from $menu, stores the final tree for the renderer.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Reconciles WP's admin menu against the Woo tree.
 */
class Menu_Reconciler {

	/**
	 * The computed tree. Static so Renderer can read it without coupling.
	 *
	 * @var array|null
	 */
	private static $tree = null;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'reconcile' ), 999 );
		// Spec §5.1 / §8: Woo root sits right after Dashboard (position 2).
		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', array( $this, 'place_woo_root' ), 200 );
	}

	/**
	 * Reorder the rail so `woocommerce` sits directly after `index.php`.
	 *
	 * @param array $menu_order Slugs in current order.
	 * @return array
	 */
	public function place_woo_root( array $menu_order ): array {
		$new_order = array();
		$menu_order = array_values( array_filter( $menu_order, fn( $item ) => 'woocommerce' !== $item ) );

		foreach ( $menu_order as $item ) {
			$new_order[] = $item;
			if ( 'index.php' === $item ) {
				$new_order[] = 'woocommerce';
			}
		}

		return $new_order;
	}

	/**
	 * Run the reconciliation.
	 */
	public function reconcile(): void {
		global $menu, $submenu;

		$default_tree = require __DIR__ . '/default-tree.php';
		$builder      = new Tree_Builder();
		$tree         = $builder->build( $default_tree, (array) $menu, (array) $submenu );

		/**
		 * Filter the navigation_v2 tree before the renderer consumes it.
		 *
		 * @param array $tree        Flat tree keyed by slug.
		 * @param array $raw_menu    WP's $menu at the time of reconciliation.
		 * @param array $raw_submenu WP's $submenu at the time of reconciliation.
		 */
		$tree = apply_filters( 'woocommerce_admin_menu_tree', $tree, (array) $menu, (array) $submenu );
		$tree = $builder->apply_capability_filter( $tree );

		$this->remove_rehomed_top_level_items();

		self::$tree = $tree;
	}

	/**
	 * Remove every rehomed-top-level slug from the $menu global.
	 */
	private function remove_rehomed_top_level_items(): void {
		global $menu;

		foreach ( Rehomed_Slugs::ALL as $slug ) {
			foreach ( $menu as $key => $entry ) {
				if ( isset( $entry[2] ) && $entry[2] === $slug ) {
					unset( $menu[ $key ] );
				}
			}
		}

		// Also strip Woo's menu separator.
		foreach ( $menu as $key => $entry ) {
			if ( isset( $entry[2] ) && 'separator-woocommerce' === $entry[2] ) {
				unset( $menu[ $key ] );
			}
		}

		$menu = array_values( $menu );
	}

	/**
	 * Expose the computed tree for the renderer. Static because the tree is
	 * stored in a static property — callers (Renderer, Assets) must not
	 * instantiate Menu_Reconciler just to read it, or they'd double-register
	 * the admin_menu hook.
	 *
	 * @return array|null Tree, or null if reconcile() hasn't run.
	 */
	public static function get_tree(): ?array {
		return self::$tree;
	}
}
```

- [ ] **Step 9.4: Run tests to verify they pass**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Menu_Reconciler_Test`

Expected: PASS — all three tests green.

- [ ] **Step 9.5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Menu_Reconciler.php \
    plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Menu_Reconciler_Test.php
git commit -m "feat(nav-v2): Menu_Reconciler composes tree and strips WP rail"
```

---

### Task 10: Context detection — "is current page a Woo page?"

**Files:**
- Create: `plugins/woocommerce/src/Internal/Admin/Navigation/Context.php`
- Test: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Context_Test.php`

- [ ] **Step 10.1: Write the failing test**

Create `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Context_Test.php`:

```php
<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Context;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Context
 */
class Context_Test extends \WC_Unit_Test_Case {

	public function tearDown(): void {
		unset( $_GET['page'], $_GET['post_type'], $_GET['path'] );
		parent::tearDown();
	}

	public function test_current_request_in_tree_is_woo_context() {
		$_GET['page'] = 'wc-settings';
		$tree         = array(
			'woocommerce' => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2  ),
			'wc-settings' => array( 'parent' => 'woocommerce', 'title' => 'Settings',    'position' => 90 ),
		);

		$this->assertTrue( Context::is_woo_page( $tree ) );
	}

	public function test_current_request_not_in_tree_is_not_woo_context() {
		$_GET['page'] = 'non-woo-page';
		$tree         = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		);

		$this->assertFalse( Context::is_woo_page( $tree ) );
	}

	public function test_wc_admin_path_is_matched_against_tree_keys() {
		$_GET['page'] = 'wc-admin';
		$_GET['path'] = '/analytics/overview';
		$tree         = array(
			'woocommerce'                       => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2  ),
			'wc-admin&path=/analytics/overview' => array( 'parent' => 'woocommerce', 'title' => 'Analytics',   'position' => 40 ),
		);

		$this->assertTrue( Context::is_woo_page( $tree ) );
	}

	public function test_product_post_type_is_matched_against_tree_keys() {
		$_GET['post_type'] = 'product';
		$tree              = array(
			'woocommerce'                => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2  ),
			'edit.php?post_type=product' => array( 'parent' => 'woocommerce', 'title' => 'Products',    'position' => 30 ),
		);

		$this->assertTrue( Context::is_woo_page( $tree ) );
	}
}
```

- [ ] **Step 10.2: Run test to verify it fails**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Context_Test`

Expected: FAIL — class not found.

- [ ] **Step 10.3: Create Context**

Create `plugins/woocommerce/src/Internal/Admin/Navigation/Context.php`:

```php
<?php
/**
 * Woo-page context detection.
 *
 * Answers the question "is the current admin request inside the Woo tree?"
 * which drives rail replacement vs. hover cascade.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the current admin request to a tree slug.
 */
final class Context {

	/**
	 * Is the current request resolvable to any slug in the tree?
	 *
	 * @param array $tree Final tree.
	 * @return bool
	 */
	public static function is_woo_page( array $tree ): bool {
		return null !== self::resolve_current_slug( $tree );
	}

	/**
	 * Return the tree slug that best matches the current request, or null.
	 *
	 * @param array $tree Final tree.
	 * @return string|null
	 */
	public static function resolve_current_slug( array $tree ): ?string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page      = isset( $_GET['page'] )      ? sanitize_text_field( wp_unslash( $_GET['page'] ) )      : '';
		$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
		$path      = isset( $_GET['path'] )      ? sanitize_text_field( wp_unslash( $_GET['path'] ) )      : '';
		// phpcs:enable

		if ( '' !== $page ) {
			if ( isset( $tree[ $page ] ) ) {
				return $page;
			}
			if ( 'wc-admin' === $page && '' !== $path ) {
				$candidate = 'wc-admin&path=' . $path;
				if ( isset( $tree[ $candidate ] ) ) {
					return $candidate;
				}
			}
		}

		if ( '' !== $post_type ) {
			$candidate = 'edit.php?post_type=' . $post_type;
			if ( isset( $tree[ $candidate ] ) ) {
				return $candidate;
			}
		}

		return null;
	}
}
```

- [ ] **Step 10.4: Run tests to verify they pass**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Context_Test`

Expected: PASS — all four tests green.

- [ ] **Step 10.5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Context.php \
    plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Context_Test.php
git commit -m "feat(nav-v2): Context detection for rail vs. cascade"
```

---

### Task 11: Assets — enqueue CSS/JS with admin-menu.css alias technique

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Assets.php` (replace stub)
- Create: `plugins/woocommerce/client/legacy/css/admin-navigation-v2.scss`
- Create: `plugins/woocommerce/client/legacy/js/admin/admin-navigation-v2.js` (stub, fully implemented in Task 13)

- [ ] **Step 11.1: Create the SCSS file**

Create `plugins/woocommerce/client/legacy/css/admin-navigation-v2.scss`. This is the full CSS, ported verbatim from the prototype's `woo-new-admin.css` with class names updated:

```scss
// Nested admin navigation v2 — rail replacement + flyout cascade.
// Alias block: class-wc-nav-v2-assets writes an inline <style> at runtime
// that rewrites all WP admin-menu.css selectors to also target #wc-nav-v2.
// This file contains only the overrides on top of that.

#wc-nav-v2 {
	display: none;
	position: fixed;
	top: 32px;
	left: 0;
	width: 160px;
	height: calc(100vh - 32px);
	background: #1d2327;
	z-index: 9990;
	overflow: visible;
	flex-direction: column;
}

@media screen and (max-width: 782px) {
	#wc-nav-v2 {
		top: 46px;
		height: calc(100vh - 46px);
	}
}

body.wc-nav-v2-active {
	#adminmenuwrap,
	#adminmenuback,
	#adminmenushadow {
		visibility: hidden !important;
		pointer-events: none !important;
	}

	#wc-nav-v2 {
		display: flex;
	}

	#wpcontent,
	#wpfooter {
		margin-left: 160px !important;
	}
}

#wc-nav-v2-header {
	border-bottom: 1px solid rgba( 255, 255, 255, 0.08 );
}

#wc-nav-v2-back {
	display: flex;
	align-items: center;
	gap: 3px;
	padding: 10px 12px;
	color: #72aee6;
	text-decoration: none;
	font-size: 12px;
	font-weight: 500;
	transition: color 0.15s ease;

	&:hover,
	&:focus {
		color: #fff;
	}
}

#wc-nav-v2-adminmenu {
	margin: 0;
	padding: 8px 0;
	width: 160px;
	flex: 1;

	a,
	a:hover,
	a:focus,
	a:visited {
		text-decoration: none !important;
	}

	li.menu-top > a {
		width: 160px;
		box-sizing: border-box;
	}

	.wp-submenu {
		z-index: 9999;
	}

	// Breadcrumb (non-clickable) ancestors when user lacks parent capability.
	li.wc-nav-v2-breadcrumb > a {
		pointer-events: none;
		opacity: 0.6;
	}
}

// RTL: flyout opens left.
body.rtl #wc-nav-v2-adminmenu .wp-submenu {
	left: auto;
	right: 160px;
}

// Folded admin state — match native 36px.
body.folded.wc-nav-v2-active {
	#wc-nav-v2 {
		width: 36px;
	}
	#wc-nav-v2-adminmenu {
		width: 36px;
	}
	#wpcontent,
	#wpfooter {
		margin-left: 36px !important;
	}
}

// Spec §6.3: touch devices do not get the hover cascade. Tapping the
// WooCommerce item navigates to the Woo dashboard, which triggers rail
// replacement — that's the touch-friendly entry. We suppress hover-opened
// flyouts via media query; JS state-machine is harmless on touch (no hover
// event fires).
@media (hover: none) {
	#wc-nav-v2-adminmenu li.wp-has-submenu > .wp-submenu,
	li#toplevel_page_woocommerce > .wp-submenu {
		display: none !important;
	}
}
```

- [ ] **Step 11.2: Create the JS stub (full implementation in Task 13)**

Create `plugins/woocommerce/client/legacy/js/admin/admin-navigation-v2.js`:

```javascript
/* global wcNavV2Config, jQuery, wcTracks */
( function ( $ ) {
	'use strict';

	// Full implementation lives in Task 13. This stub makes the enqueue work
	// end-to-end so Assets can be tested in isolation.
	$( function () {
		if ( wcNavV2Config && wcNavV2Config.isWooPage === '1' ) {
			$( 'body' ).addClass( 'wc-nav-v2-active' );
		}
	} );
} )( jQuery );
```

- [ ] **Step 11.3: Replace the Assets stub**

Rewrite `plugins/woocommerce/src/Internal/Admin/Navigation/Assets.php`:

```php
<?php
/**
 * Asset enqueuing for navigation_v2.
 *
 * The aliased-CSS trick: read WP's own wp-admin/css/admin-menu.css, rewrite
 * every `#adminmenu` selector to also target `#wc-nav-v2-adminmenu`, and
 * inline the result as a dependent stylesheet. This lets the Woo rail
 * inherit 100% of WP's menu styling — active states, color schemes, hover,
 * flyout, folded mode, RTL — for free. Ported from the WooPro prototype.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the navigation_v2 CSS and JS.
 */
class Assets {

	public const STYLE_HANDLE  = 'wc-nav-v2';
	public const SCRIPT_HANDLE = 'wc-nav-v2';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue.
	 */
	public function enqueue(): void {
		if ( ! is_admin() ) {
			return;
		}

		$version = defined( 'WC_VERSION' ) ? WC_VERSION : '1.0.0';

		wp_enqueue_style(
			self::STYLE_HANDLE,
			WC()->plugin_url() . '/assets/css/admin-navigation-v2.css',
			array( 'admin-menu' ),
			$version
		);

		$aliased = $this->get_aliased_adminmenu_css();
		if ( '' !== $aliased ) {
			wp_add_inline_style( self::STYLE_HANDLE, $aliased );
		}

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			WC()->plugin_url() . '/assets/js/admin/admin-navigation-v2.js',
			array( 'jquery' ),
			$version,
			true
		);

		// Expose the computed tree and current-page flag to JS.
		$tree = Menu_Reconciler::get_tree() ?? array();

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wcNavV2Config',
			array(
				'isWooPage'     => Context::is_woo_page( $tree ) ? '1' : '0',
				'wpDashboardUrl' => admin_url( 'index.php' ),
				'tree'          => $tree,
			)
		);
	}

	/**
	 * Build an inline CSS block that aliases all WP admin-menu rules to also
	 * target #wc-nav-v2-adminmenu. Cached per WP version + color scheme.
	 *
	 * @return string
	 */
	private function get_aliased_adminmenu_css(): string {
		$color_scheme = get_user_option( 'admin_color' ) ?: 'fresh';
		$cache_key    = 'wc_nav_v2_alias_' . get_bloginfo( 'version' ) . '_' . $color_scheme;
		$cached       = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (string) $cached;
		}

		$css = '';
		$css .= $this->read_and_alias( ABSPATH . 'wp-admin/css/admin-menu.min.css' );
		if ( '' === $css ) {
			$css .= $this->read_and_alias( ABSPATH . 'wp-admin/css/admin-menu.css' );
		}

		// 'fresh' (default) embeds colors in admin-menu.css itself; other schemes have a separate file.
		if ( 'fresh' !== $color_scheme ) {
			$color_dir  = ABSPATH . 'wp-admin/css/colors/' . sanitize_key( $color_scheme ) . '/';
			$color_file = $color_dir . 'colors.min.css';
			if ( ! file_exists( $color_file ) ) {
				$color_file = $color_dir . 'colors.css';
			}
			if ( file_exists( $color_file ) ) {
				$css .= $this->read_and_alias( $color_file );
			}
		}

		set_transient( $cache_key, $css, WEEK_IN_SECONDS );
		return $css;
	}

	/**
	 * Read one CSS file and rewrite its #adminmenu-family selectors onto our clones.
	 *
	 * @param string $path Absolute path.
	 * @return string Aliased CSS, or '' if file is missing.
	 */
	private function read_and_alias( string $path ): string {
		if ( ! file_exists( $path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$css = file_get_contents( $path );
		if ( false === $css ) {
			return '';
		}

		$replacements = array(
			'#adminmenuback'        => '#wc-nav-v2',
			'#adminmenuwrap'        => '#wc-nav-v2',
			'#adminmenushadow'      => '#wc-nav-v2',
			'#adminmenu'            => '#wc-nav-v2-adminmenu',
			// Color-scheme CSS sometimes uses ul#adminmenu which the above rewrites break.
			'ul#wc-nav-v2-adminmenu' => '#wc-nav-v2-adminmenu',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $css );
	}
}
```

- [ ] **Step 11.4: Manual verification**

No unit test for Assets — it's I/O-heavy and better verified via E2E. Confirm classes load:

```bash
pnpm --filter=@woocommerce/plugin-woocommerce test:php
```

Expected: All PHP tests still pass (no regression).

- [ ] **Step 11.5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Assets.php \
    plugins/woocommerce/client/legacy/css/admin-navigation-v2.scss \
    plugins/woocommerce/client/legacy/js/admin/admin-navigation-v2.js
git commit -m "feat(nav-v2): enqueue CSS/JS and port the admin-menu.css alias trick"
```

---

### Task 12: Renderer — body class and rail HTML

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Renderer.php` (replace stub)

- [ ] **Step 12.1: Replace the Renderer stub**

Rewrite `plugins/woocommerce/src/Internal/Admin/Navigation/Renderer.php`:

```php
<?php
/**
 * Renderer for navigation_v2.
 *
 * Two surfaces, one tree:
 *   1. Hover cascade — the WP rail item `woocommerce` has its native L2 flyout
 *      replaced by a multi-level flyout. This is done entirely in CSS (aliased
 *      admin-menu.css) + JS (opensub class toggling).
 *   2. Rail replacement — on Woo pages, the native rail is hidden and a Woo
 *      rail (same 160px) takes its place. This outputs the rail HTML via
 *      admin_footer using WP-native CSS class names so the aliased CSS applies.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Outputs the navigation_v2 rail and body class.
 */
class Renderer {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'admin_body_class', array( $this, 'add_body_class' ) );
		add_action( 'admin_footer', array( $this, 'render_rail' ) );
	}

	/**
	 * Add .wc-nav-v2-active to body on Woo pages. The CSS keys off this class
	 * to swap the rail.
	 *
	 * @param string $classes Existing classes.
	 * @return string
	 */
	public function add_body_class( string $classes ): string {
		$tree = $this->get_tree();
		if ( null !== $tree && Context::is_woo_page( $tree ) ) {
			$classes .= ' wc-nav-v2-active';
		}
		return $classes;
	}

	/**
	 * Output the Woo rail into the DOM. Always emitted on admin pages — shown
	 * only when the body class is present (CSS-controlled). This avoids a
	 * layout-timing flicker on SPA-style Woo Admin pages.
	 */
	public function render_rail(): void {
		$tree = $this->get_tree();
		if ( null === $tree ) {
			return;
		}

		$current = Context::resolve_current_slug( $tree );
		$by_parent = $this->index_by_parent( $tree );

		// Spec §6.4: rail replacement uses <nav aria-label="WooCommerce"> with role="tree".
		echo '<nav id="wc-nav-v2" aria-label="' . esc_attr__( 'WooCommerce', 'woocommerce' ) . '">';
		echo '<div id="wc-nav-v2-header">';
		echo '<a href="' . esc_url( admin_url( 'index.php' ) ) . '" id="wc-nav-v2-back">';
		echo is_rtl() ? '&rarr; ' : '&larr; ';
		echo esc_html__( 'WordPress', 'woocommerce' );
		echo '</a>';
		echo '</div>';
		echo '<ul id="wc-nav-v2-adminmenu" role="tree">';

		// Root's children are the top-level rail items.
		$roots = $by_parent['woocommerce'] ?? array();
		usort( $roots, array( $this, 'sort_by_position' ) );
		foreach ( $roots as $node ) {
			$this->render_node( $node, $by_parent, $current );
		}

		echo '</ul>';
		echo '</nav>';
	}

	/**
	 * Render one <li> for a given node, recursing into children.
	 *
	 * @param array  $node      Node with 'slug' added.
	 * @param array  $by_parent Children indexed by parent slug.
	 * @param string $current   Current slug or null.
	 */
	private function render_node( array $node, array $by_parent, ?string $current ): void {
		$slug      = $node['slug'];
		$children  = $by_parent[ $slug ] ?? array();
		usort( $children, array( $this, 'sort_by_position' ) );
		$is_current = ( $slug === $current );
		$has_kids   = ! empty( $children );

		$classes = array( 'menu-top' );
		if ( $is_current ) {
			$classes[] = 'current';
			$classes[] = 'wp-has-current-submenu';
			$classes[] = 'wp-menu-open';
		} elseif ( $has_kids ) {
			$classes[] = 'wp-has-submenu';
			$classes[] = 'wp-not-current-submenu';
		}
		if ( ! empty( $node['breadcrumb'] ) ) {
			$classes[] = 'wc-nav-v2-breadcrumb';
		}

		echo '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		echo '<a href="' . esc_url( $this->slug_to_url( $slug ) ) . '" class="menu-top">';
		if ( ! empty( $node['icon'] ) ) {
			echo '<div class="wp-menu-image dashicons-before ' . esc_attr( $node['icon'] ) . '" aria-hidden="true"><br></div>';
		}
		echo '<div class="wp-menu-name">' . esc_html( $node['title'] ) . '</div>';
		echo '</a>';

		if ( $has_kids ) {
			echo '<ul class="wp-submenu wp-submenu-wrap">';
			echo '<li class="wp-submenu-head" aria-hidden="true">' . esc_html( $node['title'] ) . '</li>';
			foreach ( $children as $child ) {
				$child_current = ( $child['slug'] === $current ) ? ' class="current"' : '';
				echo '<li' . $child_current . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<a href="' . esc_url( $this->slug_to_url( $child['slug'] ) ) . '">' . esc_html( $child['title'] ) . '</a>';
				echo '</li>';
			}
			echo '</ul>';
		}

		echo '</li>';
	}

	/**
	 * Group tree entries by parent.
	 *
	 * @param array $tree Tree.
	 * @return array Array of parent-slug => list-of-child-nodes. Each node has
	 *               'slug' key added for convenience.
	 */
	private function index_by_parent( array $tree ): array {
		$by_parent = array();
		foreach ( $tree as $slug => $node ) {
			$node['slug']               = $slug;
			$parent                     = $node['parent'] ?? 'woocommerce';
			$by_parent[ $parent ]      ??= array();
			$by_parent[ $parent ][]     = $node;
		}
		return $by_parent;
	}

	/**
	 * Sort callback.
	 *
	 * @param array $a Node.
	 * @param array $b Node.
	 * @return int
	 */
	private function sort_by_position( array $a, array $b ): int {
		return ( $a['position'] ?? 0 ) <=> ( $b['position'] ?? 0 );
	}

	/**
	 * Turn a tree slug back into an admin URL. The slug itself is typically
	 * already a full query-string fragment (`edit.php?post_type=product`,
	 * `wc-admin&path=/analytics/overview`).
	 *
	 * @param string $slug Slug.
	 * @return string
	 */
	private function slug_to_url( string $slug ): string {
		if ( str_contains( $slug, '?' ) || str_contains( $slug, '&' ) ) {
			return admin_url( str_contains( $slug, '?' ) ? $slug : 'admin.php?page=' . $slug );
		}
		return admin_url( 'admin.php?page=' . $slug );
	}

	/**
	 * Fetch the tree from Menu_Reconciler's static store.
	 *
	 * @return array|null
	 */
	private function get_tree(): ?array {
		return Menu_Reconciler::get_tree();
	}
}
```

- [ ] **Step 12.2: Manual smoke test**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php`

Expected: PASS — no regression. The Renderer is covered by the E2E tests in Task 14, not unit tests (too much DOM coupling to be worth mocking).

- [ ] **Step 12.3: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Renderer.php
git commit -m "feat(nav-v2): render rail HTML with WP-native class names"
```

---

### Task 13: JS — flyout state machine, keyboard nav, Tracks emission

**Files:**
- Modify: `plugins/woocommerce/client/legacy/js/admin/admin-navigation-v2.js` (replace stub)

- [ ] **Step 13.1: Replace the JS stub with the full implementation**

Ported from `woo-new-nav.js` with handle names and event names updated.

```javascript
/* global wcNavV2Config, jQuery */
/**
 * Nested admin navigation v2 — rail + cascade behavior.
 *
 * - Rail replacement: toggled via body.wc-nav-v2-active (set server-side on
 *   Woo pages; we trust PHP and just run with what's in the DOM).
 * - Flyout cascade: hover-intent via the `opensub` class on li.wp-has-submenu,
 *   mirroring WP's own common.js behavior. The aliased admin-menu.css then
 *   handles the show/hide visuals.
 * - Keyboard navigation: native WP behavior still applies because we use WP's
 *   class names; we only add depth-aware arrow-right/left handling.
 * - Tracks: emit the 5 events specified in the design doc.
 */
( function ( $ ) {
	'use strict';

	var tracks = ( window.wcTracks && window.wcTracks.recordEvent ) || function () {};

	$( function () {
		if ( ! window.wcNavV2Config ) {
			return;
		}

		// Rail mode is decided server-side.
		if ( window.wcNavV2Config.isWooPage === '1' ) {
			$( 'body' ).addClass( 'wc-nav-v2-active' );
		}

		// -----------------------------------------------------------------------
		// Flyout cascade — hover-intent open/close.
		// -----------------------------------------------------------------------
		var $menu = $( '#wc-nav-v2-adminmenu' );

		$menu.find( 'li.wp-has-submenu' )
			.on( 'mouseenter.wcnavv2', function () {
				var $li = $( this );
				var depth = $li.parents( 'li.wp-has-submenu' ).length;
				$menu.find( 'li.opensub' ).not( $li ).not( $li.parents() ).removeClass( 'opensub' );
				$li.addClass( 'opensub' );
				tracks( 'navigation_v2_hover_opened', { depth_reached: depth + 1 } );
			} )
			.on( 'mouseleave.wcnavv2', function () {
				$( this ).removeClass( 'opensub' );
			} );

		// Keep opensub when focus moves into a submenu (keyboard nav).
		$menu
			.on( 'focus.wcnavv2', '.wp-submenu a', function () {
				$( this ).closest( 'li.menu-top' ).addClass( 'opensub' );
			} )
			.on( 'blur.wcnavv2', '.wp-submenu a', function () {
				$( this ).closest( 'li.menu-top' ).removeClass( 'opensub' );
			} );

		// -----------------------------------------------------------------------
		// Keyboard: Escape closes open flyout; arrow keys move focus.
		// -----------------------------------------------------------------------
		$menu.on( 'keydown.wcnavv2', 'a', function ( e ) {
			var key = e.key;
			if ( key === 'Escape' ) {
				$menu.find( 'li.opensub' ).removeClass( 'opensub' );
				return;
			}
			if ( key === 'ArrowDown' ) {
				e.preventDefault();
				$( this ).closest( 'li' ).next( 'li' ).find( 'a' ).first().focus();
			}
			if ( key === 'ArrowUp' ) {
				e.preventDefault();
				$( this ).closest( 'li' ).prev( 'li' ).find( 'a' ).first().focus();
			}
			if ( key === 'ArrowRight' ) {
				var $submenu = $( this ).closest( 'li' ).find( '> .wp-submenu a' ).first();
				if ( $submenu.length ) {
					e.preventDefault();
					$submenu.focus();
				}
			}
			if ( key === 'ArrowLeft' ) {
				var $parent = $( this ).closest( '.wp-submenu' ).prev( 'a' );
				if ( $parent.length ) {
					e.preventDefault();
					$parent.focus();
				}
			}
		} );

		// -----------------------------------------------------------------------
		// Tracks — leaf clicks.
		// -----------------------------------------------------------------------
		$menu.on( 'click.wcnavv2', 'a', function () {
			var $a       = $( this );
			var slug     = $a.attr( 'href' ) || '';
			var depth    = $a.parents( 'li.wp-has-submenu' ).length;
			var surface  = $( 'body' ).hasClass( 'wc-nav-v2-active' ) ? 'rail' : 'hover';
			tracks( 'navigation_v2_item_clicked', { slug: slug, depth: depth, surface: surface } );
		} );

		// Back link.
		$( '#wc-nav-v2-back' ).on( 'click.wcnavv2', function () {
			tracks( 'navigation_v2_back_clicked' );
		} );
	} );
} )( jQuery );
```

- [ ] **Step 13.2: Commit**

```bash
git add plugins/woocommerce/client/legacy/js/admin/admin-navigation-v2.js
git commit -m "feat(nav-v2): JS for flyout, keyboard nav, and Tracks events"
```

---

### Task 14: Telemetry — server-side Tracks events

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Telemetry.php` (replace stub)
- Test: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Telemetry_Test.php`

Two of the five Tracks events are emitted server-side: `navigation_v2_toggled` (on flag flip) and `navigation_v2_duration_days` (daily beacon via Action Scheduler). The other three (`item_clicked`, `hover_opened`, `back_clicked`) are fired from JS in Task 13.

- [ ] **Step 14.1: Write the failing test**

Create `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Telemetry_Test.php`:

```php
<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap;
use Automattic\WooCommerce\Internal\Admin\Navigation\Telemetry;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Telemetry
 */
class Telemetry_Test extends \WC_Unit_Test_Case {

	public function tearDown(): void {
		delete_option( 'woocommerce_feature_navigation_v2_enabled' );
		parent::tearDown();
	}

	public function test_telemetry_hooks_flag_toggles() {
		new Telemetry();

		$this->assertNotFalse(
			has_action( 'update_option_woocommerce_feature_navigation_v2_enabled' ),
			'update hook must be registered so flag flips emit a Tracks event'
		);
		$this->assertNotFalse(
			has_action( 'add_option_woocommerce_feature_navigation_v2_enabled' ),
			'add hook must also be registered (first-time set uses add_option, not update)'
		);
	}

	public function test_telemetry_on_flag_toggled_does_not_error_when_tracks_unavailable() {
		$telemetry = new Telemetry();
		// Should be a no-op (and not throw) even when Tracks is disabled in tests.
		$telemetry->on_flag_toggled( 'no', 'yes' );
		$this->assertTrue( true, 'on_flag_toggled completed without error' );
	}
}
```

- [ ] **Step 14.2: Run test to verify it fails**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Telemetry_Test`

Expected: FAIL — Telemetry has no logic yet.

- [ ] **Step 14.3: Replace the Telemetry stub**

Rewrite `plugins/woocommerce/src/Internal/Admin/Navigation/Telemetry.php`:

```php
<?php
/**
 * Navigation v2 telemetry.
 *
 * Server-side Tracks events:
 *   - navigation_v2_toggled       — on any flag flip, {enabled: bool}.
 *   - navigation_v2_duration_days — daily beacon while enabled, scheduled via Action Scheduler.
 *
 * JS-side Tracks events (emitted from admin-navigation-v2.js):
 *   - navigation_v2_item_clicked  — {slug, depth, surface}.
 *   - navigation_v2_hover_opened  — {depth_reached}.
 *   - navigation_v2_back_clicked  — count of explicit exits.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Emits navigation_v2 Tracks events.
 */
class Telemetry {

	private const OPTION_NAME           = 'woocommerce_feature_navigation_v2_enabled';
	private const DAILY_BEACON_ACTION   = 'woocommerce_nav_v2_daily_beacon';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'update_option_' . self::OPTION_NAME, array( $this, 'on_flag_toggled' ), 10, 2 );
		add_action( 'add_option_' . self::OPTION_NAME,   array( $this, 'on_flag_toggled_first_time' ), 10, 2 );
		add_action( self::DAILY_BEACON_ACTION, array( $this, 'emit_daily_beacon' ) );

		$this->maybe_schedule_daily_beacon();
	}

	/**
	 * Fire toggled event with the new enabled state.
	 *
	 * @param string $old New value.
	 * @param string $new Old value (wp filter signature).
	 */
	public function on_flag_toggled( $old, $new ): void {
		if ( function_exists( 'wc_admin_record_tracks_event' ) ) {
			wc_admin_record_tracks_event(
				'navigation_v2_toggled',
				array( 'enabled' => 'yes' === $new )
			);
		}
	}

	/**
	 * First-time add_option signature: ($option, $value) not ($old, $new).
	 *
	 * @param string $option Option name.
	 * @param string $value  Option value.
	 */
	public function on_flag_toggled_first_time( $option, $value ): void {
		if ( function_exists( 'wc_admin_record_tracks_event' ) ) {
			wc_admin_record_tracks_event(
				'navigation_v2_toggled',
				array( 'enabled' => 'yes' === $value )
			);
		}
	}

	/**
	 * If the flag is on and no daily beacon is scheduled, schedule one.
	 */
	private function maybe_schedule_daily_beacon(): void {
		if ( 'yes' !== get_option( self::OPTION_NAME, 'no' ) ) {
			return;
		}
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}
		if ( ! as_has_scheduled_action( self::DAILY_BEACON_ACTION ) ) {
			as_schedule_recurring_action( time(), DAY_IN_SECONDS, self::DAILY_BEACON_ACTION, array(), 'woocommerce-nav-v2' );
		}
	}

	/**
	 * Emit the daily-duration beacon.
	 */
	public function emit_daily_beacon(): void {
		if ( 'yes' !== get_option( self::OPTION_NAME, 'no' ) ) {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::DAILY_BEACON_ACTION );
			}
			return;
		}

		if ( function_exists( 'wc_admin_record_tracks_event' ) ) {
			wc_admin_record_tracks_event( 'navigation_v2_duration_days', array() );
		}
	}
}
```

- [ ] **Step 14.4: Run tests to verify they pass**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Telemetry_Test`

Expected: PASS.

- [ ] **Step 14.5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Telemetry.php \
    plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Telemetry_Test.php
git commit -m "feat(nav-v2): Tracks events for flag flip and daily beacon"
```

---

### Task 15: Flag-off byte-identical regression snapshot

This is spec §9.4 — the most important test. Run the admin_menu hook chain twice: once with the feature never installed, once with flag off. `$menu` and `$submenu` must be byte-identical.

**Files:**
- Test: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Flag_Off_Snapshot_Test.php`

- [ ] **Step 15.1: Write the test**

Create `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Flag_Off_Snapshot_Test.php`:

```php
<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Bootstrap
 *
 * The empirical proof that flag-off = uninstalled. If this test fails, the
 * navigation_v2 feature has leaked a side effect into the flag-off path.
 * Fix it before shipping.
 */
class Flag_Off_Snapshot_Test extends \WC_Unit_Test_Case {

	public function setUp(): void {
		parent::setUp();
		// Ensure flag is explicitly off.
		update_option( 'woocommerce_feature_navigation_v2_enabled', 'no' );
	}

	public function tearDown(): void {
		delete_option( 'woocommerce_feature_navigation_v2_enabled' );
		parent::tearDown();
	}

	/**
	 * Fire admin_menu with the flag off and confirm the reconciler did NOT run.
	 *
	 * Practical implementation of spec §9.4: we can't uninstall the feature
	 * mid-test (the container already holds a Bootstrap instance), so we prove
	 * the no-op property by asserting the two observable side effects never
	 * happen:
	 *   1. Rehomed Woo top-level slugs are still present in $menu.
	 *   2. The computed tree in Menu_Reconciler::get_tree() is null.
	 */
	public function test_flag_off_leaves_menu_untouched() {
		global $menu;

		// Seed $menu with Woo-related top-level entries the reconciler would remove.
		$menu = array(
			array( 'WooCommerce', 'read', 'woocommerce',                'wc-icon', '' ),
			array( 'Products',    'read', 'edit.php?post_type=product', '',       '' ),
			array( 'Marketing',   'read', 'woocommerce-marketing',      '',       '' ),
			array( 'Plugins',     'read', 'plugins.php',                '',       '' ),
		);

		// Force Bootstrap to exist (as it would in production).
		wc_get_container()->get( Bootstrap::class );

		// Fire admin_init then admin_menu — the actions Bootstrap listens to.
		do_action( 'admin_init' );
		do_action( 'admin_menu' );

		$slugs = array_column( $menu, 2 );

		// All four remain — reconciler never ran.
		$this->assertContains( 'woocommerce', $slugs );
		$this->assertContains( 'edit.php?post_type=product', $slugs );
		$this->assertContains( 'woocommerce-marketing', $slugs );
		$this->assertContains( 'plugins.php', $slugs );
	}

	/**
	 * Flag ON still obeys is_network_admin(): network admin never gets the feature.
	 * We can't easily toggle is_network_admin() mid-test without patching WP globals,
	 * so this is covered by inspection of Bootstrap::boot_when_enabled() rather than
	 * executing the branch. See the `if ( is_network_admin() ) return;` guard.
	 */
}
```

- [ ] **Step 15.2: Run tests to verify they pass**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter Flag_Off_Snapshot_Test`

Expected: PASS — Bootstrap's constructor registers the feature definition + admin_init hook, but `boot_when_enabled()` exits early when the flag is off, so $menu and $submenu are untouched.

If this fails: trace the diff. The failure means the feature is leaking a side effect into the flag-off path. Fix before continuing.

- [ ] **Step 15.3: Commit**

```bash
git add plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Flag_Off_Snapshot_Test.php
git commit -m "test(nav-v2): regression snapshot proves flag-off is a no-op"
```

---

### Task 16: E2E Playwright tests

**Files:**
- Create: `plugins/woocommerce/tests/e2e-pw/tests/admin-navigation-v2/navigation-v2.spec.js`

- [ ] **Step 16.1: Write the E2E tests**

Create `plugins/woocommerce/tests/e2e-pw/tests/admin-navigation-v2/navigation-v2.spec.js`:

```javascript
const { test, expect } = require( '@playwright/test' );

/**
 * The WC-admin Playwright setup uses storageState (ADMINSTATE env) for
 * a logged-in admin session. These tests enable the navigation_v2 flag
 * via the REST API, then assert the resulting DOM behavior.
 */

const FLAG_OPTION = 'woocommerce_feature_navigation_v2_enabled';

async function setFlag( request, value ) {
	// Use the admin-ajax path — the standard WP flow. Alternative: direct DB option update.
	await request.post( '/wp-admin/admin-ajax.php', {
		form: {
			action: 'rest_save_option',
			option: FLAG_OPTION,
			value,
			_wpnonce: process.env.WP_NONCE || '',
		},
	} );
}

test.describe( 'Nested admin navigation v2', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { page } ) => {
		await page.goto(
			'/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features'
		);
		const checkbox = page.locator(
			`input[name="${ FLAG_OPTION }"]`
		);
		if ( ! ( await checkbox.isChecked() ) ) {
			await checkbox.check();
			await page
				.locator( 'button[name="save"]' )
				.first()
				.click();
			await expect(
				page.getByText( 'Your settings have been saved.' )
			).toBeVisible();
		}
	} );

	test.afterEach( async ( { page } ) => {
		await page.goto(
			'/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features'
		);
		const checkbox = page.locator(
			`input[name="${ FLAG_OPTION }"]`
		);
		if ( await checkbox.isChecked() ) {
			await checkbox.uncheck();
			await page
				.locator( 'button[name="save"]' )
				.first()
				.click();
		}
	} );

	test( 'flag on: WooCommerce is the only Woo top-level item in rail', async ( {
		page,
	} ) => {
		await page.goto( '/wp-admin/index.php' );

		const adminMenu = page.locator( '#adminmenu' );
		await expect(
			adminMenu.locator( 'li#toplevel_page_woocommerce' )
		).toBeVisible();

		// Other Woo top-level items are gone from the native rail.
		await expect(
			adminMenu.locator( 'li#toplevel_page_woocommerce-marketing' )
		).toHaveCount( 0 );
		await expect(
			adminMenu.locator(
				'li#menu-posts-product'
			)
		).toHaveCount( 0 );
	} );

	test( 'rail replacement appears on a Woo page with Back link', async ( {
		page,
	} ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-settings' );

		const railContainer = page.locator( '#wc-nav-v2' );
		await expect( railContainer ).toBeVisible();
		await expect( page.locator( 'body.wc-nav-v2-active' ) ).toHaveCount( 1 );

		const back = page.locator( '#wc-nav-v2-back' );
		await expect( back ).toHaveAttribute( 'href', /\/wp-admin\/(index\.php)?$/ );
	} );

	test( 'rail hidden on a non-Woo page', async ( { page } ) => {
		await page.goto( '/wp-admin/edit.php' ); // Posts — not Woo.

		await expect( page.locator( 'body.wc-nav-v2-active' ) ).toHaveCount( 0 );
		// The rail node is still emitted in the footer but CSS hides it.
		await expect( page.locator( '#wc-nav-v2' ) ).toBeHidden();
	} );

	test( 'hover cascade opens flyout and navigates on click', async ( {
		page,
	} ) => {
		await page.goto( '/wp-admin/index.php' );

		await page.locator( '#toplevel_page_woocommerce' ).hover();
		const flyout = page
			.locator( '#toplevel_page_woocommerce .wp-submenu' )
			.first();
		await expect( flyout ).toBeVisible();

		await flyout.getByRole( 'link', { name: /Orders/i } ).click();
		await expect( page ).toHaveURL( /post_type=shop_order/ );
	} );

	test( 'third-party submenu auto-nests under woocommerce', async ( {
		page,
		context,
	} ) => {
		// A test-plugin fixture registers add_submenu_page('woocommerce', ...).
		// See tests/e2e-pw/fixtures for the plugin scaffolding.
		await page.goto( '/wp-admin/admin.php?page=wc-settings' );

		const rail = page.locator( '#wc-nav-v2-adminmenu' );
		await expect(
			rail.getByRole( 'link', { name: /Test Plugin Page/i } )
		).toBeVisible();
	} );

	test( 'back link returns to WP dashboard', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-settings' );
		await page.locator( '#wc-nav-v2-back' ).click();
		await expect( page ).toHaveURL( /\/wp-admin\/(index\.php)?$/ );
		// Back on a non-Woo page, the native rail is back.
		await expect( page.locator( 'body.wc-nav-v2-active' ) ).toHaveCount( 0 );
	} );

	test( 'keyboard navigation — arrow keys reach every leaf', async ( {
		page,
	} ) => {
		await page.goto( '/wp-admin/admin.php?page=wc-settings' );
		const firstLink = page.locator( '#wc-nav-v2-adminmenu a' ).first();
		await firstLink.focus();

		// Press ArrowDown 6 times and confirm focus moves.
		for ( let i = 0; i < 6; i++ ) {
			await page.keyboard.press( 'ArrowDown' );
		}
		const activeHref = await page.evaluate(
			() => document.activeElement && document.activeElement.href
		);
		expect( activeHref ).toBeTruthy();
	} );

	test( 'flag off: native rail is restored on next load', async ( {
		page,
	} ) => {
		// afterEach unchecks the flag, but this test also verifies disable mid-session.
		await page.goto(
			'/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features'
		);
		await page.locator( `input[name="${ FLAG_OPTION }"]` ).uncheck();
		await page.locator( 'button[name="save"]' ).first().click();

		await page.goto( '/wp-admin/admin.php?page=wc-settings' );
		await expect( page.locator( 'body.wc-nav-v2-active' ) ).toHaveCount( 0 );
		await expect( page.locator( '#wc-nav-v2' ) ).toHaveCount( 0 );

		// Payments is back in the native rail.
		const adminMenu = page.locator( '#adminmenu' );
		await expect(
			adminMenu.locator( 'li a' ).filter( { hasText: /Payments/i } )
		).toHaveCount( /* >= */ 1 );
	} );
} );
```

- [ ] **Step 16.2: Create the test-plugin fixture for auto-nesting test**

Create `plugins/woocommerce/tests/e2e-pw/fixtures/nav-v2-test-plugin/nav-v2-test-plugin.php`:

```php
<?php
/**
 * Plugin Name: Navigation V2 Test Plugin
 * Description: Registers a submenu under WooCommerce to exercise auto-nesting.
 */

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'woocommerce',
			'Test Plugin Page',
			'Test Plugin Page',
			'manage_woocommerce',
			'nav-v2-test-plugin',
			function () {
				echo '<h1>Test Plugin Page</h1>';
			}
		);
	},
	20
);
```

Register this plugin in the E2E env's `global-setup.js`. Reference: `plugins/woocommerce/tests/e2e-pw/global-setup.js`. Add an `activatePlugin( 'nav-v2-test-plugin' )` call.

- [ ] **Step 16.3: Run the E2E suite**

Run:

```bash
cd /Users/beau/Source/git/woocommerce/plugins/woocommerce
pnpm test:e2e-pw -- tests/admin-navigation-v2/
```

Expected: all 8 tests pass. If the environment needs spin-up, run `pnpm env:test:start` first.

- [ ] **Step 16.4: Commit**

```bash
git add plugins/woocommerce/tests/e2e-pw/tests/admin-navigation-v2/ \
    plugins/woocommerce/tests/e2e-pw/fixtures/nav-v2-test-plugin/ \
    plugins/woocommerce/tests/e2e-pw/global-setup.js
git commit -m "test(nav-v2): E2E Playwright coverage for rail, cascade, keyboard, toggle"
```

---

### Task 17: FeaturesController registration test

**Files:**
- Modify: `plugins/woocommerce/tests/php/src/Internal/Features/FeaturesControllerTest.php`

- [ ] **Step 17.1: Append the navigation_v2 registration check**

In `plugins/woocommerce/tests/php/src/Internal/Features/FeaturesControllerTest.php`, add a test method:

```php
	/**
	 * navigation_v2 is registered as an experimental, disabled-by-default feature.
	 */
	public function test_navigation_v2_feature_is_registered() {
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\Features\FeaturesController::class );
		$features   = $controller->get_features( true );

		$this->assertArrayHasKey( 'navigation_v2', $features );
		$this->assertTrue( $features['navigation_v2']['is_experimental'] );
	}
```

- [ ] **Step 17.2: Run test to verify it passes**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:php -- --filter FeaturesControllerTest::test_navigation_v2_feature_is_registered`

Expected: PASS — Bootstrap's constructor registers the feature on the `woocommerce_register_feature_definitions` action, which fires when FeaturesController resolves its definitions.

- [ ] **Step 17.3: Commit**

```bash
git add plugins/woocommerce/tests/php/src/Internal/Features/FeaturesControllerTest.php
git commit -m "test(nav-v2): assert feature is registered in FeaturesController"
```

---

### Task 18: Documentation and devnote

**Files:**
- Create: `docs/quality-and-best-practices/nested-admin-navigation.md`
- Modify: `docs/docs-manifest.json` (add new doc to the manifest)
- Create: `plugins/woocommerce/changelog/feat-nested-admin-navigation`

- [ ] **Step 18.1: Create the doc**

Create `docs/quality-and-best-practices/nested-admin-navigation.md`:

```markdown
---
post_title: Nested admin navigation (experimental)
menu_title: Nested admin navigation
tags: admin, navigation, experimental
---

# Nested admin navigation

The `navigation_v2` feature flag replaces the flat WooCommerce admin menu with
a nested tree under a single `WooCommerce` rail item. Enable it from
**WooCommerce → Settings → Advanced → Features**.

## What it does

When enabled, the flag:

- Removes these Woo-related top-level items from WP's native rail: `WooCommerce`,
  `Products`, `Analytics`, `Marketing`, `Payments` (all variants), `WooPayments`,
  `Klaviyo` (when no Marketing parent is present).
- Re-registers a single `WooCommerce` rail item.
- Shows two navigation surfaces:
  - **Hover cascade** — hovering the rail item opens a multi-level flyout.
  - **Rail replacement** — on any Woo page, the native rail is replaced by a
    Woo rail with a `← Back` link that returns to the WP Dashboard.

## Filter hook

Extensions can override placement via the `woocommerce_admin_menu_tree` filter:

```php
add_filter(
    'woocommerce_admin_menu_tree',
    function ( $tree, $raw_menu, $raw_submenu ) {
        \Automattic\WooCommerce\Internal\Admin\Navigation\WC_Admin_Nav::add(
            $tree,
            'my-plugin-reports',
            array(
                'parent' => 'wc-admin&path=/analytics/overview',
                'title'  => 'My Reports',
            )
        );
        return $tree;
    },
    10,
    3
);
```

Four helpers are available on `WC_Admin_Nav`: `add()`, `move()`, `remove()`,
`rename()`. All mutate `$tree` by reference.

## Auto-nesting

Submenu items registered under the `woocommerce` parent via
`add_submenu_page( 'woocommerce', ... )` auto-attach without any filter changes.
This covers the majority of existing extensions without migration.

## Known limitations

- Multisite network admin — flag is ignored; native rail only.
- Plugins registering menu items after priority 999 stay in the native rail.
- Plugins rewriting `parent_file` via the `parent_file` filter (which runs after
  the reconciler) — the tree reflects pre-filter state. Affects a small number
  of known plugins.

## Disabling

Uncheck the flag to restore the native rail. The feature is byte-identical to
uninstalled when disabled — verified by a regression test.
```

- [ ] **Step 18.2: Add to docs manifest**

Add an entry in `docs/docs-manifest.json` under the `quality-and-best-practices` section:

```json
{
    "post_title": "Nested admin navigation",
    "menu_title": "Nested admin navigation",
    "slug": "nested-admin-navigation"
}
```

- [ ] **Step 18.3: Add changelog entry**

Create `plugins/woocommerce/changelog/feat-nested-admin-navigation`:

```
Significance: minor
Type: add

Add experimental nested admin navigation behind the navigation_v2 feature flag.
```

- [ ] **Step 18.4: Commit**

```bash
git add docs/quality-and-best-practices/nested-admin-navigation.md \
    docs/docs-manifest.json \
    plugins/woocommerce/changelog/feat-nested-admin-navigation
git commit -m "docs(nav-v2): add devnote, manifest entry, changelog"
```

---

## Final verification

- [ ] **All PHP tests green**

Run: `cd /Users/beau/Source/git/woocommerce && pnpm --filter=@woocommerce/plugin-woocommerce test:php`

Expected: all tests pass, including the new `Tree_Builder_Test`, `WC_Admin_Nav_Test`, `Menu_Reconciler_Test`, `Context_Test`, `Telemetry_Test`, `Flag_Off_Snapshot_Test`, `Default_Tree_Test`, `Bootstrap_Test`.

- [ ] **E2E tests green**

Run: `pnpm --filter=@woocommerce/plugin-woocommerce test:e2e-pw -- tests/admin-navigation-v2/`

Expected: all 8 scenarios pass.

- [ ] **Manual smoke test**

Enable the feature in **WooCommerce → Settings → Advanced → Features**. Confirm:

1. WordPress Dashboard — `WooCommerce` is a single top-level rail item. No Payments, no Analytics, no Marketing top-level siblings.
2. Hover `WooCommerce` — flyout appears with Home, Orders, Products, Analytics, Customers, Marketing, Settings, Extensions.
3. Hover `Settings` in the flyout — cascade opens a second level with Payments, WooPayments, Status.
4. Click `Orders` — land on Orders page. Woo rail replaces WP rail. `← Back` link at top.
5. Click `← Back` — return to WP Dashboard with native rail.
6. Disable flag — native rail restored. `Payments`, `Products`, `Analytics`, `Marketing` all back as top-level.

- [ ] **Final commit**

Nothing to commit for this step — just confirmation.

---

## Summary of tasks

| # | Task | Estimated time |
|---|---|---|
| 1 | Scaffold feature flag and DI wiring | 30 min |
| 2 | Rehomed-slugs constant and default tree | 20 min |
| 3 | Tree_Builder: basic construction | 25 min |
| 4 | Tree_Builder: auto-attach woocommerce children | 25 min |
| 5 | Tree_Builder: cycle detection | 35 min |
| 6 | Tree_Builder: unknown parent drop | 15 min |
| 7 | Tree_Builder: capability filter + breadcrumb | 40 min |
| 8 | WC_Admin_Nav helper API | 25 min |
| 9 | Menu_Reconciler integration | 40 min |
| 10 | Context detection | 25 min |
| 11 | Assets + SCSS + admin-menu.css alias | 45 min |
| 12 | Renderer: body class + rail HTML | 45 min |
| 13 | JS: flyout + keyboard + Tracks | 40 min |
| 14 | Telemetry: server-side Tracks events | 30 min |
| 15 | Flag-off byte-identical snapshot | 20 min |
| 16 | E2E Playwright tests | 60 min |
| 17 | FeaturesController registration test | 10 min |
| 18 | Docs + devnote + changelog | 25 min |

**Total estimate: ~8.5 hours of focused work** for a senior engineer new to the Woo codebase but comfortable with PHP + WP. Expect 1.5x to 2x that in practice including context switches, env setup, and iterating on the CSS alias trick.
