<?php
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

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.Classes.ValidClassName.NotCamelCaps, WooCommerce.Commenting.CommentHooks -- Splices the WP $menu/$submenu globals by design; underscore class name and hook re-application are intentional.

/**
 * Splices the tree into `$menu`/`$submenu` for native rail rendering.
 */
class Native_Rail_Splicer {

	/**
	 * Snapshot of the global $menu / $submenu captured at the very start of
	 * splice(), before any mutations. Used by print_wp_rail_panel() to render
	 * the slide-in WP navigation overlay with the original menu items.
	 *
	 * @var array
	 */
	private array $original_menu = array();

	/**
	 * Snapshot of the pre-mutation $submenu global (companion to $original_menu).
	 *
	 * @var array
	 */
	private array $original_submenu = array();

	/**
	 * Store the pre-mutation $menu/$submenu snapshot for print_wp_rail_panel().
	 *
	 * Called by Menu_Reconciler::reconcile() before any modifications so the
	 * WP-rail overlay shows all registered items (third-party plugins, etc.).
	 *
	 * @param array $menu    WP global $menu before any nav-v2 mutations.
	 * @param array $submenu WP global $submenu before any nav-v2 mutations.
	 *
	 * @since 10.9.0
	 */
	public function set_original_menu( array $menu, array $submenu ): void {
		$this->original_menu    = $menu;
		$this->original_submenu = $submenu;
	}

	/**
	 * Splice the tree into the global $menu/$submenu when on a Woo page.
	 *
	 * No-op when off a Woo page — non-Woo pages keep WP's native rail and
	 * the existing `$submenu['woocommerce']` flyout (built by Menu_Reconciler).
	 *
	 * @param array $tree Final reconciled tree.
	 *
	 * @since 10.9.0
	 */
	public function splice( array $tree ): void {
		// Resolve once here and reuse for force_current_highlight() below — both
		// are pure functions of ( $pagenow, $_GET, $tree ), which do not change
		// during splice(). null means we are off a Woo page, so this is a no-op.
		$current = Context::resolve_current_slug( $tree );
		if ( null === $current ) {
			return;
		}

		$pre_splice_submenu_woocommerce = $GLOBALS['submenu']['woocommerce'] ?? array();

		$this->relabel_dashboard();
		$this->strip_non_woo_top_level();
		$this->hide_woocommerce_top_level();
		$this->insert_woo_roots( $tree );
		$this->populate_root_submenus( $tree );
		$this->preserve_access_check_entries( $tree, $pre_splice_submenu_woocommerce );
		$this->map_real_parents_for_access_checks( $tree );
		$this->register_rail_root_hooknames( $tree );
		$this->force_current_highlight( $tree, $current );

		// Some WC subsystems (importers, exporters) register
		// `add_submenu_page` entries on `admin_menu` and then unset them
		// from $submenu on `admin_head` so the URL works but the menu item
		// is hidden. That admin_head pass runs after our splicer and wipes
		// the rail entries we just wrote. Re-populate after WP's
		// admin_head action has finished so menu-header.php sees our
		// version — capture by ref since the same instance is reused for
		// the action callback context.
		add_action(
			'admin_head',
			function () use ( $tree ) {
				$this->populate_root_submenus( $tree );
			},
			PHP_INT_MAX
		);

		// Output the WP rail overlay immediately after #adminmenu is rendered.
		add_action( 'adminmenu', array( $this, 'print_wp_rail_panel' ), 99 );
	}

	/**
	 * Remap each spliced bare-slug rail root back to `woocommerce` via
	 * `$_wp_real_parent_file`, so that `user_can_access_admin_page()` resolves
	 * the slug's hookname to the registration WC originally created.
	 *
	 * Background: after `insert_woo_roots()` puts a slug like `wc-admin` into
	 * `$menu` as a top-level entry, WP's `get_admin_page_parent()` second
	 * branch (which iterates `$menu` for `$menu[i][2] === $plugin_page`) finds
	 * it and returns the slug ITSELF as the parent. `get_plugin_page_hookname()`
	 * then computes `admin_page_<slug>` — which isn't registered, because WC
	 * registered the page via `add_submenu_page('woocommerce', …)` (hookname:
	 * `woocommerce_page_<slug>`). The access check returns false → 403.
	 *
	 * `$_wp_real_parent_file` is WP's documented remap that
	 * `get_admin_page_parent()` consults after the lookup: if `[$slug] =>
	 * 'woocommerce'` is set, the function returns `woocommerce` instead of
	 * `$slug`, and the hookname computation lands on `woocommerce_page_<slug>`.
	 *
	 * Skipped for rail roots that already have an `$admin_page_hooks` entry
	 * (post-type pages, plugins that called `add_menu_page` themselves). Those
	 * have their own legitimate hookname registration under their original
	 * prefix (`product_page_<slug>`, `marketing_page_<slug>`, etc.); remapping
	 * would break it.
	 *
	 * @param array $tree Final reconciled tree.
	 */
	private function map_real_parents_for_access_checks( array $tree ): void {
		global $_wp_real_parent_file, $admin_page_hooks;
		if ( ! is_array( $_wp_real_parent_file ) ) {
			$_wp_real_parent_file = array();
		}
		foreach ( $tree as $slug => $node ) {
			if ( ( $node['parent'] ?? null ) !== 'woocommerce' ) {
				continue;
			}
			if ( isset( $admin_page_hooks[ $slug ] ) ) {
				continue;
			}
			if ( ! isset( $_wp_real_parent_file[ $slug ] ) ) {
				$_wp_real_parent_file[ $slug ] = 'woocommerce';
			}
		}
	}

	/**
	 * Ensure WP's menu URL generator can resolve every rail-root to an
	 * `admin.php?page=<slug>` href across every page context.
	 *
	 * For rail roots without tree children, `menu-header.php` computes the
	 * href via `get_plugin_page_hook( $item[2], 'admin.php' )`. That call
	 * resolves to `<page_type>_page_<slug>` where `<page_type>` comes from
	 * `$admin_page_hooks` keyed on whatever `get_admin_page_parent('admin.php')`
	 * returns for the *current* request — and that varies by `$pagenow` /
	 * `$typenow` / `$plugin_page`. On a Woo settings page it lands on
	 * `'woocommerce'`; on the Products list (`edit.php?post_type=product`)
	 * the `$typenow` branch of `get_admin_page_parent`'s foreach scan steers
	 * it to the Products $submenu entry, yielding `'product'` instead. If
	 * `has_action()` returns false for the computed hookname, the href
	 * falls through to the naked literal (`/wp-admin/wc-orders`) instead of
	 * the working `admin.php?page=wc-orders` form.
	 *
	 * Register a no-op callback under every `<page_type>_page_<slug>`
	 * combination — one stub per registered page_type (`$admin_page_hooks`
	 * value) crossed with every rail-root slug, plus the `admin` /
	 * `toplevel` defaults `get_plugin_page_hookname()` falls back to when
	 * no `$admin_page_hooks` entry matches. `add_action` is idempotent —
	 * if the owning module already registered the real callback, our stub
	 * piles on harmlessly; if not, ours is the only one and `has_action()`
	 * still reports truthy regardless of which page context generated the
	 * hookname lookup.
	 *
	 * @param array $tree Final reconciled tree.
	 */
	private function register_rail_root_hooknames( array $tree ): void {
		global $admin_page_hooks;

		$slugs = array();
		foreach ( $tree as $slug => $node ) {
			if ( ( $node['parent'] ?? null ) === 'woocommerce' ) {
				$slugs[] = (string) $slug;
			}
		}
		if ( empty( $slugs ) ) {
			return;
		}

		$page_types   = array();
		$page_types[] = 'admin';
		$page_types[] = 'toplevel';
		foreach ( (array) $admin_page_hooks as $value ) {
			$page_types[] = (string) $value;
		}
		$page_types = array_unique( array_filter( $page_types, static fn( $v ) => '' !== $v ) );

		foreach ( $page_types as $page_type ) {
			foreach ( $slugs as $slug ) {
				$hookname = $page_type . '_page_' . $slug;
				if ( ! has_action( $hookname ) ) {
					add_action( $hookname, static function (): void {} );
				}
			}
		}
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

		// Build a set of slugs already rendered as visible submenu items under
		// the rail roots. Excludes `$submenu['woocommerce']` itself — that
		// belongs to the (now hidden) `woocommerce` top-level entry and is the
		// bucket we're rebuilding for access checks here.
		$rendered = array();
		foreach ( $submenu as $parent => $entries ) {
			if ( 'woocommerce' === $parent ) {
				continue;
			}
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
			$existing_classes = isset( $entry[4] ) ? (string) $entry[4] : '';
			$entry[4]         = trim( $existing_classes . ' hide-if-js' );
			$preserved[]      = $entry;
		}

		// Replace `$submenu['woocommerce']` with the preserved entries so any
		// visible duplicates (rendered under a rail root) don't appear here
		// without `hide-if-js`. Drop the key entirely when nothing needs
		// preserving so the (hidden) top-level entry has no submenu.
		if ( ! empty( $preserved ) ) {
			$submenu['woocommerce'] = $preserved;
		} else {
			unset( $submenu['woocommerce'] );
		}
	}

	/**
	 * Force WP's `parent_file` and `submenu_file` filters to emit the current
	 * slug so the renderer applies `current` highlighting to the correct rail
	 * root and submenu item.
	 *
	 * `parent_file` returns the rail root (the ancestor whose parent is
	 * `woocommerce`). `submenu_file` returns the resolved slug itself when it
	 * is a first-level child; for grandchild pages the JS cascade applies
	 * `current` separately at render time.
	 *
	 * @param array  $tree    Final reconciled tree.
	 * @param string $current Current tree slug (already resolved by splice()).
	 */
	private function force_current_highlight( array $tree, string $current ): void {
		$root = $this->ancestor_root_slug( $tree, $current );
		if ( null === $root ) {
			return;
		}

		add_filter(
			'parent_file',
			static fn(): string => $root,
			PHP_INT_MAX
		);
		// Must mirror how `populate_root_submenus` writes `$entry[2]`: the
		// url override (if any) then renderable_url(). Otherwise WP's
		// `$submenu_file === $sub_item[2]` highlight check never matches
		// for slugs whose tree node carries a `url` override (e.g.
		// action-scheduler → tools.php?page=action-scheduler).
		$current_url = self::renderable_url( (string) ( $tree[ $current ]['url'] ?? $current ) );
		add_filter(
			'submenu_file',
			static fn(): string => $current_url,
			PHP_INT_MAX
		);

		$this->mark_root_current( $root );

		// WP's _wp_menu_output() marks any top-level item whose $item[2]
		// matches $plugin_page as `current`, independently of parent_file.
		// $plugin_page comes from $_GET['page'] (e.g. 'wc-settings' when
		// visiting the Payments tab) and matches the Settings rail-root slug,
		// causing Settings to appear active alongside Payments.
		//
		// Override $plugin_page to the rail-root slug so WP's check marks
		// the correct item. Must run on admin_head (after WP's own access
		// check which also uses $plugin_page) but before _wp_menu_output()
		// which is called from menu-header.php included during body output.
		add_action(
			'admin_head',
			static function () use ( $root ): void {
				global $plugin_page;
				if ( isset( $plugin_page ) && $plugin_page !== $root ) {
					$plugin_page = $root;
				}
			},
			0
		);
	}

	/**
	 * Add `wp-has-current-submenu wp-menu-open` to the active rail-root's
	 * $menu entry so its submenu renders inline (in-rail expansion).
	 *
	 * Normally WP adds these classes in `_wp_menu_output()` when
	 * `$parent_file === $item[2]`. We can't rely on that path: for
	 * descendant pages (`wc-status`, `action-scheduler`, `wcdn_page`...)
	 * `get_admin_page_parent()` runs after our `parent_file` filter and
	 * resets `$parent_file` — either via the inline `$_wp_real_parent_file`
	 * remap (`wc-settings` → `woocommerce`) when iterating $submenu, or via
	 * the original Tools-side registration (`tools.php` for action-scheduler).
	 * Neither matches the rail-root's $item[2], so the classes never get
	 * added. Setting them directly via $entry[4] sidesteps the comparison.
	 *
	 * WP will also append `wp-not-current-submenu` (its else branch), so the
	 * LI ends up with both — that's fine: `.wp-has-current-submenu .wp-submenu`
	 * (position: relative, top: auto) wins on cascade order over
	 * `.wp-not-current-submenu .wp-submenu` (which only sets border/width).
	 *
	 * @param string $root Active rail-root slug.
	 */
	private function mark_root_current( string $root ): void {
		global $menu;
		foreach ( $menu as $key => $entry ) {
			if ( ! isset( $entry[2] ) || $entry[2] !== $root ) {
				continue;
			}
			$existing = isset( $entry[4] ) ? (string) $entry[4] : '';
			// `wc-nav-v2-current-root` is a stable marker class that
			// survives wc-admin's `wpNavMenuClassChange` (controller.js)
			// — that function strips `wp-has-current-submenu`/`wp-menu-open`
			// on every wc-admin route change. Our JS wrapper looks up
			// `.wc-nav-v2-current-root` after the controller runs and
			// re-applies the active classes there.
			$menu[ $key ][4] = trim( $existing . ' wp-has-current-submenu wp-menu-open wc-nav-v2-current-root' );
		}
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
		// A visited set keeps the walk terminating if an extension introduced a
		// parent cycle via the woocommerce_admin_menu_tree filter.
		$seen = array();
		while ( isset( $tree[ $walk ] ) && ! isset( $seen[ $walk ] ) ) {
			$seen[ $walk ] = true;
			$parent        = $tree[ $walk ]['parent'] ?? null;
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

	/**
	 * For each Woo tree root, write `$submenu[$root_slug]` with that root's
	 * first-level children (grandchildren stay tree-only — the JS cascade
	 * picks them up at render time).
	 *
	 * Entry shape: `[ title, capability, slug, page_title, classes ]`. We
	 * write `page_title` = title and leave `classes` blank; WP appends to
	 * classes for `current` highlighting at render time.
	 *
	 * @param array $tree Final tree.
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
				$title     = (string) ( $child['title'] ?? $child_slug );
				$cap       = (string) ( $child['capability'] ?? 'read' );
				$url       = self::renderable_url( (string) ( $child['url'] ?? $child_slug ) );
				$entries[] = array( $title, $cap, $url, $title, '' );
			}

			if ( ! empty( $entries ) ) {
				$submenu[ $slug ] = $entries;
			}
		}
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
			$existing        = isset( $entry[4] ) ? (string) $entry[4] : '';
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
				++$key;
			}
			// `menu-top` is the class WP keys positioning + hover styles off;
			// `menu-header.php` does NOT add it automatically. Without it the
			// rail LI loses `position: relative`, so the absolutely-positioned
			// `.wp-submenu` flyout anchors to `#adminmenu` instead of its row.
			//
			// The `toplevel_page_<slug>` class mirrors the hookname/id and
			// reproduces WP's own top-level markup (`add_menu_page` stores
			// `'menu-top ' . $hookname` as the CSS-class field, so native items
			// carry this slug as a class as well as the id). Some extensions —
			// notably marketing channels — locate their menu item by this class
			// in the DOM; preserving it keeps them working once nav-v2 has
			// rebuilt the rail.
			$hookname     = 'toplevel_page_' . self::css_slug( $slug );
			$menu[ $key ] = array(
				$title,
				$cap,
				$slug,
				$title,
				'menu-top wc-nav-v2-item ' . $hookname,
				$hookname,
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

	/**
	 * Output the WP-navigation overlay panel immediately after #adminmenu.
	 *
	 * Renders the original WordPress menu (captured before our mutations) as a
	 * sibling element to #adminmenu. CSS slides it in from off-screen when the
	 * back link is clicked; JS handles the open/close interaction.
	 *
	 * @internal
	 */
	public function print_wp_rail_panel(): void {
		if ( empty( $this->original_menu ) ) {
			return;
		}

		// Sort by position key, then apply WP's menu_order filter so the panel
		// matches the non-Woo page WP rail exactly — our place_woo_after_dashboard
		// hook moves WooCommerce to directly after Dashboard here too.
		ksort( $this->original_menu );

		$raw_slugs = array_values(
			array_filter(
				array_map( static fn( $i ) => (string) ( $i[2] ?? '' ), $this->original_menu ),
				static fn( $s ) => '' !== $s
			)
		);

		// Apply the full menu_order filter chain (Site Kit, place_woo_after_dashboard,
		// any other third-party repositioning filters) so our panel matches the non-Woo
		// WP rail exactly.
		//
		// The problem: strip_phantom_slugs() (hooked at priority 20) reads global $menu
		// to check which slugs are "live". On a Woo page, $menu has been stripped down
		// to WC items only, so strip_phantom_slugs would remove everything else.
		//
		// Fix: temporarily swap global $menu to our original snapshot so strip_phantom_slugs
		// sees all items and keeps them. We restore immediately after the filter runs.
		global $menu;
		$live_menu     = $menu;
		$menu          = $this->original_menu;
		$ordered_slugs = (array) apply_filters( 'menu_order', $raw_slugs );
		$menu          = $live_menu;

		// Computed once: $menu is the restored live menu and is not mutated in the
		// loop below. Slugs still present in the live $menu (index.php, woocommerce)
		// get a custom id prefix; stripped slugs reuse WP's `toplevel_page_<slug>` id
		// so plugin icon CSS still applies.
		$live_slugs = array_column( (array) $menu, 2 );

		// Index by slug for ordered access; first entry wins on slug collision.
		$by_slug = array();
		foreach ( $this->original_menu as $item ) {
			$s = (string) ( $item[2] ?? '' );
			if ( '' !== $s && ! isset( $by_slug[ $s ] ) ) {
				$by_slug[ $s ] = $item;
			}
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div id="wc-nav-v2-wp-rail" aria-hidden="true"><ul>';

		$seen = array();
		foreach ( $ordered_slugs as $slug ) {
			if ( isset( $seen[ $slug ] ) || ! isset( $by_slug[ $slug ] ) ) {
				continue;
			}
			$seen[ $slug ] = true;
			$item          = $by_slug[ $slug ];
			if ( ! isset( $item[2] ) ) {
				continue;
			}

			$css_classes = (string) ( $item[4] ?? '' );

			// Render separators as-is — they carry no title, capability, or slug.
			if ( false !== strpos( $css_classes, 'wp-menu-separator' ) ) {
				echo '<li class="wp-menu-separator" aria-hidden="true"><div></div></li>';
				continue;
			}

			$raw_title = (string) ( $item[0] ?? '' );
			if ( '' === wp_strip_all_tags( $raw_title ) ) {
				continue;
			}
			// hide-if-js  → item must be hidden when JS is present (skip).
			// hide-if-no-js → item is hidden by CSS and only shown by WP's JS
			// for specific conditions (e.g. Links Manager when
			// there are no links stays hidden even with JS).
			// Exclude these so the panel matches the real rail.
			if ( false !== strpos( $css_classes, 'hide-if-js' ) ||
				false !== strpos( $css_classes, 'hide-if-no-js' ) ) {
				continue;
			}

			$slug = (string) $item[2];

			// Skip items that nav-v2 rehomes into the WC rail. They're already
			// accessible inside the WooCommerce flyout; showing them again as
			// separate top-level entries would create confusing duplicates.
			if ( in_array( $slug, Menu_Reconciler::REHOMED_TOP_LEVEL_SLUGS, true ) ) {
				continue;
			}

			// Preserve badge/counter spans so WP's .menu-counter CSS renders
			// the notification bubble; strip everything else.
			$title     = wp_kses( $raw_title, array( 'span' => array( 'class' => true ) ) );
			$url       = self::wp_item_href( $slug );
			$icon_html = self::wp_menu_icon_html( (string) ( $item[6] ?? '' ) );

			$children = array_values(
				array_filter(
					(array) ( $this->original_submenu[ $slug ] ?? array() ),
					static function ( $sub ) {
						if ( false !== strpos( (string) ( $sub[4] ?? '' ), 'hide-if-js' ) ) {
							return false;
						}
						return current_user_can( (string) ( $sub[1] ?? 'read' ) );
					}
				)
			);

			// The WooCommerce item acts as a "back to Woo rail" button — suppress
			// its flyout so clicking it just closes the overlay rather than showing
			// the WC section submenu (which is already accessible in the Woo rail).
			$has_sub = ! empty( $children ) && 'woocommerce' !== $slug;

			// Pass through the menu-icon-* class from $item[4] so CSS-based
			// icons (e.g. built-in WP pages) render with their correct icon
			// rather than falling back to the generic dashicon.
			preg_match( '/\bmenu-icon-[a-z0-9_-]+\b/i', $css_classes, $icon_cls );
			$li_class = 'menu-top'
				. ( ! empty( $icon_cls[0] ) ? ' ' . sanitize_html_class( $icon_cls[0] ) : '' )
				. ( $has_sub ? ' wp-has-submenu wp-not-current-submenu' : '' );
			$a_class  = 'menu-top' . ( $has_sub ? ' wp-has-submenu wp-not-current-submenu' : '' );

			// Use WP's standard `toplevel_page_<slug>` id for items stripped from
			// $menu (so plugin icon CSS applies); items still present in $menu get
			// a custom prefix to avoid duplicate id attributes. $live_slugs is
			// computed once above the loop.
			$css_id = in_array( $slug, $live_slugs, true )
				? 'wc-wp-item-' . self::css_slug( $slug )
				: 'toplevel_page_' . self::css_slug( $slug );

			echo '<li class="' . esc_attr( $li_class ) . '" id="' . esc_attr( $css_id ) . '">';
			echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $a_class ) . '">';
			echo '<div class="wp-menu-arrow"><div></div></div>';
			echo $icon_html;
			echo '<div class="wp-menu-name">' . $title . '</div>';
			echo '</a>';

			if ( $has_sub ) {
				echo '<ul class="wp-submenu wp-submenu-wrap">';
				echo '<li class="wp-submenu-head" aria-hidden="true">' . esc_html( $title ) . '</li>';
				foreach ( $children as $idx => $sub ) {
					$sub_slug  = (string) ( $sub[2] ?? '#' );
					$sub_title = wp_strip_all_tags( (string) ( $sub[0] ?? '' ) );
					$sub_url   = self::wp_item_href( $sub_slug );
					$li_c      = 0 === $idx ? ' class="wp-first-item"' : '';
					$a_c       = 0 === $idx ? ' class="wp-first-item"' : '';
					echo '<li' . $li_c . '><a href="' . esc_url( $sub_url ) . '"' . $a_c . '>' . esc_html( $sub_title ) . '</a></li>';
				}
				echo '</ul>';
			}

			echo '</li>';
		}

		echo '</ul></div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the `<div class="wp-menu-image">` HTML for a menu icon spec.
	 *
	 * Mirrors WP's menu-header.php icon rendering exactly so panel icons match
	 * the real top-level rail.
	 *
	 * @param string $icon Icon value from $menu[i][6].
	 */
	private static function wp_menu_icon_html( string $icon ): string {
		// Mirrors the logic in wp-admin/menu-header.php _wp_menu_output().
		$img       = '';
		$img_class = '';
		$img_style = '';

		if ( '' !== $icon ) {
			if ( 'none' === $icon || 'div' === $icon ) {
				$img = '<br />';
			} elseif ( str_starts_with( $icon, 'data:image/svg+xml;base64,' ) ) {
				$img       = '<br />';
				$img_style = ' style="background-image:url(\'' . esc_attr( $icon ) . '\')"';
				$img_class = ' svg';
			} elseif ( str_starts_with( $icon, 'dashicons-' ) ) {
				$img       = '<br />';
				$img_class = ' dashicons-before ' . sanitize_html_class( $icon );
			} else {
				// URL-based image (could also be a generic data: URI other than SVG).
				$img = '<img src="' . esc_url( $icon ) . '" alt="" />';
			}
		}

		return '<div class="wp-menu-image' . $img_class . '"' . $img_style . ' aria-hidden="true">' . $img . '</div>';
	}

	/**
	 * Derive a renderable href from a raw menu slug.
	 *
	 * Slugs that already contain a `.php` extension or `://` are passed
	 * directly to admin_url(); bare page slugs get `admin.php?page=` prepended.
	 *
	 * @param string $slug Raw value from $menu[i][2] or $submenu[p][i][2].
	 */
	private static function wp_item_href( string $slug ): string {
		if ( '' === $slug || '#' === $slug ) {
			return '#';
		}
		if ( false !== strpos( $slug, '://' ) ) {
			return esc_url_raw( $slug );
		}
		if ( false !== strpos( $slug, '.php' ) || false !== strpos( $slug, '?' ) ) {
			return admin_url( $slug );
		}
		return admin_url( 'admin.php?page=' . $slug );
	}

	/**
	 * Convert a tree slug/url to a form WP's `menu-header.php` will render as
	 * a valid `<a href>` value.
	 *
	 * Compound bare slugs like `wc-settings&tab=tax` carry no registered page
	 * hookname (the tab itself isn't `add_submenu_page()`'d), so WP's URL gen
	 * falls through to its naked-fallback branch which echoes `$sub_item[2]`
	 * verbatim. That branch produces a broken relative `<a href='wc-settings&tab=tax'>`.
	 * The menu_hook branch isn't viable either — it pipes the value through
	 * `add_query_arg()`, which URL-encodes the `&` and corrupts the tab arg.
	 *
	 * The fix: prepend `admin.php?page=` so the naked fallback emits a valid
	 * relative admin URL. Bare plain slugs (`wc-admin`) and full URL overrides
	 * (`edit.php?post_type=product`) are left untouched — both already render
	 * correctly through their respective branches.
	 *
	 * @param string $slug_or_url Tree slug or url override.
	 */
	private static function renderable_url( string $slug_or_url ): string {
		if ( false !== strpos( $slug_or_url, '?' ) ) {
			return $slug_or_url;
		}
		if ( false !== strpos( $slug_or_url, '&' ) ) {
			return 'admin.php?page=' . $slug_or_url;
		}
		return $slug_or_url;
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
}
