<?php
/**
 * MultiCurrencyStorefrontProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects Storefront multi-currency integration metadata without registering hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyStorefrontProjectionService {

	private const THEME_STOREFRONT           = 'storefront';
	private const STYLE_HANDLE               = 'storefront-style';
	private const FILTER_PREFIX              = 'wcpay_multi_currency_';
	private const WIDGET_ID                  = 'woocommerce-payments-multi-currency-storefront-widget';
	private const BREADCRUMB_FILTER          = 'woocommerce_breadcrumb_defaults';
	private const ENQUEUE_ACTION             = 'wp_enqueue_scripts';
	private const BREADCRUMB_FILTER_PRIORITY = 9999;
	private const ENQUEUE_ACTION_PRIORITY    = 50;

	/**
	 * Project whether the theme is Storefront-compatible.
	 *
	 * @param string $stylesheet Active stylesheet.
	 * @param string $template   Active template.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function is_storefront_theme( string $stylesheet, string $template ): bool {
		return self::THEME_STOREFRONT === $stylesheet || self::THEME_STOREFRONT === $template;
	}

	/**
	 * Project Storefront switcher activation blockers.
	 *
	 * @param int                 $enabled_currency_count      Enabled currency count.
	 * @param bool                $storefront_switcher_enabled Whether the saved switcher setting is enabled.
	 * @param array<string,mixed> $simulation_variables        Onboarding simulation variables.
	 * @return string[]
	 *
	 * @since 11.0.0
	 */
	public static function get_activation_blockers(
		int $enabled_currency_count,
		bool $storefront_switcher_enabled,
		array $simulation_variables = array()
	): array {
		$blockers = array();

		if ( 1 >= $enabled_currency_count ) {
			$blockers[] = 'single_currency';
		}

		if ( array_key_exists( 'enable_storefront_switcher', $simulation_variables ) ) {
			if ( ! (bool) $simulation_variables['enable_storefront_switcher'] ) {
				$blockers[] = 'simulation_hides_switcher';
			}

			return $blockers;
		}

		if ( ! $storefront_switcher_enabled ) {
			$blockers[] = 'storefront_switcher_disabled';
		}

		return $blockers;
	}

	/**
	 * Project whether Storefront switcher integration should activate.
	 *
	 * @param int                 $enabled_currency_count      Enabled currency count.
	 * @param bool                $storefront_switcher_enabled Whether the saved switcher setting is enabled.
	 * @param array<string,mixed> $simulation_variables        Onboarding simulation variables.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function should_activate(
		int $enabled_currency_count,
		bool $storefront_switcher_enabled,
		array $simulation_variables = array()
	): bool {
		return array() === self::get_activation_blockers(
			$enabled_currency_count,
			$storefront_switcher_enabled,
			$simulation_variables
		);
	}

	/**
	 * Project Storefront switcher hook/action metadata.
	 *
	 * @param int                 $enabled_currency_count      Enabled currency count.
	 * @param bool                $storefront_switcher_enabled Whether the saved switcher setting is enabled.
	 * @param array<string,mixed> $simulation_variables        Onboarding simulation variables.
	 * @return array<string,mixed>
	 *
	 * @since 11.0.0
	 */
	public static function get_hook_manifest(
		int $enabled_currency_count,
		bool $storefront_switcher_enabled,
		array $simulation_variables = array()
	): array {
		$blockers = self::get_activation_blockers(
			$enabled_currency_count,
			$storefront_switcher_enabled,
			$simulation_variables
		);

		if ( array() !== $blockers ) {
			return array(
				'filters'  => array(),
				'actions'  => array(),
				'blockers' => $blockers,
			);
		}

		return array(
			'filters'  => array(
				array(
					'hook'     => self::BREADCRUMB_FILTER,
					'callback' => 'inject_switcher_into_breadcrumb',
					'priority' => self::BREADCRUMB_FILTER_PRIORITY,
				),
			),
			'actions'  => array(
				array(
					'hook'     => self::ENQUEUE_ACTION,
					'callback' => 'add_inline_css',
					'priority' => self::ENQUEUE_ACTION_PRIORITY,
				),
			),
			'blockers' => array(),
		);
	}

	/**
	 * Project default Storefront switcher widget arguments.
	 *
	 * @return array<string,string>
	 *
	 * @since 11.0.0
	 */
	public static function get_default_widget_args(): array {
		return array(
			'before_widget' => '<div id="' . self::WIDGET_ID . '" class="woocommerce-breadcrumb">',
			'after_widget'  => '</div>',
		);
	}

	/**
	 * Project default Storefront switcher inline CSS.
	 *
	 * @return string
	 *
	 * @since 11.0.0
	 */
	public static function get_default_inline_css(): string {
		return '
			#woocommerce-payments-multi-currency-storefront-widget {
				float: right;
			}
			#woocommerce-payments-multi-currency-storefront-widget form {
				margin: 0;
			}
		';
	}

	/**
	 * Project inline style metadata.
	 *
	 * @param string $css Inline CSS override.
	 * @return array<string,string>
	 *
	 * @since 11.0.0
	 */
	public static function get_inline_style_manifest( string $css = '' ): array {
		return array(
			'handle' => self::STYLE_HANDLE,
			'filter' => self::filter_name( 'storefront_widget_css' ),
			'css'    => '' === $css ? self::get_default_inline_css() : $css,
		);
	}

	/**
	 * Project Storefront switcher widget filter metadata.
	 *
	 * @return array<int,array<string,mixed>>
	 *
	 * @since 11.0.0
	 */
	public static function get_widget_filter_manifest(): array {
		return array(
			array(
				'filter'  => self::filter_name( 'storefront_widget_instance' ),
				'default' => array(),
			),
			array(
				'filter'  => self::filter_name( 'storefront_widget_args' ),
				'default' => self::get_default_widget_args(),
			),
		);
	}

	/**
	 * Project breadcrumb defaults with switcher markup inserted before the nav.
	 *
	 * @param array<string,mixed> $defaults      Breadcrumb defaults.
	 * @param string              $widget_markup Switcher widget markup.
	 * @return array<string,mixed>
	 *
	 * @since 11.0.0
	 */
	public static function inject_switcher_into_breadcrumb( array $defaults, string $widget_markup ): array {
		if ( ! isset( $defaults['wrap_before'] ) || ! is_string( $defaults['wrap_before'] ) ) {
			return $defaults;
		}

		$defaults['wrap_before'] = str_replace(
			'<nav',
			$widget_markup . '<nav',
			$defaults['wrap_before']
		);

		return $defaults;
	}

	/**
	 * Build a multi-currency filter name.
	 *
	 * @param string $suffix Filter suffix.
	 * @return string
	 */
	private static function filter_name( string $suffix ): string {
		return self::FILTER_PREFIX . $suffix;
	}
}
