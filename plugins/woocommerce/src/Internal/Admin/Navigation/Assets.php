<?php
/**
 * Asset enqueuing for navigation_v2.
 *
 * Enqueues the SCSS/JS that power the native-flyout cascade and (on Woo
 * pages) the rail replacement. Since the rail replacement re-uses WP's
 * `#adminmenu` element directly, admin-menu.css applies natively — no CSS
 * alias trick required.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

declare( strict_types = 1 );

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
		// Critical badge styles are printed directly into admin_head so they
		// apply even on pages (e.g. MailPoet's landing page) that clear all
		// admin_enqueue_scripts hooks before our stylesheet can load.
		add_action( 'admin_head', array( $this, 'print_critical_css' ) );
		// Early cascade injection: runs synchronously right after WP outputs
		// #adminmenu HTML. This injects the nested <ul> elements before footer
		// JS loads, so CSS :hover can show cascades during page load. The
		// footer JS is idempotent and skips items already injected.
		add_action( 'adminmenu', array( $this, 'print_early_cascade_script' ) );
		add_filter( 'admin_body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * Add `wc-nav-v2-active` to the body on Woo pages so CSS/JS can key off it.
	 *
	 * @internal
	 *
	 * @param string $classes Existing classes.
	 * @return string
	 */
	public function add_body_class( string $classes ): string {
		$tree = Menu_Reconciler::get_tree();
		if ( null !== $tree && Context::is_woo_page( $tree ) ) {
			$classes .= ' wc-nav-v2-active';
		}
		return $classes;
	}

	/**
	 * Print critical attention-dot CSS inline.
	 *
	 * Only runs when our stylesheet hasn't already been queued (i.e. when
	 * admin_enqueue_scripts was cleared before we could enqueue).
	 *
	 * @internal
	 */
	public function print_critical_css(): void {
		if ( wp_style_is( self::STYLE_HANDLE, 'done' ) ) {
			return;
		}
		echo '<style id="wc-nav-v2-critical">'
			. '#adminmenu .wc-attention-dot{width:8px!important;min-width:8px!important;height:8px!important;padding:0!important;margin:0 0 0 2px!important;border-radius:50%!important;vertical-align:middle!important}'
			. '#adminmenu li#toplevel_page_woocommerce>a .wp-menu-name,'
			. '#adminmenu li#toplevel_page_wc-orders>a .wp-menu-name{white-space:nowrap;word-break:keep-all;overflow-wrap:normal}'
			. '</style>';
	}

	/**
	 * Inject cascade <ul> elements synchronously after #adminmenu renders.
	 *
	 * Vanilla JS (no jQuery) so it runs immediately. The footer script's
	 * injectNativeCascade() is idempotent and will skip items already injected.
	 *
	 * Kept behaviorally in sync with injectNativeCascade() in
	 * client/legacy/js/admin/admin-navigation-v2.js and the slug regex in
	 * Native_Rail_Splicer::css_slug(): the `li.menu-top` selector, the
	 * `wc-nav-v2-subflyout` markup, the `/[^A-Za-z0-9_-]/` slug regex, and the
	 * `toplevel_page_` id keying must match across all three sites.
	 *
	 * @internal
	 */
	public function print_early_cascade_script(): void {
		$tree = Menu_Reconciler::get_tree();
		if ( empty( $tree ) ) {
			return;
		}
		$config = wp_json_encode(
			array(
				'tree'     => $tree,
				'adminUrl' => admin_url(),
			),
			JSON_HEX_TAG | JSON_HEX_AMP
		);
		if ( ! $config ) {
			return;
		}
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<script id="wc-nav-v2-early-cascade">(function(){';
		echo 'var C=' . $config . ';';
		echo <<<'JS'
if(!C||!C.tree)return;
var T=C.tree,base=(C.adminUrl||'/wp-admin/');
if(base.charAt(base.length-1)!=='/'){base+='/';}
function au(t){if(!t)return'#';return t.indexOf('?')>=0?base+t:base+'admin.php?page='+t;}
function cu(h){return h?h.replace(/^https?:\/\/[^/]+/,'').replace(/^\/+wp-admin\//,'').replace(/&#038;/g,'&').replace(/&amp;/g,'&'):'';}
var bp={};
Object.keys(T).forEach(function(s){var p=T[s].parent;if(!p)return;bp[p]=bp[p]||[];bp[p].push(Object.assign({},T[s],{slug:s}));});
Object.keys(bp).forEach(function(p){bp[p].sort(function(a,b){return(a.position||0)-(b.position||0);});});
var us={};
Object.keys(T).forEach(function(s){var key=cu(au(T[s].url||s));var ex=us[key];var tk=(bp[s]||[]).length>0;var pk=ex&&(bp[ex]||[]).length>0;if(!ex||(tk&&!pk)){us[key]=s;}});
var ri={};
Object.keys(T).forEach(function(s){if(T[s].parent!=='woocommerce')return;ri['toplevel_page_'+s.replace(/[^A-Za-z0-9_-]/g,'-')]=s;});
var am=document.getElementById('adminmenu');if(!am)return;
var lis=am.querySelectorAll('li.menu-top[id^="toplevel_page_"] > .wp-submenu > li');
for(var i=0;i<lis.length;i++){
var li=lis[i];
if(li.classList.contains('wp-submenu-head')||li.querySelector('.wc-nav-v2-subflyout'))continue;
var a=li.querySelector('a');if(!a)continue;
var ts=us[cu(a.getAttribute('href')||'')];if(!ts)continue;
var rl=li.closest('li.menu-top');var rid=rl?(rl.getAttribute('id')||''):'';var rs=ri[rid];
if(rs&&ts===rs)continue;
var gk=bp[ts];if(!gk||!gk.length)continue;
li.classList.add('wc-nav-v2-has-subflyout');
var ul=document.createElement('ul');ul.className='wp-submenu wc-nav-v2-subflyout';
gk.forEach(function(k){if(k.hidden)return;var kl=document.createElement('li');var ka=document.createElement('a');ka.href=au(k.url||k.slug);ka.textContent=k.title;kl.appendChild(ka);ul.appendChild(kl);});
li.appendChild(ul);}
JS;
		echo '})();</script>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Enqueue.
	 *
	 * @internal
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

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			WC()->plugin_url() . '/assets/js/admin/admin-navigation-v2.js',
			// `common` declared as a dep so WP's hoverIntent binding on
			// `li.wp-has-submenu` runs before our DOM-ready handler — our
			// injectNativeCascade() unbinds those hover handlers on the
			// WooCommerce top-level item so our longer close delay can win.
			array( 'jquery', 'common' ),
			$version,
			true
		);

		// Expose the computed tree and current-page flag to JS.
		$tree = Menu_Reconciler::get_tree() ?? array();

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wcNavV2Config',
			array(
				'isWooPage' => Context::is_woo_page( $tree ) ? '1' : '0',
				'adminUrl'  => admin_url(),
				'tree'      => $tree,
			)
		);
	}
}
