<?php
/**
 * WooCommerce Admin POS Staff Class
 *
 * @package WooCommerce\Admin
 * @since   10.9.0
 */

declare(strict_types=1);

use Automattic\WooCommerce\Internal\POS\Capabilities as POSCapabilities;
use Automattic\WooCommerce\Internal\POS\POSController;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_POS_Staff.
 *
 * Server-side admin UI for managing POS staff: assigning a POS role to a WP user
 * and setting their PIN. Gated behind the `point_of_sale_staff` dev feature flag
 * (which also requires the parent `point_of_sale` flag); when either is off, the
 * class is inert.
 *
 * @since 10.9.0
 */
class WC_Admin_POS_Staff {

	private const EDIT_NEW = 'new';

	/**
	 * Initialize the POS Staff admin actions.
	 *
	 * @since 10.9.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
		add_action( 'admin_head', array( $this, 'styles' ) );
	}

	/**
	 * Whether the POS staff admin UI is currently enabled.
	 *
	 * @since 10.9.0
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( POSController::PARENT_FLAG )
			&& FeaturesUtil::feature_is_enabled( POSController::FEATURE_FLAG );
	}

	/**
	 * Check if this is the POS staff settings page.
	 *
	 * @since 10.9.0
	 * @return bool
	 */
	private function is_pos_staff_settings_page() {
		if ( ! self::is_enabled() ) {
			return false;
		}
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
	 * @since 10.9.0
	 */
	public static function page_output(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['edit-staff'] ) ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
			}

			$edit_param = sanitize_text_field( wp_unslash( $_GET['edit-staff'] ) );
			if ( self::EDIT_NEW === $edit_param ) {
				self::add_output();
				return;
			}

			$user_id = absint( $edit_param );
			if ( $user_id > 0 ) {
				self::edit_output( $user_id );
				return;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		self::table_list_output();
	}

	/**
	 * Table list output.
	 *
	 * @since 10.9.0
	 */
	private static function table_list_output(): void {
		$staff_table = new WC_Admin_POS_Staff_Table_List();
		$staff_table->prepare_items();

		$add_url = add_query_arg(
			array(
				'page'       => 'wc-settings',
				'tab'        => 'point-of-sale',
				'section'    => 'staff',
				'edit-staff' => self::EDIT_NEW,
			),
			admin_url( 'admin.php' )
		);

		echo '<div class="wc-pos-staff-page">';
		echo '<div class="wc-pos-staff-header">';
		echo '<h2 class="wc-table-list-header">' . esc_html__( 'Staff', 'woocommerce' ) . '</h2>';
		echo '<a href="' . esc_url( $add_url ) . '" class="page-title-action">'
			. esc_html__( 'Add staff', 'woocommerce' ) . '</a>';
		echo '</div>';
		echo '<p class="wc-pos-staff-description">';
		esc_html_e( 'Assign a Point of Sale role to a user and set their PIN.', 'woocommerce' );
		echo '</p>';
		$staff_table->display();
		echo '</div>';
	}

	/**
	 * Add-staff output: pick an existing wp_user (via AJAX search) or create a
	 * new one inline, assign a POS role, and optionally set a PIN.
	 *
	 * @since 10.9.0
	 */
	private static function add_output(): void {
		$assignable_pos_roles  = POSCapabilities::assignable_pos_roles();
		$assigned_user_ids_csv = self::existing_staff_user_ids_csv();

		echo '<div class="wc-pos-staff-page">';
		include __DIR__ . '/settings/views/html-pos-staff-add.php';
		echo '</div>';
	}

	/**
	 * Return a CSV string of user IDs that already have a POS role assigned.
	 *
	 * Used as the `data-exclude` attribute on the wc-customer-search dropdown so
	 * the AJAX search doesn't surface users we'd then reject server-side anyway.
	 *
	 * @return string
	 */
	private static function existing_staff_user_ids_csv(): string {
		$user_query = new \WP_User_Query(
			array(
				'fields'     => 'ID',
				'number'     => -1,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => POSCapabilities::POS_ROLE_META_KEY,
						'value'   => POSCapabilities::assignable_pos_roles(),
						'compare' => 'IN',
					),
				),
			)
		);

		return implode( ',', array_map( 'intval', $user_query->get_results() ) );
	}

	/**
	 * Edit output for an existing staff member.
	 *
	 * @since 10.9.0
	 * @param int $user_id User ID.
	 */
	private static function edit_output( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_die( esc_html__( 'Invalid user.', 'woocommerce' ) );
		}

		if ( ! POSCapabilities::has_pos_access( $user_id ) ) {
			wp_die( esc_html__( 'This user does not have POS access.', 'woocommerce' ) );
		}

		$pin_service          = wc_get_container()->get( POSPinService::class );
		$has_pin              = $pin_service->has_pin( $user_id );
		$current_pos_role     = (string) POSCapabilities::get_pos_role( $user_id );
		$assignable_pos_roles = POSCapabilities::assignable_pos_roles();

		echo '<div class="wc-pos-staff-page">';
		include __DIR__ . '/settings/views/html-pos-staff-edit.php';
		echo '</div>';
	}

	/**
	 * Handle admin actions.
	 *
	 * @internal
	 *
	 * @since 10.9.0
	 */
	public function actions(): void {
		if ( ! $this->is_pos_staff_settings_page() ) {
			return;
		}

		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '' ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['add_pos_staff'] ) ) {
			$this->add();
			return;
		}

		if ( isset( $_POST['remove_pos_staff'] ) ) {
			$this->remove_staff();
			return;
		}

		if ( isset( $_POST['save_pos_staff'] ) ) {
			$this->save();
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Handle the "Add staff" form submission.
	 *
	 * Two paths:
	 *  - existing user: caller selected one from the dropdown; assign a POS role.
	 *  - new user: caller left the dropdown blank and entered a username; create a
	 *    wp_user with an unreachable placeholder email (unless an override is
	 *    provided) and then assign the POS role.
	 *
	 * The PIN is always optional — see the form copy for the rationale.
	 */
	private function add(): void {
		check_admin_referer( 'woocommerce-pos-staff-add', 'woocommerce_pos_staff_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		$user_id        = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$new_user_login = isset( $_POST['new_user_login'] ) ? sanitize_user( wp_unslash( $_POST['new_user_login'] ), true ) : '';
		$new_user_email = isset( $_POST['new_user_email'] ) ? sanitize_email( wp_unslash( $_POST['new_user_email'] ) ) : '';
		$pos_role       = isset( $_POST['pos_role'] ) ? sanitize_key( wp_unslash( $_POST['pos_role'] ) ) : '';
		$pin            = isset( $_POST['pos_pin'] ) ? sanitize_text_field( wp_unslash( $_POST['pos_pin'] ) ) : '';

		if ( ! in_array( $pos_role, POSCapabilities::assignable_pos_roles(), true ) ) {
			WC_Admin_Settings::add_error( __( 'Please choose a valid POS role.', 'woocommerce' ) );
			return;
		}

		$pin_service = wc_get_container()->get( POSPinService::class );

		// Pre-validate the PIN format so a bad value doesn't create a half-rolled-back
		// user (we'd otherwise insert the wp_user, assign the role, and then fail).
		if ( '' !== $pin && ! $pin_service->validate_pin_format( $pin ) ) {
			WC_Admin_Settings::add_error( __( 'PIN must be exactly 4 digits.', 'woocommerce' ) );
			return;
		}

		if ( $user_id > 0 ) {
			if ( ! get_userdata( $user_id ) ) {
				WC_Admin_Settings::add_error( __( 'Invalid user.', 'woocommerce' ) );
				return;
			}
			if ( POSCapabilities::has_pos_access( $user_id ) ) {
				WC_Admin_Settings::add_error( __( 'This user already has a POS role assigned.', 'woocommerce' ) );
				return;
			}
		} else {
			$created = self::create_pos_only_user( $new_user_login, $new_user_email );
			if ( is_wp_error( $created ) ) {
				WC_Admin_Settings::add_error( $created->get_error_message() );
				return;
			}
			$user_id = $created;
		}

		POSCapabilities::set_pos_role( $user_id, $pos_role );

		if ( '' !== $pin ) {
			$pin_service->set_pin( $user_id, $pin );
		}

		wp_safe_redirect( self::list_redirect_url( array( 'added' => '1' ) ) );
		exit();
	}

	/**
	 * Create a wp_user record purely so we have something to attribute POS
	 * activity to.
	 *
	 * Hardening (per Proposal 1):
	 *  - WP role is set to `subscriber` — the minimum-cap built-in role. The
	 *    standard `WC_Admin::prevent_admin_access()` redirect already bounces
	 *    subscribers from wp-admin, so we get the customer-style wp-admin block
	 *    for free without a parallel admin_init hook.
	 *  - `user_pass` is a long random string — no usable credential for
	 *    password-based auth (wp-login.php, REST cookie, Application Passwords,
	 *    XML-RPC).
	 *  - `user_email` defaults to a plus-addressed copy of the merchant's admin
	 *    email (e.g. `merchant+pos-42@example.com`). WP requires unique emails,
	 *    plus-addressing gives us per-user uniqueness while still routing any
	 *    password-reset attempts to the merchant inbox rather than letting the
	 *    POS-only operator self-recover. Admins can override with a real
	 *    address in the form when that's actually desired.
	 *
	 * @param string $login          Sanitized desired username.
	 * @param string $email_override Sanitized override email, or empty to auto-generate.
	 * @return int|\WP_Error User ID on success.
	 */
	private static function create_pos_only_user( string $login, string $email_override ) {
		if ( '' === $login ) {
			return new \WP_Error(
				'woocommerce_pos_missing_username',
				__( 'Enter a username for the new staff member, or select an existing user.', 'woocommerce' )
			);
		}
		if ( ! validate_username( $login ) ) {
			return new \WP_Error(
				'woocommerce_pos_invalid_username',
				__( 'That username contains invalid characters.', 'woocommerce' )
			);
		}
		if ( username_exists( $login ) ) {
			return new \WP_Error(
				'woocommerce_pos_username_exists',
				__( 'That username already exists. Pick them from the existing user dropdown instead.', 'woocommerce' )
			);
		}

		if ( '' !== $email_override ) {
			if ( ! is_email( $email_override ) ) {
				return new \WP_Error(
					'woocommerce_pos_invalid_email',
					__( 'Enter a valid email address, or leave the field blank.', 'woocommerce' )
				);
			}
			if ( email_exists( $email_override ) ) {
				return new \WP_Error(
					'woocommerce_pos_email_exists',
					__( 'That email is already in use by another account.', 'woocommerce' )
				);
			}
			$initial_email = $email_override;
		} else {
			// Random placeholder so the insert succeeds; we rewrite it to a
			// plus-addressed form below once we know the new user's ID.
			$initial_email = sprintf( 'pos-staff-%s@pos.invalid', wp_generate_uuid4() );
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $initial_email,
				'user_pass'    => wp_generate_password( 24, true, true ),
				'display_name' => $login,
				'role'         => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		if ( '' === $email_override ) {
			wp_update_user(
				array(
					'ID'         => $user_id,
					'user_email' => self::generate_plus_addressed_email( (int) $user_id ),
				)
			);
		}

		return $user_id;
	}

	/**
	 * Build the plus-addressed email for a POS-only user based on the merchant's
	 * configured admin email.
	 *
	 * Tries the canonical `merchant+pos-{id}@domain` form first. In the rare
	 * case that's already in use (someone manually created such a wp_user, or
	 * a backup restore reused a user_id), retries with a short random suffix
	 * (`merchant+pos-{id}-{rand}@domain`). After a handful of attempts we
	 * fall back to a non-routable `.invalid` placeholder rather than blocking
	 * staff creation — password reset would silently fail at that point,
	 * which matches the "POS-only user cannot self-recover" design goal.
	 *
	 * Falls back to a `.invalid` placeholder up front when admin_email is
	 * missing or malformed (shouldn't happen on a normal install).
	 *
	 * @param int $user_id The user_id whose email we're generating.
	 * @return string
	 */
	private static function generate_plus_addressed_email( int $user_id ): string {
		$merchant_email = (string) get_option( 'admin_email', '' );
		if ( '' === $merchant_email || ! str_contains( $merchant_email, '@' ) ) {
			return sprintf( 'pos-staff-%d@pos.invalid', $user_id );
		}

		list( $local, $domain ) = explode( '@', $merchant_email, 2 );

		// Strip any pre-existing +tag from the merchant local part so we don't
		// stack tags (`merchant+old+pos-42@…`).
		$plus = strpos( $local, '+' );
		if ( false !== $plus ) {
			$local = substr( $local, 0, $plus );
		}

		$canonical = sprintf( '%s+pos-%d@%s', $local, $user_id, $domain );
		if ( ! email_exists( $canonical ) ) {
			return $canonical;
		}

		for ( $i = 0; $i < 5; $i++ ) {
			$candidate = sprintf(
				'%s+pos-%d-%s@%s',
				$local,
				$user_id,
				strtolower( wp_generate_password( 6, false, false ) ),
				$domain
			);
			if ( ! email_exists( $candidate ) ) {
				return $candidate;
			}
		}

		return sprintf( 'pos-staff-%d-%s@pos.invalid', $user_id, wp_generate_uuid4() );
	}

	/**
	 * Handle the "Save staff" form submission for an existing staff member.
	 *
	 * Lets the admin change the POS role and/or set/replace the PIN.
	 */
	private function save(): void {
		check_admin_referer( 'woocommerce-pos-staff-edit', 'woocommerce_pos_staff_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		$user_id  = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$pos_role = isset( $_POST['pos_role'] ) ? sanitize_key( wp_unslash( $_POST['pos_role'] ) ) : '';
		$pin      = isset( $_POST['pos_pin'] ) ? sanitize_text_field( wp_unslash( $_POST['pos_pin'] ) ) : '';

		if ( ! $user_id || ! POSCapabilities::has_pos_access( $user_id ) ) {
			WC_Admin_Settings::add_error( __( 'Invalid user or user does not have POS access.', 'woocommerce' ) );
			return;
		}

		if ( ! in_array( $pos_role, POSCapabilities::assignable_pos_roles(), true ) ) {
			WC_Admin_Settings::add_error( __( 'Please choose a valid POS role.', 'woocommerce' ) );
			return;
		}

		POSCapabilities::set_pos_role( $user_id, $pos_role );

		if ( '' !== $pin ) {
			$pin_service = wc_get_container()->get( POSPinService::class );
			$result      = $pin_service->set_pin( $user_id, $pin );
			if ( is_wp_error( $result ) ) {
				WC_Admin_Settings::add_error( $result->get_error_message() );
				return;
			}
		}

		wp_safe_redirect( self::list_redirect_url( array( 'saved' => '1' ) ) );
		exit();
	}

	/**
	 * Remove a staff member: clear their POS role meta and PIN.
	 */
	private function remove_staff(): void {
		check_admin_referer( 'remove-pos-staff', 'woocommerce_pos_staff_remove_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $user_id || ! POSCapabilities::has_pos_access( $user_id ) ) {
			wp_die( esc_html__( 'Invalid user or user does not have POS access.', 'woocommerce' ) );
		}

		$pin_service = wc_get_container()->get( POSPinService::class );
		$pin_service->delete_pin( $user_id );
		POSCapabilities::set_pos_role( $user_id, null );

		wp_safe_redirect( self::list_redirect_url( array( 'removed' => '1' ) ) );
		exit();
	}

	/**
	 * Build a redirect URL back to the staff list with the given query args.
	 *
	 * @param array<string, scalar> $extra Extra query args to merge in.
	 * @return string
	 */
	private static function list_redirect_url( array $extra = array() ): string {
		return esc_url_raw(
			add_query_arg(
				array_merge(
					array(
						'page'    => 'wc-settings',
						'tab'     => 'point-of-sale',
						'section' => 'staff',
					),
					$extra
				),
				admin_url( 'admin.php' )
			)
		);
	}

	/**
	 * Return the translated POS role label for a user.
	 *
	 * @since 10.9.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	public static function get_pos_role_label( WP_User $user ): string {
		$pos_role = POSCapabilities::get_pos_role( (int) $user->ID );
		return null === $pos_role ? '' : POSCapabilities::role_label( $pos_role );
	}

	/**
	 * Output scoped styles for the POS staff screen.
	 *
	 * @internal
	 *
	 * @since 10.9.0
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

			.woocommerce .wc-pos-staff-page .wc-pos-staff-header {
				display: flex;
				align-items: baseline;
				gap: 12px;
				margin-bottom: 0.2em;
			}

			.woocommerce .wc-pos-staff-page .wc-pos-staff-header .wc-table-list-header {
				margin: 0;
			}

			.woocommerce .wc-pos-staff-page .wc-pos-staff-description {
				margin: 0 0 8px;
				color: #50575e;
			}

			.woocommerce .wc-pos-staff-page .column-pin_status {
				width: 140px;
			}

			.woocommerce .wc-pos-staff-page .column-actions {
				width: 200px;
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
	 * @since 10.9.0
	 */
	public static function notices(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['saved'] ) ) {
			WC_Admin_Settings::add_message( __( 'Staff updated.', 'woocommerce' ) );
		}

		if ( isset( $_GET['added'] ) ) {
			WC_Admin_Settings::add_message( __( 'Staff added.', 'woocommerce' ) );
		}

		if ( isset( $_GET['removed'] ) ) {
			WC_Admin_Settings::add_message( __( 'Staff removed.', 'woocommerce' ) );
		}
		// phpcs:enable
	}
}

new WC_Admin_POS_Staff();
