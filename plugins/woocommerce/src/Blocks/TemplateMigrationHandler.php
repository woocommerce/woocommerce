<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * Template Migration Handler
 * 
 * Handles template preservation during WooCommerce plugin updates by saving 
 * existing templates as customizations when templates change.
 */
class TemplateMigrationHandler {

	/**
	 * Plugin slug for WooCommerce
	 */
	const PLUGIN_SLUG = 'woocommerce/woocommerce';

	/**
	 * Transient key for storing pre-upgrade template content
	 */
	const PRE_UPGRADE_TRANSIENT = 'wc_templates_pre_upgrade';

	/**
	 * Initialize the migration handler by registering hooks
	 */
	public function init() {
		// Only register hooks in appropriate contexts
		if ( ! is_admin() && ! wp_doing_ajax() && ! defined( 'WP_CLI' ) ) {
			return;
		}

		add_action( 'upgrader_pre_install', array( $this, 'capture_current_templates' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'handle_template_migration' ), 10, 2 );
	}

	/**
	 * Capture current template contents before upgrade
	 *
	 * @param bool|WP_Error $response Installation response
	 * @param array         $args Extra arguments passed to hooked filters
	 * @return bool|WP_Error Unchanged response
	 */
	public function capture_current_templates( $response, $args ) {
		if ( ! $this->is_woocommerce_update( $args ) ) {
			return $response;
		}

		$current_templates = $this->get_all_template_contents();
		set_transient( self::PRE_UPGRADE_TRANSIENT, $current_templates, HOUR_IN_SECONDS );

		return $response;
	}

	/**
	 * Handle plugin upgrade completion and migrate changed templates
	 *
	 * @param WP_Upgrader $upgrader_object WP_Upgrader instance
	 * @param array       $hook_extra      Array of update data
	 */
	public function handle_template_migration( $upgrader_object, $hook_extra ) {
		if ( ! $this->is_woocommerce_update( $hook_extra ) ) {
			return;
		}

		$old_templates = get_transient( self::PRE_UPGRADE_TRANSIENT );
		if ( ! $old_templates ) {
			return;
		}

		$changed_default_templates = $this->get_changed_default_templates( $old_templates );

		foreach ( $changed_default_templates as $slug ) {
			if ( isset( $old_templates[ $slug ] ) ) {
				$old_content = $old_templates[ $slug ];
				$result = BlockTemplateUtils::save_template_as_customization( $slug, $old_content );
				
				if ( is_wp_error( $result ) ) {
					// TODO: How should we handle this error?
					error_log( "Failed to migrate template '{$slug}': " . $result->get_error_message() );
				}
			}
		}

		delete_transient( self::PRE_UPGRADE_TRANSIENT );
	}

	/**
	 * Check if this is a WooCommerce plugin update
	 * 
	 * Updates can be both single and bulk, this function checks for both.
	 *
	 * @param array $options Update options
	 * @return bool
	 */
	private function is_woocommerce_update( $options ) {
		if ( isset( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
			return in_array( self::PLUGIN_SLUG, $options['plugins'], true );
		}

		if ( isset( $options['plugin'] ) ) {
			return self::PLUGIN_SLUG === $options['plugin'];
		}

		return false;
	}

	/**
	 * Get all WooCommerce template contents
	 *
	 * @return array Template slug => content mapping
	 */
	private function get_all_template_contents() {
		$template_contents = array();
		$template_paths = $this->get_template_paths();

		foreach ( $template_paths as $slug => $path ) {
			$content = $this->get_template_content( $path );
			if ( $content !== false ) {
				$template_contents[ $slug ] = $content;
			}
		}

		return $template_contents;
	}

	/**
	 * Compare old and new templates to find changed template slugs
	 *
	 * @param array $old_templates Old template contents
	 * @param array $new_templates New template contents
	 * @return array Array of changed template slugs
	 */
	private function get_changed_template_slugs( $old_templates, $new_templates ) {
		$changed_slugs = array();

		foreach ( $new_templates as $slug => $new_content ) {
			if ( isset( $old_templates[ $slug ] ) ) {
				$old_content = $old_templates[ $slug ];

				if ( BlockTemplateUtils::templates_are_different( $old_content, $new_content ) ) {
					$changed_slugs[] = $slug;
				}
			}
		}

		return $changed_slugs;
	}

	
	/**
	 * Get templates that have changed but haven't been customized by the user
	 *
	 * Compares old and new template contents to find templates that have changed,
	 * then filters out any that have already been customized by the user.
	 *
	 * @param array $old_templates Old template contents
	 * @param array $new_templates New template contents
	 * @return array|void Array of template slugs that changed but weren't customized, void if no changes
	 */
	private function get_changed_default_templates( $old_templates ) {
		$new_templates = $this->get_all_template_contents();
		$changed_slugs = $this->get_changed_template_slugs( $old_templates, $new_templates );

		if ( empty( $changed_slugs ) ) {
			// No templates were changed
			delete_transient( self::PRE_UPGRADE_TRANSIENT );
			return;
		}

		$customized_templates = BlockTemplateUtils::get_block_templates_from_db( $changed_slugs );
		$customized_slugs = array_map( 
			function( $template ) { 
				return $template->slug; 
			}, 
			array_filter( $customized_templates, function( $template ) {
				return 'custom' === $template->source;
			})
		);

		return array_diff( $changed_slugs, $customized_slugs );
	}

	/**
	 * Get template content from file
	 *
	 * @param string $template_path Template file path
	 * @return string|false
	 */
	private function get_template_content( $template_path ) {
		if ( ! file_exists( $template_path ) ) {
			return false;
		}

		return file_get_contents( $template_path );
	}

	/**
	 * Get all WooCommerce template paths
	 *
	 * @return array Template slug => file path mapping
	 */
	private function get_template_paths() {
		$template_paths = BlockTemplateUtils::get_template_paths( 'wp_template' );
		$template_part_paths = BlockTemplateUtils::get_template_paths( 'wp_template_part' );
		
		$all_paths = array_merge( $template_paths, $template_part_paths );

		$slugs_to_paths = array();
		foreach ( $all_paths as $path ) {
			$slug = BlockTemplateUtils::generate_template_slug_from_path( $path );
			$slugs_to_paths[ $slug ] = $path;
		}
		
		return $slugs_to_paths;
	}
}