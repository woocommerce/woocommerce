<?php
/**
 * Polyfilled ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * An ability that fires the hooks WP_Ability fires from WordPress 7.1, in the
 * same order and with the same arguments, on earlier versions. From 7.1 it
 * behaves exactly like WP_Ability.
 *
 * Experimental: a subclass can implement do_dry_run() to say what execute
 * would do without doing it. dry_run() checks the input and permissions first.
 *
 * @since 11.3.0
 */
class PolyfilledAbility extends \WP_Ability {

	/**
	 * Whether WordPress is older than 7.1 and the hooks need a polyfill.
	 */
	public static function is_active(): bool {
		return version_compare( get_bloginfo( 'version' ), '7.1-alpha', '<' );
	}

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
	 * Normalize the input, then filter it.
	 *
	 * @param mixed $input Raw input.
	 * @return mixed
	 */
	public function normalize_input( $input = null ) {
		$input = parent::normalize_input( $input );
		if ( ! self::is_active() ) {
			return $input;
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress 7.1 hook.
		return apply_filters( 'wp_ability_normalize_input', $input, $this->get_name(), $this );
	}

	/**
	 * Validate the input, then filter the result.
	 *
	 * @param mixed $input Input.
	 * @return true|\WP_Error
	 */
	public function validate_input( $input = null ) {
		$is_valid = parent::validate_input( $input );
		if ( ! self::is_active() || empty( $this->get_input_schema() ) ) {
			return $is_valid;
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress 7.1 hook.
		$validity = apply_filters( 'wp_ability_validate_input', $is_valid, $input, $this->get_name() );
		if ( false === $validity ) {
			return new \WP_Error( 'ability_invalid_input', __( 'Invalid input.', 'woocommerce' ) );
		}
		if ( is_wp_error( $validity ) && $validity->has_errors() ) {
			return $validity;
		}
		return true;
	}

	/**
	 * Check permissions, then filter the result.
	 *
	 * @param mixed $input Input.
	 * @return bool|\WP_Error
	 */
	public function check_permissions( $input = null ) {
		$permission = parent::check_permissions( $input );
		if ( ! self::is_active() ) {
			return $permission;
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress 7.1 hook.
		$result = apply_filters( 'wp_ability_permission_result', $permission, $this->get_name(), $input, $this );
		return is_bool( $result ) || is_wp_error( $result ) ? $result : false;
	}

	/**
	 * Run the execute callback, then filter the result.
	 *
	 * @param mixed $input Input.
	 * @return mixed
	 */
	protected function do_execute( $input = null ) {
		$result = parent::do_execute( $input );
		if ( ! self::is_active() ) {
			return $result;
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress 7.1 hook.
		return apply_filters( 'wp_ability_execute_result', $result, $this->get_name(), $input, $this );
	}

	/**
	 * Validate the output, then filter the result.
	 *
	 * @param mixed $output Output.
	 * @return true|\WP_Error
	 */
	protected function validate_output( $output ) {
		$is_valid = parent::validate_output( $output );
		if ( ! self::is_active() ) {
			return $is_valid;
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress 7.1 hook.
		$validity = apply_filters( 'wp_ability_validate_output', $is_valid, $output, $this->get_name() );
		if ( false === $validity ) {
			return new \WP_Error( 'ability_invalid_output', __( 'Invalid output.', 'woocommerce' ) );
		}
		if ( is_wp_error( $validity ) && $validity->has_errors() ) {
			return $validity;
		}
		return true;
	}

	/**
	 * Execute the ability as WordPress 7.1 does.
	 *
	 * @param mixed $input Input.
	 * @return mixed|\WP_Error
	 */
	public function execute( $input = null ) {
		if ( ! self::is_active() ) {
			return parent::execute( $input );
		}

		$name = $this->get_name();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress 7.1 hook.
		do_action( 'wp_ability_invoked', $name, $input, $this );

		$sentinel = new \stdClass();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress 7.1 hook.
		$pre = apply_filters( 'wp_pre_execute_ability', $sentinel, $name, $input, $this );
		if ( $pre !== $sentinel ) {
			return $pre;
		}

		$input = $this->normalize_input( $input );
		if ( is_wp_error( $input ) ) {
			return $input;
		}

		$is_valid = $this->validate_input( $input );
		if ( is_wp_error( $is_valid ) ) {
			return $is_valid;
		}

		$has_permissions = $this->check_permissions( $input );
		if ( true !== $has_permissions ) {
			if ( is_wp_error( $has_permissions ) ) {
				_doing_it_wrong( __METHOD__, esc_html( $has_permissions->get_error_message() ), '6.9.0' );
			}

			return new \WP_Error(
				'ability_invalid_permissions',
				/* translators: %s ability name. */
				sprintf( __( 'Ability "%s" does not have necessary permission.', 'woocommerce' ), $name )
			);
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress hook.
		do_action( 'wp_before_execute_ability', $name, $input, $this );

		$result = $this->do_execute( $input );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$is_valid = $this->validate_output( $result );
		if ( is_wp_error( $is_valid ) ) {
			return $is_valid;
		}

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WordPress hook.
		do_action( 'wp_after_execute_ability', $name, $input, $result, $this );

		return $result;
	}
}
