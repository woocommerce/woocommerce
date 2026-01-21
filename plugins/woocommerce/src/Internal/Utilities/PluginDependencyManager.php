<?php
/**
 * Manages plugin dependency checks for WooCommerce deletion.
 *
 * This class modifies WordPress's default plugin dependency behavior to allow deletion
 * of WooCommerce when all dependent plugins are inactive, even if they're still installed.
 *
 * @package WooCommerce\Internal\Utilities
 */

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Plugin Dependency Manager class.
 *
 * @since 9.7.0
 */
class PluginDependencyManager implements RegisterHooksInterface {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_filter( 'plugin_action_links_' . WC_PLUGIN_BASENAME, array( $this, 'filter_plugin_action_links' ), 20 );
	}

	/**
	 * Filter plugin action links to enable deletion when all dependent plugins are inactive.
	 *
	 * @param array $actions Array of plugin action links.
	 * @return array Modified array of plugin action links.
	 */
	public function filter_plugin_action_links( $actions ) {
		// Only modify on the plugins page.
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return $actions;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return $actions;
		}

		// If WooCommerce is active, don't modify.
		if ( is_plugin_active( WC_PLUGIN_BASENAME ) ) {
			return $actions;
		}

		// Check if we have dependent plugins.
		$dependent_plugins = $this->get_dependent_plugins();
		if ( empty( $dependent_plugins ) ) {
			return $actions;
		}

		// Check if all dependent plugins are inactive.
		$all_inactive = $this->are_all_dependent_plugins_inactive( $dependent_plugins );
		
		// If all dependent plugins are inactive, we'll allow deletion via JavaScript
		// (we can't directly modify the action links here due to WordPress core restrictions).
		return $actions;
	}

	/**
	 * Enqueue admin scripts to modify plugin deletion behavior.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		// Only load on the plugins page.
		if ( 'plugins.php' !== $hook_suffix ) {
			return;
		}

		// Check if WooCommerce is active.
		if ( is_plugin_active( WC_PLUGIN_BASENAME ) ) {
			return;
		}

		// Get dependent plugins.
		$dependent_plugins = $this->get_dependent_plugins();
		if ( empty( $dependent_plugins ) ) {
			return;
		}

		// Check if all dependent plugins are inactive.
		$all_inactive = $this->are_all_dependent_plugins_inactive( $dependent_plugins );
		
		if ( ! $all_inactive ) {
			return;
		}

		// Enqueue inline script to enable deletion.
		// Use 'common' which is always loaded on admin pages.
		wp_enqueue_script( 'jquery' );
		wp_add_inline_script(
			'common',
			$this->get_inline_script(),
			'after'
		);
	}

	/**
	 * Get inline JavaScript to enable WooCommerce deletion when all dependents are inactive.
	 *
	 * @return string JavaScript code.
	 */
	private function get_inline_script() {
		$wc_basename = WC_PLUGIN_BASENAME;
		
		return "
		(function($) {
			$(document).ready(function() {
				// Find the WooCommerce plugin row.
				var wcRow = $('tr[data-plugin=\"{$wc_basename}\"]');
				if (wcRow.length === 0) {
					return;
				}
				
				// Enable the delete link.
				var deleteLink = wcRow.find('.delete a');
				if (deleteLink.length > 0) {
					// Remove the disabled state if present.
					deleteLink.removeClass('disabled');
					deleteLink.css('pointer-events', 'auto');
					deleteLink.css('opacity', '1');
				}
				
				// Remove any notice about required plugins if all are inactive.
				var notice = wcRow.next('tr.plugin-update-tr').find('.notice');
				if (notice.length > 0) {
					var noticeText = notice.text();
					if (noticeText.indexOf('cannot be deactivated or deleted') !== -1) {
						// Add clarification that dependent plugins are inactive.
						var newNotice = $('<div class=\"update-message notice inline notice-info notice-alt\"><p></p></div>');
						var activeText = '" . esc_js( __( 'Note: All plugins that depend on WooCommerce are currently inactive. You can safely delete WooCommerce.', 'woocommerce' ) ) . "';
						newNotice.find('p').html(activeText);
						notice.replaceWith(newNotice);
					}
				}
			});
		})(jQuery);
		";
	}

	/**
	 * Get list of plugins that depend on WooCommerce.
	 *
	 * @return array Array of plugin basenames that require WooCommerce.
	 */
	private function get_dependent_plugins() {
		// WordPress's wp_get_plugin_dependencies() is only available in WP 6.5+.
		if ( ! function_exists( 'wp_get_plugin_dependencies' ) ) {
			return array();
		}

		$wc_slug = dirname( WC_PLUGIN_BASENAME );
		
		// Get all plugins that depend on WooCommerce.
		$all_dependencies = wp_get_plugin_dependencies();
		$dependent_plugins = array();
		
		foreach ( $all_dependencies as $plugin => $dependencies ) {
			if ( in_array( $wc_slug, $dependencies, true ) ) {
				$dependent_plugins[] = $plugin;
			}
		}
		
		return $dependent_plugins;
	}

	/**
	 * Check if all dependent plugins are inactive.
	 *
	 * @param array $dependent_plugins Array of plugin basenames.
	 * @return bool True if all dependent plugins are inactive, false otherwise.
	 */
	private function are_all_dependent_plugins_inactive( $dependent_plugins ) {
		foreach ( $dependent_plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				return false;
			}
		}
		return true;
	}
}
