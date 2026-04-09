<?php
/**
 * WooCommerce Admin POS Staff Class
 *
 * @package WooCommerce\Admin
 * @since   10.8.0
 */

declare(strict_types=1);

use Automattic\WooCommerce\Internal\POS\Service\POSPinService;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_POS_Staff.
 *
 * @since 10.8.0
 */
class WC_Admin_POS_Staff {

	/**
	 * Initialize the POS Staff admin actions.
	 *
	 * @since 10.8.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
		add_action( 'admin_head', array( $this, 'styles' ) );
	}

	/**
	 * Check if this is the POS staff settings page.
	 *
	 * @since 10.8.0
	 * @return bool
	 */
	private function is_pos_staff_settings_page() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		return is_wc_admin_settings_page()
			&& isset( $_GET['tab'], $_GET['section'] )
			&& 'point-of-sale' === $_GET['tab']
			&& 'staff' === $_GET['section'];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Page output.
	 *
	 * @since 10.8.0
	 */
	public static function page_output(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['edit-staff'] ) ) {
			$user_id = absint( $_GET['edit-staff'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $user_id && ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
			}

			self::edit_output( $user_id );
			return;
		}

		self::table_list_output();
	}

	/**
	 * Table list output.
	 *
	 * @since 10.8.0
	 */
	private static function table_list_output(): void {
		$staff_table = new WC_Admin_POS_Staff_Table_List();
		$staff_table->prepare_items();

		$users_link = current_user_can( 'list_users' )
			? '<a href="' . esc_url( admin_url( 'users.php' ) ) . '">' . esc_html__( 'Users', 'woocommerce' ) . '</a>'
			: esc_html__( 'Users', 'woocommerce' );

		echo '<div class="wc-pos-staff-page">';
		echo '<h2 class="wc-table-list-header">' . esc_html__( 'Staff', 'woocommerce' ) . '</h2>';
		echo '<p class="wc-pos-staff-description">';
		echo wp_kses(
			sprintf(
				/* translators: %s: Users admin screen link. */
				__( 'Set PINs for existing users with point of sale access. Change roles in %s.', 'woocommerce' ),
				$users_link
			),
			array(
				'a' => array(
					'href' => array(),
				),
			)
		);
		echo '</p>';
		$staff_table->display();
		echo '</div>';
	}

	/**
	 * Edit output for a specific staff member.
	 *
	 * @since 10.8.0
	 * @param int $user_id User ID.
	 */
	private static function edit_output( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_die( esc_html__( 'Invalid user.', 'woocommerce' ) );
		}

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered in WC_Install::create_roles().
		if ( ! user_can( $user_id, 'woocommerce_pos_access' ) ) {
			wp_die( esc_html__( 'This user does not have POS access.', 'woocommerce' ) );
		}

		$pin_service = wc_get_container()->get( POSPinService::class );
		$has_pin     = $pin_service->has_pin( $user_id );
		$role_name   = self::get_role_name( $user );

		echo '<div class="wc-pos-staff-page">';
		include __DIR__ . '/settings/views/html-pos-staff-edit.php';
		echo '</div>';
	}

	/**
	 * Handle admin actions.
	 *
	 * @since 10.8.0
	 */
	public function actions(): void {
		if ( ! $this->is_pos_staff_settings_page() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['remove-pin'] ) ) {
			$this->remove_pin();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['save_pos_staff_pin'] ) ) {
			$this->save();
		}
	}

	/**
	 * Handle PIN save.
	 *
	 * @since 10.8.0
	 */
	private function save(): void {
		check_admin_referer( 'woocommerce-pos-staff-pin', 'woocommerce_pos_staff_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$pin     = isset( $_POST['pos_pin'] ) ? sanitize_text_field( wp_unslash( $_POST['pos_pin'] ) ) : '';

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered in WC_Install::create_roles().
		if ( ! $user_id || ! user_can( $user_id, 'woocommerce_pos_access' ) ) {
			WC_Admin_Settings::add_error( __( 'Invalid user or user does not have POS access.', 'woocommerce' ) );
			return;
		}

		if ( empty( $pin ) ) {
			WC_Admin_Settings::add_error( __( 'Please enter a PIN.', 'woocommerce' ) );
			return;
		}

		$pin_service = wc_get_container()->get( POSPinService::class );
		$result      = $pin_service->set_pin( $user_id, $pin );

		if ( is_wp_error( $result ) ) {
			WC_Admin_Settings::add_error( $result->get_error_message() );
			return;
		}

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'page'    => 'wc-settings',
						'tab'     => 'point-of-sale',
						'section' => 'staff',
						'saved'   => '1',
					),
					admin_url( 'admin.php' )
				)
			)
		);
		exit();
	}

	/**
	 * Remove a user's PIN.
	 *
	 * @since 10.8.0
	 */
	private function remove_pin(): void {
		check_admin_referer( 'remove-pos-pin' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		$user_id = isset( $_GET['remove-pin'] ) ? absint( $_GET['remove-pin'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered in WC_Install::create_roles().
		if ( ! $user_id || ! user_can( $user_id, 'woocommerce_pos_access' ) ) {
			wp_die( esc_html__( 'Invalid user or user does not have POS access.', 'woocommerce' ) );
		}

		$pin_service = wc_get_container()->get( POSPinService::class );
		$pin_service->delete_pin( $user_id );

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'page'    => 'wc-settings',
						'tab'     => 'point-of-sale',
						'section' => 'staff',
						'removed' => '1',
					),
					admin_url( 'admin.php' )
				)
			)
		);
		exit();
	}

	/**
	 * Return a translated role name for a user.
	 *
	 * @since 10.8.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	private static function get_role_name( WP_User $user ): string {
		$roles = (array) $user->roles;

		if ( empty( $roles ) ) {
			return '';
		}

		$wp_roles = wp_roles();
		$first    = reset( $roles );

		return isset( $wp_roles->role_names[ $first ] )
			? translate_user_role( $wp_roles->role_names[ $first ] )
			: $first;
	}

	/**
	 * Output scoped styles for the POS staff screen.
	 *
	 * @since 10.8.0
	 */
	public function styles(): void {
		if ( ! $this->is_pos_staff_settings_page() ) {
			return;
		}
		?>
		<style>
			.woocommerce #mainform > p.submit {
				display: none !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			.woocommerce .wc-pos-staff-page .wc-table-list-header {
				margin-bottom: 0.2em;
			}

			.woocommerce .wc-pos-staff-page .wc-pos-staff-description {
				margin: 0 0 8px;
				color: #50575e;
			}

			.woocommerce .wc-pos-staff-page .column-pin_status {
				width: 140px;
			}

			.woocommerce .wc-pos-staff-page .column-actions {
				width: 160px;
			}

			.woocommerce .wc-pos-staff-page .wc-pos-staff-status {
				color: #50575e;
			}

			.woocommerce .wc-pos-staff-page .wc-pos-staff-status--active {
				color: #1d6b1d;
				font-weight: 600;
			}

			.woocommerce .wc-pos-staff-page #pos-staff-fields input[type="password"] {
				width: 100px;
				min-width: 0;
				font-variant-numeric: tabular-nums;
			}

			.woocommerce .wc-pos-staff-page #pos-staff-fields .submit {
				margin: 0;
				padding: 0;
			}
		</style>
		<?php
	}

	/**
	 * Display admin notices.
	 *
	 * @since 10.8.0
	 */
	public static function notices(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['saved'] ) ) {
			WC_Admin_Settings::add_message( __( 'PIN saved successfully.', 'woocommerce' ) );
		}

		if ( isset( $_GET['removed'] ) ) {
			WC_Admin_Settings::add_message( __( 'PIN removed successfully.', 'woocommerce' ) );
		}
		// phpcs:enable
	}
}

new WC_Admin_POS_Staff();
