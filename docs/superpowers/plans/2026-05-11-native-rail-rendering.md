# Native Rail Rendering Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** On Woo pages, rewrite `$menu`/`$submenu` so WordPress's native renderer emits the Woo rail (and first-level flyouts). Reduce `admin-navigation-v2.js` to the third-level cascade only.

**Architecture:** A new `Native_Rail_Splicer` runs from `Menu_Reconciler::reconcile()` *after* the tree is built. When `Context::is_woo_page()` returns true, it: (1) relabels WP's `index.php` entry as "Dashboard" with a back-arrow icon and clears its submenu; (2) deletes all other non-Woo top-level `$menu` entries; (3) splices each Woo tree root (children of the synthetic `woocommerce` root) into `$menu` with the icon/capability/position carried from the tree; (4) populates `$submenu[$root_slug]` with first-level tree children; (5) preserves access-check submenu entries for any descendant slugs that WP needs to resolve; (6) sets `$parent_file` / `$submenu_file` via filters so WP's `current` highlighting matches the tree's resolved current slug. The JS keeps only `injectNativeCascade()`, generalized to walk every rail root's flyout (not just `#toplevel_page_woocommerce`).

**Tech Stack:** PHP 8.1, WordPress core admin menu globals (`$menu`, `$submenu`, `$parent_file`, `$submenu_file`), jQuery (for the JS cascade), PHPUnit 9.6 via `wp-env`.

**Scope boundaries:**
- In: rail rendering on Woo pages, the "Back to Dashboard" entry, active-item highlighting, access-check preservation, JS pruning.
- In: hover cascade behavior on **non-Woo** pages (still works, just via the existing `injectNativeCascade()` path).
- Out: changes to the tree shape, the `woocommerce_admin_menu_tree` filter contract, the `Rehomed_Slugs` list, telemetry, or feature-flag plumbing.
- Out: replacing the legacy `client/legacy/js` build pipeline; we still edit the source JS file at `client/legacy/js/admin/admin-navigation-v2.js`.

---

## File Structure

**Create:**
- `plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php` — the new class. Single responsibility: mutate `$menu`/`$submenu` for native rail rendering on Woo pages. Pure functions where possible; the one stateful call writes the globals.
- `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php` — PHPUnit test class with `setUp()`/`tearDown()` that backs up and restores `$menu`/`$submenu` (mirrors `Menu_Reconciler_Test.php`).

**Modify:**
- `plugins/woocommerce/src/Internal/Admin/Navigation/Menu_Reconciler.php` — call the splicer at the end of `reconcile()`; keep the existing `$submenu['woocommerce']` rebuild for non-Woo pages (the hover cascade still needs it).
- `plugins/woocommerce/src/Internal/Admin/Navigation/Assets.php` — drop `backLabel` from `wcNavV2Config` (no longer consumed).
- `plugins/woocommerce/src/Internal/Admin/Navigation/Renderer.php` — unchanged structurally; the body class still triggers SCSS scoping.
- `plugins/woocommerce/client/legacy/js/admin/admin-navigation-v2.js` — delete `injectWooRail()`, `buildBackItem()`, `buildRailItem()`, `buildMenuImage()`, plus the rail-replacement branch in DOM-ready. Generalize `injectNativeCascade()` to walk all rail roots.
- `plugins/woocommerce/client/legacy/css/admin-navigation-v2.scss` — remove rules that target the JS-injected back item / rail-replacement; keep cascade styles.
- `docs/best-practices/nested-admin-navigation.md` — update the "When enabled" section to describe the new architecture.

**Why this split:** The splicer is the new behavior and earns its own file. Everything else is a targeted edit. We are not refactoring `Tree_Builder`, `Context`, `Rehomed_Slugs`, `default-tree.php`, `WC_Admin_Nav`, `Telemetry`, or any settings-tab logic — those remain the source of the tree the splicer consumes.

---

## Task 1: Test scaffolding for `Native_Rail_Splicer`

**Files:**
- Create: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php`

- [ ] **Step 1: Write the failing scaffold test**

Create `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php`:

```php
<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Native_Rail_Splicer;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Native_Rail_Splicer
 */
class Native_Rail_Splicer_Test extends \WC_Unit_Test_Case {

	/** @var array|null */
	private $menu_backup;
	/** @var array|null */
	private $submenu_backup;

	public function setUp(): void {
		parent::setUp();
		global $menu, $submenu;
		$this->menu_backup    = is_array( $menu ) ? $menu : null;
		$this->submenu_backup = is_array( $submenu ) ? $submenu : null;
		$menu                 = array();
		$submenu              = array();
	}

	public function tearDown(): void {
		global $menu, $submenu;
		$menu    = null === $this->menu_backup ? array() : $this->menu_backup;
		$submenu = null === $this->submenu_backup ? array() : $this->submenu_backup;
		parent::tearDown();
	}

	public function test_class_exists(): void {
		$this->assertTrue( class_exists( Native_Rail_Splicer::class ) );
	}
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run:
```bash
pnpm test:php:env -- --filter Native_Rail_Splicer_Test
```
Expected: FAIL with `Class "Automattic\WooCommerce\Internal\Admin\Navigation\Native_Rail_Splicer" not found`.

- [ ] **Step 3: Commit the failing scaffold**

```bash
git add plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php
git commit -m "Add failing scaffold for Native_Rail_Splicer test"
```

---

## Task 2: Create the `Native_Rail_Splicer` class skeleton

**Files:**
- Create: `plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php`

- [ ] **Step 1: Create the class file**

Create `plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php`:

```php
<?php

declare( strict_types = 1 );

/**
 * Native rail splicer for navigation_v2.
 *
 * On Woo pages, mutates the `$menu` and `$submenu` globals so WordPress's
 * native admin-menu renderer emits the Woo rail. Eliminates the JS-driven
 * `#adminmenu.empty()` + rebuild path; the JS now only injects the
 * third-level cascade.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Splices the tree into `$menu`/`$submenu` for native rail rendering.
 */
class Native_Rail_Splicer {

	/**
	 * Splice the tree into the global $menu/$submenu when on a Woo page.
	 *
	 * No-op when off a Woo page — non-Woo pages keep WP's native rail and
	 * the existing `$submenu['woocommerce']` flyout (built by Menu_Reconciler).
	 *
	 * @param array $tree Final reconciled tree.
	 */
	public function splice( array $tree ): void {
		if ( ! Context::is_woo_page( $tree ) ) {
			return;
		}

		// Subsequent tasks fill this in.
	}
}
```

- [ ] **Step 2: Verify the scaffold test passes**

Run:
```bash
pnpm test:php:env -- --filter Native_Rail_Splicer_Test
```
Expected: PASS (1 test, 1 assertion).

- [ ] **Step 3: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php
git commit -m "Add Native_Rail_Splicer skeleton"
```

---

## Task 3: Splicer relabels Dashboard as "Dashboard" with back-arrow icon

**Why this matters:** WP's `index.php` entry is the user's escape hatch back to the WordPress dashboard. By relabeling it in place (rather than injecting a synthetic Back item) we get the URL, capability, and access checks for free. Its native submenu (Home / Updates) is cleared so it renders as a flat single item.

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php`

- [ ] **Step 1: Write the failing test**

Append to `Native_Rail_Splicer_Test.php` inside the class:

```php
public function test_splice_relabels_dashboard_and_swaps_icon(): void {
	global $menu, $submenu;
	// WP's typical index.php registration: position 2, title "Dashboard",
	// cap "read", slug "index.php", page_title "Dashboard", empty class,
	// hookname "menu-dashboard", icon "dashicons-dashboard".
	$menu[2] = array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' );
	$submenu['index.php'] = array(
		array( 'Home', 'read', 'index.php' ),
		array( 'Updates', 'read', 'update-core.php' ),
	);

	$tree = array(
		'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		'wc-admin'    => array( 'parent' => 'woocommerce', 'title' => 'Home', 'position' => 10, 'capability' => 'manage_woocommerce' ),
	);

	// Force is_woo_page to true by visiting wc-admin.
	$_GET['page']               = 'wc-admin';
	$GLOBALS['pagenow']         = 'admin.php';

	( new Native_Rail_Splicer() )->splice( $tree );

	$this->assertSame( 'Dashboard', $menu[2][0] );
	$this->assertSame( 'index.php', $menu[2][2] );
	$this->assertSame( 'dashicons-arrow-left-alt', $menu[2][6] );
	$this->assertSame( array(), $submenu['index.php'] ?? array(), 'Dashboard submenu cleared.' );
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run:
```bash
pnpm test:php:env -- --filter test_splice_relabels_dashboard_and_swaps_icon
```
Expected: FAIL — `index.php` entry is unchanged because `splice()` is still a no-op past the gate.

- [ ] **Step 3: Implement Dashboard relabeling**

Replace the body of `splice()` in `Native_Rail_Splicer.php`:

```php
public function splice( array $tree ): void {
	if ( ! Context::is_woo_page( $tree ) ) {
		return;
	}

	$this->relabel_dashboard();
}

/**
 * Relabel WP's `index.php` entry to act as the rail's back-to-Dashboard
 * link. Replace its icon with `dashicons-arrow-left-alt` and clear its
 * submenu (Home / Updates) so it renders as a single flat row.
 *
 * No-op if WP's Dashboard entry isn't present (e.g. user lacks `read`).
 */
private function relabel_dashboard(): void {
	global $menu, $submenu;

	foreach ( $menu as $key => $entry ) {
		if ( ! isset( $entry[2] ) || 'index.php' !== $entry[2] ) {
			continue;
		}
		$menu[ $key ][0] = __( 'Dashboard', 'woocommerce' );
		$menu[ $key ][3] = __( 'Dashboard', 'woocommerce' );
		$menu[ $key ][6] = 'dashicons-arrow-left-alt';
	}

	if ( isset( $submenu['index.php'] ) ) {
		$submenu['index.php'] = array();
	}
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
pnpm test:php:env -- --filter test_splice_relabels_dashboard_and_swaps_icon
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php \
        plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php
git commit -m "Native rail splicer: relabel Dashboard entry as back link"
```

---

## Task 4: Splicer strips non-Woo, non-Dashboard top-level menu entries

**Why this matters:** With Dashboard preserved and Woo roots about to be spliced in, every other top-level entry (Posts, Pages, Comments, Plugins, etc.) should be removed so WP's renderer emits a Woo-only rail. We *do not* call `unset()`-then-`array_values()` because numeric keys encode positions; we keep keys and unset entries.

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php`

- [ ] **Step 1: Write the failing test**

Append to `Native_Rail_Splicer_Test.php`:

```php
public function test_splice_removes_non_woo_top_level_entries_keeping_dashboard_and_woocommerce(): void {
	global $menu, $submenu;
	$menu = array(
		2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
		5  => array( 'Posts', 'edit_posts', 'edit.php', 'Posts', '', 'menu-posts', 'dashicons-admin-post' ),
		10 => array( 'Media', 'upload_files', 'upload.php', 'Media', '', 'menu-media', 'dashicons-admin-media' ),
		55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
		56 => array( 'Plugins', 'activate_plugins', 'plugins.php', 'Plugins', '', 'menu-plugins', 'dashicons-admin-plugins' ),
	);
	$submenu['index.php']  = array( array( 'Home', 'read', 'index.php' ) );
	$submenu['woocommerce'] = array( array( 'Home', 'manage_woocommerce', 'wc-admin' ) );

	$tree = array(
		'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		'wc-admin'    => array( 'parent' => 'woocommerce', 'title' => 'Home', 'position' => 10, 'capability' => 'manage_woocommerce' ),
	);

	$_GET['page']               = 'wc-admin';
	$GLOBALS['pagenow']         = 'admin.php';

	( new Native_Rail_Splicer() )->splice( $tree );

	$remaining_slugs = array_values( array_filter( array_map(
		static fn( $entry ) => is_array( $entry ) && isset( $entry[2] ) ? $entry[2] : null,
		$menu
	) ) );
	sort( $remaining_slugs );
	$this->assertSame( array( 'index.php', 'woocommerce' ), $remaining_slugs );
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
pnpm test:php:env -- --filter test_splice_removes_non_woo_top_level_entries_keeping_dashboard_and_woocommerce
```
Expected: FAIL — Posts/Media/Plugins still in `$menu`.

- [ ] **Step 3: Implement non-Woo strip**

In `Native_Rail_Splicer.php`, extend `splice()` and add a helper:

```php
public function splice( array $tree ): void {
	if ( ! Context::is_woo_page( $tree ) ) {
		return;
	}

	$this->relabel_dashboard();
	$this->strip_non_woo_top_level();
}

/**
 * Remove every `$menu` top-level entry that isn't `index.php` (the relabeled
 * Dashboard back link) or `woocommerce` (WC's own registration, used by the
 * existing $submenu['woocommerce'] for access checks; the entry itself will
 * be hidden via `hide-if-js` in Task 5).
 *
 * Preserves numeric keys (= WP-native position slots) by using `unset()`
 * rather than `array_values()`. We are not interleaving with non-Woo items,
 * so absolute positions don't matter visually — but keeping keys avoids
 * disturbing any code that reads $menu by position later in the request.
 */
private function strip_non_woo_top_level(): void {
	global $menu, $submenu;

	$keep = array( 'index.php', 'woocommerce' );
	foreach ( $menu as $key => $entry ) {
		if ( ! isset( $entry[2] ) ) {
			continue;
		}
		if ( in_array( $entry[2], $keep, true ) ) {
			continue;
		}
		unset( $menu[ $key ] );
	}

	// Also drop separators — WP renders them as visual breaks; we don't
	// want stray separators between Dashboard and the Woo roots.
	foreach ( $menu as $key => $entry ) {
		if ( ! isset( $entry[2] ) ) {
			continue;
		}
		if ( 0 === strpos( (string) $entry[2], 'separator' ) ) {
			unset( $menu[ $key ] );
		}
	}
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
pnpm test:php:env -- --filter test_splice_removes_non_woo_top_level_entries_keeping_dashboard_and_woocommerce
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php \
        plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php
git commit -m "Native rail splicer: strip non-Woo top-level entries on Woo pages"
```

---

## Task 5: Splice Woo tree roots into `$menu` as native top-level entries

**Why this matters:** The Woo rail items (Home, Orders, Products, Marketing, Analytics, Settings, Extensions, …) are children of the synthetic `woocommerce` tree root. To get WP's renderer to emit them as top-level rail items, each becomes its own `$menu` entry. The original `woocommerce` entry is hidden via `hide-if-js` so its existing `$submenu['woocommerce']` access-check entries remain valid.

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php`

- [ ] **Step 1: Write the failing test**

Append to `Native_Rail_Splicer_Test.php`:

```php
public function test_splice_inserts_woo_roots_into_menu_with_icon_and_capability(): void {
	global $menu, $submenu;
	$menu = array(
		2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
		55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
	);
	$submenu['woocommerce'] = array( array( 'Home', 'manage_woocommerce', 'wc-admin' ) );

	$tree = array(
		'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		'wc-admin'    => array(
			'parent'     => 'woocommerce',
			'title'      => 'Home',
			'icon'       => 'dashicons-admin-home',
			'position'   => 10,
			'capability' => 'manage_woocommerce',
		),
		'wc-orders'   => array(
			'parent'     => 'woocommerce',
			'title'      => 'Orders',
			'icon'       => 'dashicons-list-view',
			'position'   => 20,
			'capability' => 'edit_shop_orders',
		),
	);

	$_GET['page']       = 'wc-admin';
	$GLOBALS['pagenow'] = 'admin.php';

	( new Native_Rail_Splicer() )->splice( $tree );

	// Collect rail entries in the order they will render (by numeric key asc).
	ksort( $menu );
	$entries_by_slug = array();
	foreach ( $menu as $entry ) {
		if ( isset( $entry[2] ) ) {
			$entries_by_slug[ $entry[2] ] = $entry;
		}
	}

	$this->assertArrayHasKey( 'wc-admin', $entries_by_slug );
	$this->assertSame( 'Home', $entries_by_slug['wc-admin'][0] );
	$this->assertSame( 'manage_woocommerce', $entries_by_slug['wc-admin'][1] );
	$this->assertSame( 'dashicons-admin-home', $entries_by_slug['wc-admin'][6] );

	$this->assertArrayHasKey( 'wc-orders', $entries_by_slug );
	$this->assertSame( 'edit_shop_orders', $entries_by_slug['wc-orders'][1] );

	// The original woocommerce entry is preserved but hidden.
	$this->assertArrayHasKey( 'woocommerce', $entries_by_slug );
	$this->assertStringContainsString( 'hide-if-js', (string) $entries_by_slug['woocommerce'][4] );
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
pnpm test:php:env -- --filter test_splice_inserts_woo_roots_into_menu_with_icon_and_capability
```
Expected: FAIL — no `wc-admin`/`wc-orders` entries in `$menu`.

- [ ] **Step 3: Implement root splicing**

In `Native_Rail_Splicer.php`, extend `splice()` and add helpers:

```php
public function splice( array $tree ): void {
	if ( ! Context::is_woo_page( $tree ) ) {
		return;
	}

	$this->relabel_dashboard();
	$this->strip_non_woo_top_level();
	$this->hide_woocommerce_top_level();
	$this->insert_woo_roots( $tree );
}

/**
 * Mark the original `woocommerce` $menu entry as `hide-if-js`. We keep
 * the entry so $submenu['woocommerce'] (which Menu_Reconciler already
 * rebuilds for the hover cascade and access checks) stays valid; we just
 * don't want WP to render it as a rail item.
 */
private function hide_woocommerce_top_level(): void {
	global $menu;
	foreach ( $menu as $key => $entry ) {
		if ( ! isset( $entry[2] ) || 'woocommerce' !== $entry[2] ) {
			continue;
		}
		$existing      = isset( $entry[4] ) ? (string) $entry[4] : '';
		$menu[ $key ][4] = trim( $existing . ' hide-if-js' );
	}
}

/**
 * Splice each Woo tree root (child of the synthetic `woocommerce` root)
 * into $menu as its own top-level entry. Numeric keys are derived from
 * the tree node's `position` so the rail order matches the tree's
 * declared order. Keys are offset so they sit after `index.php` (key 2)
 * and never collide with the preserved Dashboard/woocommerce entries.
 *
 * Entry tuple shape (WP convention):
 *   [ menu_title, capability, slug, page_title, css_class, hookname, icon ]
 *
 * @param array $tree Final reconciled tree.
 */
private function insert_woo_roots( array $tree ): void {
	global $menu;

	$roots = array();
	foreach ( $tree as $slug => $node ) {
		if ( ( $node['parent'] ?? null ) !== 'woocommerce' ) {
			continue;
		}
		if ( ! empty( $node['hidden'] ) ) {
			continue;
		}
		$roots[ $slug ] = $node;
	}

	uasort(
		$roots,
		static fn( $a, $b ) => ( $a['position'] ?? 0 ) <=> ( $b['position'] ?? 0 )
	);

	// Offset = 100 keeps us clear of Dashboard (2) and any preserved
	// non-Woo entries left in the [2..99] band. We add the tree position
	// directly so within the Woo group the visual order matches the tree.
	$base = 100;
	foreach ( $roots as $slug => $node ) {
		$key   = $base + (int) ( $node['position'] ?? 0 );
		$title = (string) ( $node['title'] ?? $slug );
		$cap   = (string) ( $node['capability'] ?? 'read' );
		$icon  = (string) ( $node['icon'] ?? 'dashicons-admin-generic' );

		// Avoid clobbering an existing key (e.g. if `position` collides).
		while ( isset( $menu[ $key ] ) ) {
			$key++;
		}
		$menu[ $key ] = array(
			$title,
			$cap,
			$slug,
			$title,
			'menu-top toplevel_page_' . self::css_slug( $slug ),
			'toplevel_page_' . self::css_slug( $slug ),
			$icon,
		);
	}
}

/**
 * CSS-safe slug for menu IDs. Mirrors the JS `cssSlug()` so server- and
 * client-rendered IDs match (the JS cascade reads `#toplevel_page_<slug>`).
 *
 * @param string $slug Tree slug.
 */
private static function css_slug( string $slug ): string {
	return (string) preg_replace( '/[^A-Za-z0-9_-]/', '-', $slug );
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
pnpm test:php:env -- --filter test_splice_inserts_woo_roots_into_menu_with_icon_and_capability
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php \
        plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php
git commit -m "Native rail splicer: insert Woo tree roots as native top-level menu entries"
```

---

## Task 6: Populate `$submenu` for each Woo root from first-level tree children

**Why this matters:** WP's native renderer emits the first-level flyout straight from `$submenu[$root_slug]`. The third-level cascade (grandchildren) is still JS-driven in Task 11.

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php`

- [ ] **Step 1: Write the failing test**

Append to `Native_Rail_Splicer_Test.php`:

```php
public function test_splice_populates_submenu_for_each_root_with_first_level_children(): void {
	global $menu, $submenu;
	$menu = array(
		2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
		55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
	);

	$tree = array(
		'woocommerce'           => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		'wc-admin&path=/marketing' => array(
			'parent'     => 'woocommerce',
			'title'      => 'Marketing',
			'icon'       => 'dashicons-megaphone',
			'position'   => 40,
			'capability' => 'manage_woocommerce',
		),
		'wc-admin&path=/marketing/overview' => array(
			'parent'     => 'wc-admin&path=/marketing',
			'title'      => 'Overview',
			'position'   => 10,
			'capability' => 'manage_woocommerce',
		),
		'wc-admin&path=/marketing/coupons'  => array(
			'parent'     => 'wc-admin&path=/marketing',
			'title'      => 'Coupons',
			'position'   => 20,
			'capability' => 'manage_woocommerce',
		),
	);

	$_GET['page']       = 'wc-admin';
	$GLOBALS['pagenow'] = 'admin.php';

	( new Native_Rail_Splicer() )->splice( $tree );

	$this->assertArrayHasKey( 'wc-admin&path=/marketing', $submenu );
	$slugs = array_map( static fn( $entry ) => $entry[2], $submenu['wc-admin&path=/marketing'] );
	$this->assertSame(
		array( 'wc-admin&path=/marketing/overview', 'wc-admin&path=/marketing/coupons' ),
		$slugs
	);
	$this->assertSame( 'Overview', $submenu['wc-admin&path=/marketing'][0][0] );
	$this->assertSame( 'manage_woocommerce', $submenu['wc-admin&path=/marketing'][0][1] );
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
pnpm test:php:env -- --filter test_splice_populates_submenu_for_each_root_with_first_level_children
```
Expected: FAIL — `$submenu['wc-admin&path=/marketing']` missing.

- [ ] **Step 3: Implement submenu population**

In `Native_Rail_Splicer.php`, extend `splice()` and add a helper:

```php
public function splice( array $tree ): void {
	if ( ! Context::is_woo_page( $tree ) ) {
		return;
	}

	$this->relabel_dashboard();
	$this->strip_non_woo_top_level();
	$this->hide_woocommerce_top_level();
	$this->insert_woo_roots( $tree );
	$this->populate_root_submenus( $tree );
}

/**
 * For each Woo tree root, write `$submenu[$root_slug]` with that root's
 * first-level children (grandchildren stay tree-only — the JS cascade
 * picks them up at render time).
 *
 * Entry shape: `[ title, capability, slug, page_title, classes ]`. We
 * write `page_title` = title and leave `classes` blank; WP appends to
 * classes for `current` highlighting at render time.
 *
 * @param array $tree Final reconciled tree.
 */
private function populate_root_submenus( array $tree ): void {
	global $submenu;

	$by_parent = array();
	foreach ( $tree as $slug => $node ) {
		$parent = $node['parent'] ?? null;
		if ( null === $parent ) {
			continue;
		}
		$by_parent[ $parent ][ $slug ] = $node;
	}

	foreach ( $tree as $slug => $node ) {
		if ( ( $node['parent'] ?? null ) !== 'woocommerce' ) {
			continue;
		}
		if ( ! isset( $by_parent[ $slug ] ) ) {
			continue;
		}

		$children = $by_parent[ $slug ];
		uasort(
			$children,
			static fn( $a, $b ) => ( $a['position'] ?? 0 ) <=> ( $b['position'] ?? 0 )
		);

		$entries = array();
		foreach ( $children as $child_slug => $child ) {
			if ( ! empty( $child['hidden'] ) ) {
				continue;
			}
			$title = (string) ( $child['title'] ?? $child_slug );
			$cap   = (string) ( $child['capability'] ?? 'read' );
			$url   = (string) ( $child['url'] ?? $child_slug );
			$entries[] = array( $title, $cap, $url, $title, '' );
		}

		if ( ! empty( $entries ) ) {
			$submenu[ $slug ] = $entries;
		}
	}
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
pnpm test:php:env -- --filter test_splice_populates_submenu_for_each_root_with_first_level_children
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php \
        plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php
git commit -m "Native rail splicer: populate per-root submenus from tree children"
```

---

## Task 7: Active-item highlighting via `parent_file` / `submenu_file` filters

**Why this matters:** WP's renderer adds `current`/`wp-has-current-submenu`/`wp-menu-open` classes by comparing each rendered entry's slug against the globals `$parent_file` and `$submenu_file`. For compound slugs like `wc-admin&path=/marketing`, those globals don't naturally match. We resolve the current slug from the tree (via `Context::resolve_current_slug()`) and override the globals.

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php`

- [ ] **Step 1: Write the failing test**

Append to `Native_Rail_Splicer_Test.php`:

```php
public function test_splice_sets_parent_file_and_submenu_file_via_filters_for_compound_current_slug(): void {
	global $menu, $submenu;
	$menu                  = array(
		2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
		55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
	);

	$tree = array(
		'woocommerce'                       => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		'wc-admin&path=/marketing'          => array(
			'parent'     => 'woocommerce',
			'title'      => 'Marketing',
			'position'   => 40,
			'capability' => 'manage_woocommerce',
		),
		'wc-admin&path=/marketing/coupons'  => array(
			'parent'     => 'wc-admin&path=/marketing',
			'title'      => 'Coupons',
			'position'   => 20,
			'capability' => 'manage_woocommerce',
		),
	);

	// Pretend we're on the Coupons page.
	$_GET['page']       = 'wc-admin';
	$_GET['path']       = '/marketing/coupons';
	$GLOBALS['pagenow'] = 'admin.php';

	( new Native_Rail_Splicer() )->splice( $tree );

	$this->assertSame( 'wc-admin&path=/marketing', apply_filters( 'parent_file', 'something-else' ) );
	$this->assertSame( 'wc-admin&path=/marketing/coupons', apply_filters( 'submenu_file', 'something-else' ) );
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
pnpm test:php:env -- --filter test_splice_sets_parent_file_and_submenu_file_via_filters_for_compound_current_slug
```
Expected: FAIL — filters return `something-else`.

- [ ] **Step 3: Implement the filter wiring**

In `Native_Rail_Splicer.php`, extend `splice()` and add a helper:

```php
public function splice( array $tree ): void {
	if ( ! Context::is_woo_page( $tree ) ) {
		return;
	}

	$this->relabel_dashboard();
	$this->strip_non_woo_top_level();
	$this->hide_woocommerce_top_level();
	$this->insert_woo_roots( $tree );
	$this->populate_root_submenus( $tree );
	$this->force_current_highlight( $tree );
}

/**
 * Resolve the current tree slug (via Context) and force WP's `parent_file`
 * and `submenu_file` filters to emit it so the renderer applies `current`
 * highlighting to the correct rail root and submenu item.
 *
 * `parent_file` returns the rail root (the ancestor whose parent is
 * `woocommerce`). `submenu_file` returns the resolved slug itself when it
 * is a first-level child; for grandchild pages the JS cascade applies
 * `current` separately at render time.
 *
 * @param array $tree Final reconciled tree.
 */
private function force_current_highlight( array $tree ): void {
	$current = Context::resolve_current_slug( $tree );
	if ( null === $current ) {
		return;
	}

	$root = $this->ancestor_root_slug( $tree, $current );
	if ( null === $root ) {
		return;
	}

	add_filter(
		'parent_file',
		static fn( string $_ ): string => $root,
		PHP_INT_MAX
	);
	add_filter(
		'submenu_file',
		static fn( string $_ ): string => $current,
		PHP_INT_MAX
	);
}

/**
 * Walk the parent chain from `$slug` and return the slug whose parent is
 * `woocommerce` (i.e. the rail root for that subtree). Returns null if the
 * slug isn't in the tree or doesn't descend from a Woo root.
 *
 * @param array  $tree Tree.
 * @param string $slug Current slug.
 */
private function ancestor_root_slug( array $tree, string $slug ): ?string {
	$walk = $slug;
	while ( isset( $tree[ $walk ] ) ) {
		$parent = $tree[ $walk ]['parent'] ?? null;
		if ( 'woocommerce' === $parent ) {
			return $walk;
		}
		if ( null === $parent ) {
			return null;
		}
		$walk = $parent;
	}
	return null;
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
pnpm test:php:env -- --filter test_splice_sets_parent_file_and_submenu_file_via_filters_for_compound_current_slug
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php \
        plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php
git commit -m "Native rail splicer: force current highlighting via parent_file/submenu_file"
```

---

## Task 8: Wire the splicer into `Menu_Reconciler`

**Why this matters:** The splicer needs to run *after* the tree is finalized in `reconcile()`. It doesn't register any WordPress hooks of its own — `Menu_Reconciler` invokes it directly — so it doesn't need Bootstrap registration. We default-instantiate it in the constructor with an optional override for tests.

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Menu_Reconciler.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Menu_Reconciler_Test.php`

- [ ] **Step 1: Write a failing integration test**

Append to `Menu_Reconciler_Test.php` (inside the class):

```php
public function test_reconcile_invokes_native_rail_splicer_on_woo_pages(): void {
	global $menu, $submenu;
	$menu = array(
		2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
		5  => array( 'Posts', 'edit_posts', 'edit.php', 'Posts', '', 'menu-posts', 'dashicons-admin-post' ),
		55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
	);
	$submenu['woocommerce'] = array(
		array( 'Home', 'manage_woocommerce', 'wc-admin' ),
	);

	$_GET['page']       = 'wc-admin';
	$GLOBALS['pagenow'] = 'admin.php';

	wp_set_current_user( $this->admin_user_id );
	( new Menu_Reconciler() )->reconcile();

	$top_slugs = array_values( array_filter( array_map(
		static fn( $entry ) => is_array( $entry ) && isset( $entry[2] ) ? $entry[2] : null,
		$menu
	) ) );
	$this->assertNotContains( 'edit.php', $top_slugs, 'Posts should be stripped on Woo pages.' );
	$this->assertContains( 'wc-admin', $top_slugs, 'Home root should be spliced in.' );
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
pnpm test:php:env -- --filter test_reconcile_invokes_native_rail_splicer_on_woo_pages
```
Expected: FAIL — Posts still in `$menu`, no `wc-admin` top-level.

- [ ] **Step 3: Inject and invoke the splicer**

Modify `Menu_Reconciler.php`. Add a property and constructor parameter; call the splicer at the tail of `reconcile()`:

```php
class Menu_Reconciler {

	/** @var Native_Rail_Splicer */
	private $splicer;

	/** @var array|null */
	private static $tree = null;

	public function __construct( ?Native_Rail_Splicer $splicer = null ) {
		$this->splicer = $splicer ?? new Native_Rail_Splicer();
		add_action( 'admin_menu', array( $this, 'reconcile' ), 999 );
		add_filter( 'menu_order', array( $this, 'strip_phantom_slugs' ), 20 );
		add_filter( 'menu_order', array( $this, 'place_woo_after_dashboard' ), 200 );
	}

	public function reconcile(): void {
		// ...existing body unchanged through the line that assigns self::$tree...
		self::$tree = $tree;

		// New: splice into $menu/$submenu for native rendering on Woo pages.
		$this->splicer->splice( $tree );
	}
}
```

(Keep the rest of `reconcile()` exactly as it is. The splicer runs *after* `self::$tree` is set so it sees the final reconciled tree.)

- [ ] **Step 4: Run the test to verify it passes**

```bash
pnpm test:php:env -- --filter test_reconcile_invokes_native_rail_splicer_on_woo_pages
```
Expected: PASS.

- [ ] **Step 5: Run the full Navigation test directory to catch regressions**

```bash
pnpm test:php:env -- --filter 'Internal\\\\Admin\\\\Navigation'
```
Expected: All existing nav tests still PASS. If `Menu_Reconciler_Test`'s pre-existing assertions about `$submenu['woocommerce']` content fail because the splicer overwrote them on a Woo page in a test fixture, fix them by setting the fixture's request to a non-Woo page (`$_GET['page'] = 'edit.php'; $GLOBALS['pagenow'] = 'edit.php';`) — those tests are about the *base* reconcile behaviour, not the rail rewrite.

- [ ] **Step 6: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Menu_Reconciler.php \
        plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Menu_Reconciler_Test.php
git commit -m "Wire Native_Rail_Splicer into Menu_Reconciler"
```

---

## Task 9: Preserve access-check entries for tree descendants

**Why this matters:** WP's `user_can_access_admin_page()` walks `$submenu[$parent_file]` looking for the requested slug to verify capability. Tree pages that are deeper than first level (grandchildren) — or settings tabs — must still resolve when visited directly, but they shouldn't render as visible rail items. We append them with `hide-if-js` so the access check works but the renderer hides them. This mirrors the pattern already in `Menu_Reconciler::replace_woocommerce_submenu()`.

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php`
- Modify: `plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php`

- [ ] **Step 1: Write the failing test**

Append to `Native_Rail_Splicer_Test.php`:

```php
public function test_splice_preserves_grandchild_access_check_entries_as_hide_if_js(): void {
	global $menu, $submenu;
	$menu = array(
		2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
		55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
	);
	// Pretend WP already had `wc-status` registered as a child of woocommerce
	// so direct visits to ?page=wc-status pass the access check.
	$submenu['woocommerce'] = array(
		array( 'Home',   'manage_woocommerce', 'wc-admin' ),
		array( 'Status', 'manage_woocommerce', 'wc-status' ),
	);

	$tree = array(
		'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		'wc-admin'    => array( 'parent' => 'woocommerce', 'title' => 'Home',    'position' => 10, 'capability' => 'manage_woocommerce' ),
		'wc-tools'    => array( 'parent' => 'woocommerce', 'title' => 'Tools',   'position' => 80, 'capability' => 'manage_woocommerce' ),
		'wc-status'   => array(
			// `wc-status` is a grandchild of `woocommerce` in the tree
			// (nested under Tools) but WP registered it as a direct child
			// of `woocommerce`.
			'parent'     => 'wc-tools',
			'title'      => 'Status',
			'position'   => 10,
			'capability' => 'manage_woocommerce',
		),
	);

	$_GET['page']       = 'wc-admin';
	$GLOBALS['pagenow'] = 'admin.php';

	( new Native_Rail_Splicer() )->splice( $tree );

	// `wc-status` should remain present under SOME parent submenu so the
	// access check still resolves. Either kept under `woocommerce` with
	// `hide-if-js`, or attached under `wc-tools`. We require at least one.
	$found_access_entry = false;
	foreach ( $submenu as $parent => $entries ) {
		foreach ( $entries as $entry ) {
			if ( ( $entry[2] ?? null ) !== 'wc-status' ) {
				continue;
			}
			$found_access_entry = true;
			// If kept under `woocommerce`, must be hide-if-js so it doesn't render.
			if ( 'woocommerce' === $parent ) {
				$this->assertStringContainsString( 'hide-if-js', (string) ( $entry[4] ?? '' ) );
			}
		}
	}
	$this->assertTrue( $found_access_entry, 'wc-status access-check entry must survive splice.' );
}
```

- [ ] **Step 2: Run the test to verify it fails**

```bash
pnpm test:php:env -- --filter test_splice_preserves_grandchild_access_check_entries_as_hide_if_js
```
Expected: FAIL — `wc-status` likely missing from any submenu after splice.

- [ ] **Step 3: Implement access-check preservation**

In `Native_Rail_Splicer.php`, extend `splice()` and add a helper:

```php
public function splice( array $tree ): void {
	if ( ! Context::is_woo_page( $tree ) ) {
		return;
	}

	$pre_splice_submenu_woocommerce = $GLOBALS['submenu']['woocommerce'] ?? array();

	$this->relabel_dashboard();
	$this->strip_non_woo_top_level();
	$this->hide_woocommerce_top_level();
	$this->insert_woo_roots( $tree );
	$this->populate_root_submenus( $tree );
	$this->preserve_access_check_entries( $tree, $pre_splice_submenu_woocommerce );
	$this->force_current_highlight( $tree );
}

/**
 * Re-attach as `hide-if-js` any entries that WP originally registered under
 * `$submenu['woocommerce']` whose tree slug is *not* a direct child of a
 * rail root. WP needs these entries somewhere in `$submenu` so
 * `user_can_access_admin_page()` resolves capability for direct page visits.
 *
 * @param array $tree                            Final tree.
 * @param array $pre_splice_submenu_woocommerce  $submenu['woocommerce'] captured before mutations.
 */
private function preserve_access_check_entries( array $tree, array $pre_splice_submenu_woocommerce ): void {
	global $submenu;

	// Build a set of slugs already rendered as visible submenu items.
	$rendered = array();
	foreach ( $submenu as $entries ) {
		foreach ( $entries as $entry ) {
			if ( isset( $entry[2] ) ) {
				$rendered[ (string) $entry[2] ] = true;
			}
		}
	}

	$preserved = array();
	foreach ( $pre_splice_submenu_woocommerce as $entry ) {
		$slug = $entry[2] ?? null;
		if ( null === $slug || isset( $rendered[ (string) $slug ] ) ) {
			continue;
		}
		// Only preserve slugs that the tree actually knows about — anything
		// else was orphan registration we'd rather not surface.
		if ( ! isset( $tree[ (string) $slug ] ) ) {
			continue;
		}
		$existing_classes        = isset( $entry[4] ) ? (string) $entry[4] : '';
		$entry[4]                = trim( $existing_classes . ' hide-if-js' );
		$preserved[]             = $entry;
	}

	if ( ! empty( $preserved ) ) {
		$submenu['woocommerce'] = array_merge(
			$submenu['woocommerce'] ?? array(),
			$preserved
		);
	}
}
```

- [ ] **Step 4: Run the test to verify it passes**

```bash
pnpm test:php:env -- --filter test_splice_preserves_grandchild_access_check_entries_as_hide_if_js
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Native_Rail_Splicer.php \
        plugins/woocommerce/tests/php/src/Internal/Admin/Navigation/Native_Rail_Splicer_Test.php
git commit -m "Native rail splicer: preserve grandchild access-check entries as hide-if-js"
```

---

## Task 10: Generalize `injectNativeCascade()` to walk every rail root's flyout

**Why this matters:** Today the third-level cascade only looks inside `#toplevel_page_woocommerce > .wp-submenu > li`. After native rendering, each rail root has its own `#toplevel_page_<slug>` with its own `.wp-submenu`. The cascade has to iterate all of them.

**Files:**
- Modify: `plugins/woocommerce/client/legacy/js/admin/admin-navigation-v2.js`

- [ ] **Step 1: Replace `injectNativeCascade()`**

In `admin-navigation-v2.js`, replace the existing `injectNativeCascade` function (currently scoped to `#toplevel_page_woocommerce`) with the generalized version:

```javascript
function injectNativeCascade() {
	var tree = window.wcNavV2Config.tree;
	if ( ! tree ) {
		return;
	}

	var byParent = buildByParent( tree );

	// Build a canonical-URL → tree-slug map (unchanged from prior version).
	var urlToSlug = {};
	Object.keys( tree ).forEach( function ( slug ) {
		var target      = tree[ slug ].url || slug;
		var key         = canonicalUrl( toAdminUrl( target ) );
		var existing    = urlToSlug[ key ];
		var thisHasKids = ( ( byParent[ slug ] || [] ).length ) > 0;
		var prevHasKids = existing && ( ( byParent[ existing ] || [] ).length ) > 0;
		if ( ! existing || ( thisHasKids && ! prevHasKids ) ) {
			urlToSlug[ key ] = slug;
		}
	} );

	// Find every rail-root flyout: any #adminmenu top-level <li> whose id
	// starts with `toplevel_page_` and has a `.wp-submenu`. This covers both
	// the legacy single-Woo-rail-item case (non-Woo pages) and the new
	// native-multi-root rail (Woo pages).
	var $rootSubmenuItems = $( '#adminmenu > li.menu-top[id^="toplevel_page_"] > .wp-submenu > li' )
		.not( '.wp-submenu-head' );

	$rootSubmenuItems.each( function () {
		var $li  = $( this );
		var $a   = $li.find( '> a' ).first();
		var href = canonicalUrl( $a.attr( 'href' ) || '' );

		var treeSlug = urlToSlug[ href ];
		if ( ! treeSlug ) {
			return;
		}

		var grandkids = byParent[ treeSlug ];
		if ( ! grandkids || ! grandkids.length ) {
			return;
		}

		$li.addClass( 'wc-nav-v2-has-subflyout' );
		var $nested = $( '<ul class="wp-submenu wc-nav-v2-subflyout"></ul>' );
		grandkids.forEach( function ( kid ) {
			if ( kid.hidden ) {
				return;
			}
			$nested.append(
				$( '<li></li>' ).append(
					$( '<a></a>' )
						.attr( 'href', toAdminUrl( kid.url || kid.slug ) )
						.text( kid.title )
				)
			);
		} );
		$li.append( $nested );
	} );

	// Apply the existing hover behaviour to every rail-root flyout, not
	// just woocommerce.
	$( '#adminmenu > li.menu-top[id^="toplevel_page_"]' ).each( function () {
		var $root = $( this );
		$root.off( 'mouseenter mouseleave mouseover mouseout' );
		bindDelayedHover(
			$( '#adminmenu' ),
			'#' + $root.attr( 'id' ),
			'opensub'
		);
		bindDelayedHover(
			$root,
			'.wp-submenu li.wc-nav-v2-has-subflyout',
			'wc-nav-v2-subopen'
		);
	} );
}
```

- [ ] **Step 2: Commit**

```bash
git add plugins/woocommerce/client/legacy/js/admin/admin-navigation-v2.js
git commit -m "Generalize injectNativeCascade to walk every rail root's flyout"
```

---

## Task 11: Remove the JS rail-replacement path

**Why this matters:** With native rendering live, `injectWooRail()`, `buildBackItem()`, `buildRailItem()`, `buildMenuImage()`, and the `body.wc-nav-v2-rail-ready` reveal mechanism are dead code. Removing them shrinks the JS, eliminates the FOUC-prevention dance, and removes the most fragile DOM operations in this file.

**Files:**
- Modify: `plugins/woocommerce/client/legacy/js/admin/admin-navigation-v2.js`
- Modify: `plugins/woocommerce/client/legacy/css/admin-navigation-v2.scss`

- [ ] **Step 1: Delete `injectWooRail`, `buildBackItem`, `buildRailItem`, `buildMenuImage` from the JS**

In `admin-navigation-v2.js`:

- Delete the function `buildMenuImage()` (lines ~141–175 in the pre-change file).
- Delete the function `buildRailItem()` (lines ~178–257).
- Delete the function `buildBackItem()` (lines ~259–283).
- Delete the function `injectWooRail()` (lines ~285–349).

In the DOM-ready handler at the bottom of the file, replace the `if ( isWooPage ) { injectWooRail(); } else { injectNativeCascade(); }` branch with an unconditional call:

```javascript
$( function () {
	if ( ! window.wcNavV2Config ) {
		return;
	}

	var isWooPage = window.wcNavV2Config.isWooPage === '1';
	try {
		injectNativeCascade();
	} catch ( err ) {
		// eslint-disable-next-line no-console
		console.error( 'navigation_v2: cascade injection failed', err );
	}

	// Tracks — clicks. The rail is now native WP markup either way; scope
	// to the woocommerce-named entries on non-Woo pages and to all rail
	// items on Woo pages.
	var clickScope = isWooPage ? '#adminmenu a' : '#toplevel_page_woocommerce a';
	$( document ).on( 'click.wcnavv2', clickScope, function () {
		var $a      = $( this );
		var href    = $a.attr( 'href' ) || '';
		var depth   = $a.parents( 'li.wp-has-submenu' ).length;
		var surface = isWooPage ? 'rail' : 'hover';
		tracks( 'navigation_v2_item_clicked', { href: href, depth: depth, surface: surface } );
	} );

	// Tracks — back link. The relabeled Dashboard entry has slug `index.php`.
	$( document ).on( 'click.wcnavv2', '#adminmenu > li > a[href$="index.php"]', function () {
		tracks( 'navigation_v2_back_clicked' );
	} );
} );
```

- [ ] **Step 2: Remove the rail-replacement SCSS**

In `admin-navigation-v2.scss`, delete any rules that:
- Hide `#adminmenu` while waiting for `body.wc-nav-v2-rail-ready` (the FOUC dance is no longer needed).
- Target `.wc-nav-v2-back-item`, `#wc-nav-v2-back`, or `.wc-nav-v2-separator` (those classes no longer exist).

Keep all rules targeting `.wc-nav-v2-subflyout`, `.wc-nav-v2-has-subflyout`, and `.wc-nav-v2-subopen` — they remain in use for the cascade.

Run `grep -n "wc-nav-v2-back\|wc-nav-v2-rail-ready\|wc-nav-v2-separator" plugins/woocommerce/client/legacy/css/admin-navigation-v2.scss` and delete each matching block. If the file no longer needs the `.wc-nav-v2-active` body class, leave that one alone — it still gates cascade rules.

- [ ] **Step 3: Commit**

```bash
git add plugins/woocommerce/client/legacy/js/admin/admin-navigation-v2.js \
        plugins/woocommerce/client/legacy/css/admin-navigation-v2.scss
git commit -m "Drop JS rail-replacement path; native WP rendering handles the rail"
```

---

## Task 12: Drop `backLabel` from the localized config

**Files:**
- Modify: `plugins/woocommerce/src/Internal/Admin/Navigation/Assets.php`

- [ ] **Step 1: Remove `backLabel`**

In `Assets.php`, change the `wp_localize_script()` payload:

```php
wp_localize_script(
	self::SCRIPT_HANDLE,
	'wcNavV2Config',
	array(
		'isWooPage'      => Context::is_woo_page( $tree ) ? '1' : '0',
		'adminUrl'       => admin_url(),
		'wpDashboardUrl' => admin_url( 'index.php' ),
		'tree'           => $tree,
	)
);
```

(`wpDashboardUrl` stays — extensions or future code may still read it; cost is trivial. `backLabel` is gone because nothing reads it now.)

- [ ] **Step 2: Commit**

```bash
git add plugins/woocommerce/src/Internal/Admin/Navigation/Assets.php
git commit -m "Drop backLabel from wcNavV2Config — native Dashboard entry replaces it"
```

---

## Task 13: Update the architecture doc

**Files:**
- Modify: `docs/best-practices/nested-admin-navigation.md`

- [ ] **Step 1: Rewrite the architecture section**

Replace the bullet list under "When enabled, the flag:" with:

```markdown
When enabled, the flag:

- Builds a curated tree of Woo navigation items from WP's `$menu`/`$submenu`
  globals, the default tree (`default-tree.php`), and the
  `woocommerce_admin_menu_tree` filter.
- On Woo pages (resolved via `Context::is_woo_page()`), splices that tree
  back into `$menu`/`$submenu` so WordPress's native admin-menu renderer
  emits the Woo rail directly — each tree root becomes its own top-level
  entry, first-level children populate its native flyout, and WP's
  `index.php` (Dashboard) entry is relabeled in place as the rail's
  back-to-Dashboard link.
- On non-Woo pages, leaves the native rail intact; hovering the
  `WooCommerce` item reveals the curated tree as a native flyout
  (built into `$submenu['woocommerce']` server-side).
- A small JS module (`admin-navigation-v2.js`) handles only the third-level
  cascade — flyouts deeper than one level, which WP's native admin rail
  doesn't render. It walks each rail root's submenu after DOM load and
  attaches grandchild items.
```

Remove the "Rail replacement" sub-bullet from the prior version (the rail is now native).

- [ ] **Step 2: Commit**

```bash
git add docs/best-practices/nested-admin-navigation.md
git commit -m "Doc: describe native rail rendering architecture"
```

---

## Task 14: Manual verification + regression sweep

**Files:** none — verification only.

- [ ] **Step 1: Run the full nav PHP test suite**

```bash
pnpm test:php:env -- --filter 'Internal\\\\Admin\\\\Navigation'
```
Expected: All tests PASS.

- [ ] **Step 2: Run PHPStan on changed PHP files**

```bash
cd plugins/woocommerce
composer exec -- phpstan analyse \
  src/Internal/Admin/Navigation/Native_Rail_Splicer.php \
  src/Internal/Admin/Navigation/Menu_Reconciler.php \
  src/Internal/Admin/Navigation/Bootstrap.php \
  src/Internal/Admin/Navigation/Assets.php \
  --memory-limit=2G
```
Expected: 0 errors. If a new error appears, fix it in the code; do not add to the baseline.

- [ ] **Step 3: Lint changed PHP files**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
```
Expected: Clean. Fix any reported issues with `pnpm lint:php:fix -- <file>`.

- [ ] **Step 4: Build the plugin so the JS lands in `assets/`**

```bash
pnpm --filter='@woocommerce/plugin-woocommerce' build
```
Expected: 0 errors.

- [ ] **Step 5: Browser smoke test**

Open the WP admin in a browser pointed at a build with the feature flag enabled. Verify visually:

1. On `/wp-admin/admin.php?page=wc-admin`:
   - Rail's first item is "Dashboard" with a left-arrow icon, linking to `/wp-admin/`.
   - Each Woo top-level item (Home, Orders, Products, Marketing, Analytics, Settings, Extensions) renders as its own rail item.
   - Hovering Marketing shows Coupons / Overview / etc. (first-level flyout).
   - Hovering Settings → Payments shows the payment sub-items (third-level cascade).
   - The currently active page is highlighted in the rail.
2. On `/wp-admin/edit.php` (non-Woo page):
   - Native WP rail renders normally.
   - Hovering the `WooCommerce` item shows the full curated tree, with the third-level cascade on items that have grandchildren.
3. Direct visit to a grandchild page (e.g. `/wp-admin/admin.php?page=wc-status`) loads successfully (access check resolves).

- [ ] **Step 6: Pre-push branch lint**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:changes:branch
```
Expected: Clean. Fix any alignment warnings before pushing.

- [ ] **Step 7: Add changelog entry**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce changelog add
```
Type: `dev`. Description: `Render the navigation_v2 rail via WP-native $menu/$submenu instead of JS DOM replacement.`

- [ ] **Step 8: Commit the changelog**

```bash
git add plugins/woocommerce/changelog/
git commit -m "Add changelog entry for native rail rendering"
```

---

## Notes for the executor

- **Don't touch `Tree_Builder`, `Context`, `Rehomed_Slugs`, `default-tree.php`, `WC_Admin_Nav`, or `Telemetry`.** The splicer reads what those classes produce; rewriting them is out of scope.
- **Don't change the `woocommerce_admin_menu_tree` filter contract.** Extensions depend on it.
- **If a test fixture in `Menu_Reconciler_Test.php` starts failing because the splicer mutated `$menu`,** that fixture is testing reconcile-stage behaviour; set `$_GET['page'] = 'edit.php'` and `$GLOBALS['pagenow'] = 'edit.php'` in that test's `setUp()` so `Context::is_woo_page()` returns false and the splicer is a no-op. Don't disable the splicer just to make a fixture pass.
- **The browser smoke test in Task 14 Step 5 is non-negotiable.** Type-checking and unit tests verify code correctness, not whether the rail looks right.
