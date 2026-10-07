<?php
/**
 * Extensible ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * An ability whose output carries the extension fields it declares in
 * `meta.woocommerce.extension_fields`. From WordPress 7.1, the values come
 * from the `wp_ability_execute_result` filter, as for any ability that opts
 * in. Before 7.1, this class adds them itself.
 *
 * @since 11.3.0
 */
class ExtensibleAbility extends \WP_Ability {

	/**
	 * Whether WordPress fires `wp_ability_execute_result`, added in 7.1.
	 */
	public static function wordpress_fills_output(): bool {
		return version_compare( get_bloginfo( 'version' ), '7.1-alpha', '>=' );
	}

	/**
	 * Run the execute callback, then add the extension field values before WordPress 7.1.
	 *
	 * @param mixed $input Input.
	 * @return mixed
	 */
	protected function do_execute( $input = null ) {
		$result = parent::do_execute( $input );
		if ( self::wordpress_fills_output() ) {
			return $result;
		}
		// Remove once WooCommerce requires WordPress 7.1.
		return AbilityFields::fill_result( $result, $this );
	}
}
