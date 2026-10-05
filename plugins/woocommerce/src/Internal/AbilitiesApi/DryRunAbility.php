<?php
/**
 * Dry run ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * The base class for WooCommerce's ability contracts. Before WordPress 7.1, it
 * adds the extension field values an ability declares in
 * `meta.woocommerce.extension_fields` to its output. Experimental: a subclass can
 * implement do_dry_run() to say what execute would do without doing it.
 * dry_run() checks the input and permissions first.
 *
 * @since 11.3.0
 */
class DryRunAbility extends \WP_Ability {

	/**
	 * Whether a class implements its own dry run.
	 *
	 * @param string $class_name Ability class.
	 */
	public static function has_dry_run( string $class_name ): bool {
		return is_a( $class_name, self::class, true ) && self::class !== ( new \ReflectionMethod( $class_name, 'do_dry_run' ) )->getDeclaringClass()->getName();
	}

	/**
	 * What execute would do, without doing it: the ability, object_type,
	 * object_id, object_label, changes, side_effects and undo. It checks the
	 * input and permissions the same way execute does.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error The summary, or the error execute would return.
	 */
	public function dry_run( array $input ) {
		if ( ! self::has_dry_run( static::class ) ) {
			return new \WP_Error(
				'ability_dry_run_unsupported',
				/* translators: %s ability name. */
				sprintf( __( 'Ability "%s" does not support a dry run.', 'woocommerce' ), $this->get_name() )
			);
		}

		$input = $this->normalize_input( $input );
		if ( is_wp_error( $input ) ) {
			return $input;
		}
		$valid = $this->validate_input( $input );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		if ( true !== $this->check_permissions( $input ) ) {
			return new \WP_Error(
				'ability_invalid_permissions',
				/* translators: %s ability name. */
				sprintf( __( 'Ability "%s" does not have necessary permission.', 'woocommerce' ), $this->get_name() )
			);
		}

		return $this->do_dry_run( is_array( $input ) ? $input : array() );
	}

	/**
	 * The dry run a subclass implements. Never saves, sends email or makes HTTP requests.
	 *
	 * @param array $input Valid input the caller may use.
	 * @return array|\WP_Error
	 */
	protected function do_dry_run( array $input ) {
		return new \WP_Error( 'ability_dry_run_unsupported', __( 'This ability does not support a dry run.', 'woocommerce' ) );
	}

	/**
	 * Run the execute callback. From WordPress 7.1, extension values come from
	 * wp_ability_execute_result, as for any ability that opts in. Before 7.1,
	 * this method adds them. This class fires no WordPress hook itself.
	 *
	 * @param mixed $input Input.
	 * @return mixed
	 */
	protected function do_execute( $input = null ) {
		$result = parent::do_execute( $input );
		if ( RegistrationArgs::execute_result_hook_available() ) {
			return $result;
		}
		// Removed once WooCommerce requires WordPress 7.1.
		return RegistrationArgs::fill_result( $result, $this );
	}
}
