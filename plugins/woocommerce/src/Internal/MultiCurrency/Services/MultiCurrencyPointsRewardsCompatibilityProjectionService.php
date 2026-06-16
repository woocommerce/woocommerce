<?php
/**
 * MultiCurrencyPointsRewardsCompatibilityProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency Points and Rewards compatibility decisions without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyPointsRewardsCompatibilityProjectionService {

	/**
	 * Project the Points and Rewards compatibility hook/filter manifest.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(): array {
		return array(
			'filters' => array(
				self::hook_entry( 'option_wc_points_rewards_earn_points_ratio', 'convert_points_ratio', 50 ),
				self::hook_entry( 'option_wc_points_rewards_redeem_points_ratio', 'convert_points_ratio', 50 ),
			),
			'actions' => array(),
		);
	}

	/**
	 * Tell whether Points and Rewards compatibility hooks should register.
	 *
	 * @param bool $points_rewards_available Whether Points and Rewards runtime is available.
	 * @param bool $is_admin                 Whether this is an admin request.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_register( bool $points_rewards_available, bool $is_admin ): bool {
		return $points_rewards_available && ! $is_admin;
	}

	/**
	 * Tell whether a points ratio should be converted.
	 *
	 * @param bool $selected_and_default_currency_differ Whether selected and default currencies differ.
	 * @param bool $is_discount_data_context             Whether discount data calculation is running.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_convert_ratio( bool $selected_and_default_currency_differ, bool $is_discount_data_context ): bool {
		return $selected_and_default_currency_differ && ! $is_discount_data_context;
	}

	/**
	 * Convert the monetary side of a points ratio by selected currency rate.
	 *
	 * @param string $ratio         Points ratio.
	 * @param float  $selected_rate Selected currency rate.
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function convert_ratio_value( string $ratio, float $selected_rate ): string {
		$parts  = explode( ':', $ratio );
		$points = (float) ( $parts[0] ?? 0 );
		$value  = (float) ( $parts[1] ?? 0 );

		return $points . ':' . ( $value * $selected_rate );
	}

	/**
	 * Build a hook entry.
	 *
	 * @param string $hook          Hook name.
	 * @param string $callback      Callback method.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Accepted args.
	 * @return array<string,mixed>
	 */
	private static function hook_entry( string $hook, string $callback, int $priority = 10, int $accepted_args = 1 ): array {
		return array(
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}
