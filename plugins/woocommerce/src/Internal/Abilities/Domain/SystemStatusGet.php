<?php
/**
 * System status get ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Admin\SiteHealth;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * An allowlisted part of the system status report. Server paths, the database
 * prefix, table names, URLs and remote call results have no field.
 */
class SystemStatusGet extends AbstractDomainAbility implements AbilityDefinition {

	/**
	 * Ability ID, stable once released.
	 *
	 * @return string
	 *
	 * @since 11.4.0
	 */
	public static function get_name(): string {
		return 'woocommerce/system-status-get';
	}

	/**
	 * Read-only, shop-manager-level registration.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Get system status', 'woocommerce' ),
			'description'         => __(
				'Get the WooCommerce and database versions, order storage, installed plugins, enabled WooCommerce features, the theme, the store pages, security checks and post counts. WordPress, PHP and database server versions come from core/get-environment-info.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(),
				'default'              => array(),
				'additionalProperties' => false,
			),
			'output_schema'       => self::get_entity_output_schema( 'system_status', self::get_status_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_read_system_status' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Plugin lists come from the status controller's hourly transients.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function execute(): array {
		$controller = new \WC_REST_System_Status_Controller();
		$dropins_mu = $controller->get_dropins_mu_plugins();
		$security   = $controller->get_security_info();

		return array(
			'system_status' => array(
				'woocommerce_version'    => (string) WC()->version,
				'woocommerce_db_version' => (string) get_option( 'woocommerce_db_version', '' ),
				'order_storage'          => OrderUtil::custom_orders_table_usage_is_enabled() ? 'hpos' : 'posts',
				'active_plugins'         => self::format_plugins( $controller->get_active_plugins() ),
				'inactive_plugins'       => self::format_plugins( $controller->get_inactive_plugins() ),
				'mu_plugins'             => self::format_plugins( $dropins_mu['mu_plugins'] ?? array() ),
				'dropins'                => self::format_dropins( $dropins_mu['dropins'] ?? array() ),
				'enabled_features'       => self::get_enabled_features(),
				'theme'                  => self::get_theme(),
				'pages'                  => self::format_pages( $controller->get_pages() ),
				'security'               => array(
					'secure_connection' => (bool) ( $security['secure_connection'] ?? false ),
					'hide_errors'       => (bool) ( $security['hide_errors'] ?? false ),
				),
				'post_type_counts'       => self::get_post_type_counts( $controller ),
			),
		);
	}

	/**
	 * Same check as the system status REST endpoint.
	 *
	 * @return bool
	 *
	 * @since 11.4.0
	 */
	public static function can_read_system_status(): bool {
		return (bool) wc_rest_check_manager_permissions( 'system_status', 'read' );
	}

	/**
	 * Keeps only name, version and author; the report also carries plugin and author URLs.
	 *
	 * @param mixed $plugins Plugin rows from the status controller.
	 * @return array<int, array<string, string>>
	 */
	private static function format_plugins( $plugins ): array {
		if ( ! is_array( $plugins ) ) {
			return array();
		}

		$items = array();
		foreach ( array_filter( $plugins, 'is_array' ) as $plugin ) {
			$items[] = array(
				'plugin'  => (string) ( $plugin['plugin'] ?? '' ),
				'name'    => (string) ( $plugin['name'] ?? '' ),
				'version' => (string) ( $plugin['version'] ?? '' ),
				'author'  => (string) ( $plugin['author_name'] ?? '' ),
			);
		}
		return $items;
	}

	/**
	 * Drop-ins have no version or author in the report.
	 *
	 * @param mixed $dropins Drop-in rows from the status controller.
	 * @return array<int, array<string, string>>
	 */
	private static function format_dropins( $dropins ): array {
		if ( ! is_array( $dropins ) ) {
			return array();
		}

		$items = array();
		foreach ( array_filter( $dropins, 'is_array' ) as $dropin ) {
			$items[] = array(
				'plugin' => (string) ( $dropin['plugin'] ?? '' ),
				'name'   => (string) ( $dropin['name'] ?? '' ),
			);
		}
		return $items;
	}

	/**
	 * Deprecated features are left out even when their option is still on.
	 *
	 * @return string[]
	 */
	private static function get_enabled_features(): array {
		$features = array();
		foreach ( FeaturesUtil::get_features( true, true ) as $id => $feature ) {
			if ( ! empty( $feature['is_enabled'] ) && empty( $feature['deprecated_since'] ) ) {
				$features[] = (string) $id;
			}
		}
		sort( $features );
		return $features;
	}

	/**
	 * Read locally: the status controller looks up the latest theme version on WordPress.org.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_theme(): array {
		$theme = wp_get_theme();
		$info  = array(
			'name'                    => (string) $theme->get( 'Name' ),
			'version'                 => (string) $theme->get( 'Version' ),
			'is_block_theme'          => $theme->is_block_theme(),
			'has_woocommerce_support' => current_theme_supports( 'woocommerce' ),
			'has_outdated_templates'  => SiteHealth::get_instance()->has_outdated_template_overrides(),
		);

		$parent = $theme->parent();
		if ( $parent ) {
			$info['parent_name']    = (string) $parent->get( 'Name' );
			$info['parent_version'] = (string) $parent->get( 'Version' );
		}

		return $info;
	}

	/**
	 * Leaves out page IDs and the expected shortcode or block of each page.
	 *
	 * @param mixed $pages Page rows from the status controller.
	 * @return array<int, array<string, mixed>>
	 */
	private static function format_pages( $pages ): array {
		if ( ! is_array( $pages ) ) {
			return array();
		}

		$items = array();
		foreach ( array_filter( $pages, 'is_array' ) as $page ) {
			$items[] = array(
				'name'    => (string) ( $page['page_name'] ?? '' ),
				'set'     => (bool) ( $page['page_set'] ?? false ),
				'exists'  => (bool) ( $page['page_exists'] ?? false ),
				'visible' => (bool) ( $page['page_visible'] ?? false ),
			);
		}
		return $items;
	}

	/**
	 * An object, so an empty list still encodes as a JSON object.
	 *
	 * @param \WC_REST_System_Status_Controller $controller Status controller.
	 * @return object
	 */
	private static function get_post_type_counts( \WC_REST_System_Status_Controller $controller ): object {
		$counts = array();
		foreach ( $controller->get_post_type_counts() as $row ) {
			$counts[ (string) $row->type ] = (int) $row->count;
		}
		return (object) $counts;
	}

	/**
	 * Closed at every level, so no report field can reach the output unlisted.
	 *
	 * @return array
	 */
	private static function get_status_schema(): array {
		$string = array( 'type' => 'string' );
		$plugin = array(
			'type'                 => 'object',
			'properties'           => array(
				'plugin'  => array(
					'type'        => 'string',
					'description' => __( 'Plugin file, relative to the plugins directory.', 'woocommerce' ),
				),
				'name'    => $string,
				'version' => $string,
				'author'  => $string,
			),
			'additionalProperties' => false,
		);

		return array(
			'type'                 => 'object',
			'properties'           => array(
				'woocommerce_version'    => $string,
				'woocommerce_db_version' => $string,
				'order_storage'          => array(
					'type' => 'string',
					'enum' => array( 'hpos', 'posts' ),
				),
				'active_plugins'         => array(
					'type'  => 'array',
					'items' => $plugin,
				),
				'inactive_plugins'       => array(
					'type'  => 'array',
					'items' => $plugin,
				),
				'mu_plugins'             => array(
					'type'  => 'array',
					'items' => $plugin,
				),
				'dropins'                => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'plugin' => $string,
							'name'   => $string,
						),
						'additionalProperties' => false,
					),
				),
				'enabled_features'       => array(
					'type'  => 'array',
					'items' => $string,
				),
				'theme'                  => array(
					'type'                 => 'object',
					'properties'           => array(
						'name'                    => $string,
						'version'                 => $string,
						'is_block_theme'          => array( 'type' => 'boolean' ),
						'has_woocommerce_support' => array( 'type' => 'boolean' ),
						'has_outdated_templates'  => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the theme overrides a WooCommerce template with an older version of it.', 'woocommerce' ),
						),
						'parent_name'             => array(
							'type'        => 'string',
							'description' => __( 'Only for a child theme.', 'woocommerce' ),
						),
						'parent_version'          => array(
							'type'        => 'string',
							'description' => __( 'Only for a child theme.', 'woocommerce' ),
						),
					),
					'additionalProperties' => false,
				),
				'pages'                  => array(
					'type'        => 'array',
					'description' => __( 'The WooCommerce pages: whether each is assigned in settings, exists, and is published.', 'woocommerce' ),
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'name'    => $string,
							'set'     => array( 'type' => 'boolean' ),
							'exists'  => array( 'type' => 'boolean' ),
							'visible' => array( 'type' => 'boolean' ),
						),
						'additionalProperties' => false,
					),
				),
				'security'               => array(
					'type'                 => 'object',
					'properties'           => array(
						'secure_connection' => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the shop page URL uses HTTPS.', 'woocommerce' ),
						),
						'hide_errors'       => array(
							'type'        => 'boolean',
							'description' => __( 'Whether PHP errors are hidden from visitors.', 'woocommerce' ),
						),
					),
					'additionalProperties' => false,
				),
				'post_type_counts'       => array(
					'type'                 => 'object',
					'description'          => __( 'Number of posts of each post type, in every status.', 'woocommerce' ),
					'additionalProperties' => array( 'type' => 'integer' ),
				),
			),
			'additionalProperties' => false,
		);
	}
}
