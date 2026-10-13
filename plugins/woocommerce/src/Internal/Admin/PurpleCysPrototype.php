<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin;

/**
 * Prototype of the Purple theme in the "Customize your store" task.
 *
 * Throwaway code behind the `purple-cys-prototype` feature flag. Adds a "Purple prototype"
 * admin bar menu that stores per-user prototype settings, and passes them to the client.
 *
 * @internal
 */
class PurpleCysPrototype {
	/**
	 * Slug of the Purple theme on WordPress.org.
	 */
	const PURPLE_SLUG = 'purple';

	/**
	 * Theme the "Reset Purple" action switches back to.
	 */
	const RESET_THEME_SLUG = 'twentytwentyfour';

	/**
	 * User meta key for the prototype settings.
	 */
	const USER_META_KEY = 'woocommerce_purple_cys_prototype';

	/**
	 * Nonce action for the admin bar links.
	 */
	const NONCE_ACTION = 'woocommerce_purple_cys_prototype';

	/**
	 * Allowed values for each setting. The first value is the default.
	 *
	 * @var array<string, string[]>
	 */
	const SETTINGS = array(
		'container' => array( 'task-page', 'modal' ),
		'layout'    => array( 'stacked-images-left' ),
		'state'     => array( 'real', 'default', 'installing', 'installed', 'activating', 'active', 'error' ),
	);

	/**
	 * Register hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', array( $this, 'handle_request' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 999 );
		add_action( 'admin_head', array( $this, 'output_admin_bar_css' ) );
		add_action( 'wp_head', array( $this, 'output_admin_bar_css' ) );
		add_filter( 'woocommerce_admin_shared_settings', array( $this, 'add_shared_settings' ) );
	}

	/**
	 * Get the current user's prototype settings, filling in defaults.
	 *
	 * @return array<string, string>
	 */
	private function get_settings(): array {
		$saved    = get_user_meta( get_current_user_id(), self::USER_META_KEY, true );
		$saved    = is_array( $saved ) ? $saved : array();
		$settings = array();

		foreach ( self::SETTINGS as $key => $allowed ) {
			$value            = $saved[ $key ] ?? null;
			$settings[ $key ] = in_array( $value, $allowed, true ) ? $value : $allowed[0];
		}

		return $settings;
	}

	/**
	 * URL of the "Customize your store" screen.
	 *
	 * @param array<string, string> $args Extra query args.
	 * @return string
	 */
	private function get_cys_url( array $args = array() ): string {
		return add_query_arg(
			array_merge(
				array(
					'page' => 'wc-admin',
					'path' => rawurlencode( '/customize-store' ),
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * URL that saves a prototype setting and reloads the CYS screen.
	 *
	 * @param string $key   Setting key, or "reset".
	 * @param string $value Setting value.
	 * @return string
	 */
	private function get_action_url( string $key, string $value = '' ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'purple_cys_set'   => $key,
					'purple_cys_value' => $value,
				),
				$this->get_cys_url()
			),
			self::NONCE_ACTION
		);
	}

	/**
	 * Save a setting or run the reset when an admin bar link is followed.
	 */
	public function handle_request(): void {
		if ( ! isset( $_GET['purple_cys_set'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( self::NONCE_ACTION );

		$key   = sanitize_key( wp_unslash( $_GET['purple_cys_set'] ) );
		$value = isset( $_GET['purple_cys_value'] ) ? sanitize_key( wp_unslash( $_GET['purple_cys_value'] ) ) : '';
		$args  = array();

		if ( 'reset' === $key ) {
			$args['purple_cys_reset'] = $this->reset_purple() ? 'done' : 'failed';
		} elseif ( isset( self::SETTINGS[ $key ] ) && in_array( $value, self::SETTINGS[ $key ], true ) ) {
			$settings         = $this->get_settings();
			$settings[ $key ] = $value;
			update_user_meta( get_current_user_id(), self::USER_META_KEY, $settings );
		}

		wp_safe_redirect( $this->get_cys_url( $args ) );
		exit;
	}

	/**
	 * Switch the site back to Twenty Twenty-Four and delete Purple.
	 *
	 * @return bool Whether the site ended up without Purple.
	 */
	private function reset_purple(): bool {
		if ( ! current_user_can( 'switch_themes' ) || ! current_user_can( 'delete_themes' ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/theme.php';

		if ( ! wp_get_theme( self::RESET_THEME_SLUG )->exists() && current_user_can( 'install_themes' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			$api = themes_api(
				'theme_information',
				array(
					'slug'   => self::RESET_THEME_SLUG,
					'fields' => array( 'sections' => false ),
				)
			);

			if ( is_object( $api ) && ! is_wp_error( $api ) && isset( $api->download_link ) ) {
				$upgrader = new \Theme_Upgrader( new \Automatic_Upgrader_Skin() );
				$upgrader->install( $api->download_link );
			}
		}

		if ( wp_get_theme( self::RESET_THEME_SLUG )->exists() ) {
			switch_theme( self::RESET_THEME_SLUG );
		} elseif ( self::PURPLE_SLUG === get_stylesheet() ) {
			$fallback = \WP_Theme::get_core_default_theme();
			if ( ! $fallback ) {
				return false;
			}
			switch_theme( $fallback->get_stylesheet() );
		}

		if ( wp_get_theme( self::PURPLE_SLUG )->exists() ) {
			$result = delete_theme( self::PURPLE_SLUG );
			if ( true !== $result ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Add the "Purple prototype" menu to the admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function add_admin_bar_menu( $wp_admin_bar ): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings = $this->get_settings();
		$parent   = 'woocommerce-purple-cys-prototype';

		$wp_admin_bar->add_node(
			array(
				'id'    => $parent,
				'title' => __( 'Purple prototype', 'woocommerce' ),
				'href'  => $this->get_cys_url(),
			)
		);

		$groups = array(
			'container' => array(
				'label'   => __( 'Container', 'woocommerce' ),
				'options' => array(
					'task-page' => __( 'Task page', 'woocommerce' ),
					'modal'     => __( 'Modal', 'woocommerce' ),
				),
			),
			'layout'    => array(
				'label'   => __( 'Layout', 'woocommerce' ),
				'options' => array(
					'stacked-images-left' => __( 'Stacked, images left (V3)', 'woocommerce' ),
				),
			),
			'state'     => array(
				'label'   => __( 'Purple state', 'woocommerce' ),
				'options' => array(
					/* translators: %s: the real Purple state on this site, for example "installed, not active". */
					'real'       => sprintf( __( 'Real (%s)', 'woocommerce' ), $this->get_real_state_label() ),
					'default'    => __( 'Default', 'woocommerce' ),
					'installing' => __( 'Installing', 'woocommerce' ),
					'installed'  => __( 'Installed', 'woocommerce' ),
					'activating' => __( 'Activating', 'woocommerce' ),
					'active'     => __( 'Active', 'woocommerce' ),
					'error'      => __( 'Error', 'woocommerce' ),
				),
			),
		);

		foreach ( $groups as $key => $group ) {
			$group_id = $parent . '-' . $key;

			$wp_admin_bar->add_group(
				array(
					'id'     => $group_id,
					'parent' => $parent,
					'meta'   => array( 'class' => 'ab-sub-secondary purple-cys-prototype-group' ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'id'     => $group_id . '-heading',
					'parent' => $group_id,
					'title'  => $group['label'],
					'meta'   => array( 'class' => 'purple-cys-prototype-heading' ),
				)
			);

			foreach ( $group['options'] as $value => $label ) {
				$is_active = $settings[ $key ] === $value;

				$wp_admin_bar->add_node(
					array(
						'id'     => $group_id . '-' . $value,
						'parent' => $group_id,
						'title'  => $label,
						'href'   => $this->get_action_url( $key, $value ),
						'meta'   => array( 'class' => 'purple-cys-prototype-option' . ( $is_active ? ' is-active' : '' ) ),
					)
				);
			}
		}

		$wp_admin_bar->add_group(
			array(
				'id'     => $parent . '-reset-group',
				'parent' => $parent,
				'meta'   => array( 'class' => 'ab-sub-secondary purple-cys-prototype-group' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => $parent . '-reset',
				'parent' => $parent . '-reset-group',
				'title'  => __( 'Reset Purple', 'woocommerce' ),
				'href'   => $this->get_action_url( 'reset' ),
				'meta'   => array(
					'class'   => 'purple-cys-prototype-option',
					'onclick' => sprintf(
						'return window.confirm(%s);',
						wp_json_encode( __( 'Switch the site back to Twenty Twenty-Four and delete the Purple theme?', 'woocommerce' ) )
					),
				),
			)
		);
	}

	/**
	 * Short label for the real Purple state on this site.
	 *
	 * @return string
	 */
	private function get_real_state_label(): string {
		if ( self::PURPLE_SLUG === get_stylesheet() ) {
			return __( 'active', 'woocommerce' );
		}

		if ( wp_get_theme( self::PURPLE_SLUG )->exists() ) {
			return __( 'installed', 'woocommerce' );
		}

		return __( 'not installed', 'woocommerce' );
	}

	/**
	 * Styles for the admin bar menu: group headings and a check mark on the active option.
	 */
	public function output_admin_bar_css(): void {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		echo '<style>
			#wpadminbar .purple-cys-prototype-heading > .ab-item {
				color: #a7aaad;
				font-size: 11px;
				text-transform: uppercase;
				letter-spacing: 0.04em;
			}
			#wpadminbar .purple-cys-prototype-option > .ab-item::before {
				content: "";
				display: inline-block;
				width: 16px;
				font-family: inherit;
			}
			#wpadminbar .purple-cys-prototype-option.is-active > .ab-item::before {
				content: "\2713";
			}
			#wpadminbar .purple-cys-prototype-option.is-active > .ab-item {
				font-weight: 600;
			}
		</style>';
	}

	/**
	 * Pass the prototype settings and the real theme state to the client.
	 *
	 * @param array $settings Shared settings.
	 * @return array
	 */
	public function add_shared_settings( $settings ) {
		if ( ! is_array( $settings ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return $settings;
		}

		$active_theme = wp_get_theme();

		$settings['purpleCysPrototype'] = array_merge(
			$this->get_settings(),
			array(
				'purpleSlug'      => self::PURPLE_SLUG,
				'purpleInstalled' => wp_get_theme( self::PURPLE_SLUG )->exists(),
				'purpleActive'    => self::PURPLE_SLUG === get_stylesheet(),
				'activeTheme'     => array(
					'name'         => $active_theme->get( 'Name' ),
					'stylesheet'   => $active_theme->get_stylesheet(),
					'screenshot'   => (string) $active_theme->get_screenshot(),
					'isBlockTheme' => $active_theme->is_block_theme(),
				),
				'homeUrl'         => home_url( '/' ),
			)
		);

		return $settings;
	}
}
