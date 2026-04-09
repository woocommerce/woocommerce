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
	public static function page_output() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['edit-staff'] ) ) {
			$user_id = absint( $_GET['edit-staff'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $user_id && ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
			}
			self::edit_output( $user_id );
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Table list output.
	 *
	 * @since 10.8.0
	 */
	private static function table_list_output() {
		$staff_table = new WC_Admin_POS_Staff_Table_List();
		$staff_table->prepare_items();

		echo '<h2 class="wc-table-list-header">'
			. esc_html__( 'POS Staff', 'woocommerce' )
			. '</h2>';

		if ( $staff_table->has_items() ) {
			echo '<input type="hidden" name="page" value="wc-settings" />';
			echo '<input type="hidden" name="tab" value="point-of-sale" />';
			echo '<input type="hidden" name="section" value="staff" />';

			$staff_table->display();
		} else {
			echo '<div class="woocommerce-BlankState">';
			echo '<h2 class="woocommerce-BlankState-message">'
				. esc_html__(
					'No users with POS access were found. Assign the POS Cashier or POS Manager role to users to manage their PINs.',
					'woocommerce'
				)
				. '</h2>';
			echo '</div>';
		}
	}

	/**
	 * Edit output for a specific staff member.
	 *
	 * @since 10.8.0
	 * @param int $user_id User ID.
	 */
	private static function edit_output( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_die( esc_html__( 'Invalid user.', 'woocommerce' ) );
		}

		if ( ! user_can( $user_id, 'woocommerce_pos_access' ) ) {
			wp_die( esc_html__( 'This user does not have POS access.', 'woocommerce' ) );
		}

		$pin_service = new POSPinService();
		$has_pin     = $pin_service->has_pin( $user_id );
		$roles       = (array) $user->roles;
		$role_name   = '';

		if ( ! empty( $roles ) ) {
			$wp_roles  = wp_roles();
			$first     = reset( $roles );
			$role_name = isset( $wp_roles->role_names[ $first ] )
				? translate_user_role( $wp_roles->role_names[ $first ] )
				: $first;
		}

		include __DIR__ . '/settings/views/html-pos-staff-edit.php';
	}

	/**
	 * Handle admin actions.
	 *
	 * @since 10.8.0
	 */
	public function actions() {
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
	private function save() {
		check_admin_referer( 'woocommerce-pos-staff-pin' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$pin     = isset( $_POST['pos_pin'] ) ? sanitize_text_field( wp_unslash( $_POST['pos_pin'] ) ) : '';

		if ( ! $user_id || ! user_can( $user_id, 'woocommerce_pos_access' ) ) {
			WC_Admin_Settings::add_error( __( 'Invalid user or user does not have POS access.', 'woocommerce' ) );
			return;
		}

		if ( empty( $pin ) ) {
			WC_Admin_Settings::add_error( __( 'Please enter a PIN.', 'woocommerce' ) );
			return;
		}

		$pin_service = new POSPinService();
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
	private function remove_pin() {
		check_admin_referer( 'remove-pos-pin' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		$user_id = isset( $_GET['remove-pin'] ) ? absint( $_GET['remove-pin'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $user_id || ! user_can( $user_id, 'woocommerce_pos_access' ) ) {
			wp_die( esc_html__( 'Invalid user or user does not have POS access.', 'woocommerce' ) );
		}

		$pin_service = new POSPinService();
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
	 * Display admin notices.
	 *
	 * @since 10.8.0
	 */
	public static function notices() {
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
