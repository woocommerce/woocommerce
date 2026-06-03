<?php
/**
 * WooCommerce Admin POS Staff Class
 *
 * @package WooCommerce\Admin
 * @since   11.0.0
 */

declare(strict_types=1);

use Automattic\WooCommerce\Internal\POS\Capabilities as POSCapabilities;
use Automattic\WooCommerce\Internal\POS\POSController;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\POS\Service\POSStaffService;
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
 * @since 11.0.0
 */
class WC_Admin_POS_Staff {

	private const EDIT_NEW = 'new';

	/**
	 * Submitted form values from a failed add/save submission, keyed by field name.
	 *
	 * Set by add()/save() before they bail on error so the form view can re-render
	 * the user's input — letting them fix only the offending field (typically PIN)
	 * instead of re-typing everything. PIN is intentionally never stashed: a
	 * collision means the value was unusable, and asking for a fresh PIN entry on
	 * retry is both safer and more obvious to the merchant than pre-filling.
	 *
	 * @var array<string, string>
	 */
	private static array $form_retry = array();

	/**
	 * Initialize the POS Staff admin actions.
	 *
	 * @since 11.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
		add_action( 'admin_head', array( $this, 'styles' ) );
	}

	/**
	 * Whether the POS staff admin UI is currently enabled.
	 *
	 * @since 11.0.0
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( POSController::PARENT_FLAG )
			&& FeaturesUtil::feature_is_enabled( POSController::FEATURE_FLAG );
	}

	/**
	 * Check if this is the POS staff settings page.
	 *
	 * @since 11.0.0
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
	 * @since 11.0.0
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
	 * @since 11.0.0
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
	 * new one inline, assign a POS role, and set a PIN.
	 *
	 * @since 11.0.0
	 */
	private static function add_output(): void {
		$assignable_pos_presets = POSCapabilities::assignable_pos_presets();
		$assigned_user_ids_csv  = self::existing_staff_user_ids_csv();

		// On error, $form_retry is set by add() so the view can re-render the form
		// pre-filled with the merchant's prior input. On a fresh open, this is empty.
		$retry_values = self::$form_retry;

		// Render an <option selected> for the previously-chosen existing user so
		// the wc-customer-search dropdown re-displays them after a failed submit —
		// the select2 widget needs the option in the DOM to show it as selected.
		$retry_existing_user = null;
		if ( ! empty( $retry_values['user_id'] ) ) {
			$candidate = get_userdata( (int) $retry_values['user_id'] );
			if ( $candidate instanceof WP_User ) {
				$retry_existing_user = $candidate;
			}
		}

		echo '<div class="wc-pos-staff-page">';
		include __DIR__ . '/settings/views/html-pos-staff-add.php';
		echo '</div>';
	}

	/**
	 * Return a CSV string of user IDs that already have POS access.
	 *
	 * Used as the `data-exclude` attribute on the wc-customer-search dropdown so
	 * the AJAX search doesn't surface users we'd then reject server-side anyway.
	 *
	 * @return string
	 */
	private static function existing_staff_user_ids_csv(): string {
		$user_query = new \WP_User_Query(
			array(
				'role'   => POSCapabilities::POS_STAFF_ROLE,
				'fields' => 'ID',
				'number' => -1,
			)
		);

		return implode( ',', array_map( 'intval', $user_query->get_results() ) );
	}

	/**
	 * Edit output for an existing staff member.
	 *
	 * @since 11.0.0
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

		$pin_service            = wc_get_container()->get( POSPinService::class );
		$has_pin                = $pin_service->has_pin( $user_id );
		$current_pos_preset     = (string) POSCapabilities::get_pos_preset( $user_id );
		$assignable_pos_presets = POSCapabilities::assignable_pos_presets();

		// On error, save() stashes the chosen role so the view can re-select it
		// instead of falling back to the stored value.
		$retry_pos_preset = self::$form_retry['pos_preset'] ?? '';
		if ( '' !== $retry_pos_preset && in_array( $retry_pos_preset, $assignable_pos_presets, true ) ) {
			$current_pos_preset = $retry_pos_preset;
		}

		echo '<div class="wc-pos-staff-page">';
		include __DIR__ . '/settings/views/html-pos-staff-edit.php';
		echo '</div>';
	}

	/**
	 * Handle admin actions.
	 *
	 * @internal
	 *
	 * @since 11.0.0
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
	 *  - Flow A — existing user: caller picked one from the autocomplete dropdown.
	 *    The user keeps their existing WP role; pos_staff is added as a secondary
	 *    role via set_pos_preset() below.
	 *  - Flow B — create new user: caller entered display name + email. A new WP
	 *    user is created with pos_staff as their only role.
	 *
	 * A PIN is mandatory — the device authenticates the operator solely by PIN,
	 * so a row without one would be unusable. PIN uniqueness across staff is
	 * enforced by POSPinService::set_pin.
	 */
	private function add(): void {
		check_admin_referer( 'woocommerce-pos-staff-add', 'woocommerce_pos_staff_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		$user_id          = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$new_display_name = isset( $_POST['new_display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['new_display_name'] ) ) : '';
		$new_user_email   = isset( $_POST['new_user_email'] ) ? sanitize_email( wp_unslash( $_POST['new_user_email'] ) ) : '';
		$pos_preset       = isset( $_POST['pos_preset'] ) ? sanitize_key( wp_unslash( $_POST['pos_preset'] ) ) : '';
		$pin              = isset( $_POST['pos_pin'] ) ? sanitize_text_field( wp_unslash( $_POST['pos_pin'] ) ) : '';

		// Stash sanitized values so the view can re-render the form pre-filled if
		// we bail below. PIN is deliberately omitted — the merchant must re-enter it.
		self::$form_retry = array(
			'user_id'          => (string) $user_id,
			'new_display_name' => $new_display_name,
			'new_user_email'   => $new_user_email,
			'pos_preset'       => $pos_preset,
		);

		if ( ! in_array( $pos_preset, POSCapabilities::assignable_pos_presets(), true ) ) {
			WC_Admin_Settings::add_error( __( 'Please choose a valid role.', 'woocommerce' ) );
			return;
		}

		$pin_service = wc_get_container()->get( POSPinService::class );

		// Pre-validate the PIN format so a bad value doesn't create a wp_user that
		// can't be finished. PIN uniqueness across staff is re-checked inside set_pin.
		if ( ! $pin_service->validate_pin_format( $pin ) ) {
			WC_Admin_Settings::add_error( __( 'PIN must be exactly 4 digits.', 'woocommerce' ) );
			return;
		}

		$created_user_id = 0;
		if ( $user_id > 0 ) {
			if ( ! get_userdata( $user_id ) ) {
				WC_Admin_Settings::add_error( __( 'Invalid user.', 'woocommerce' ) );
				return;
			}
			if ( POSCapabilities::has_pos_access( $user_id ) ) {
				WC_Admin_Settings::add_error( __( 'This user already has POS access.', 'woocommerce' ) );
				return;
			}
		} else {
			$staff_service = wc_get_container()->get( POSStaffService::class );
			$created       = $staff_service->create_staff( $new_user_email, $new_display_name );
			if ( is_wp_error( $created ) ) {
				WC_Admin_Settings::add_error( $created->get_error_message() );
				return;
			}
			$user_id         = $created;
			$created_user_id = $created;
		}

		// Set the PIN before the preset so a uniqueness collision (or any other
		// set_pin failure) doesn't leave a user with a preset and no PIN. Roll
		// back a freshly-created user on failure so the form is safe to retry.
		$pin_result = $pin_service->set_pin( $user_id, $pin );
		if ( is_wp_error( $pin_result ) ) {
			if ( $created_user_id > 0 ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
				wp_delete_user( $created_user_id );
			}
			WC_Admin_Settings::add_error( $pin_result->get_error_message() );
			return;
		}

		POSCapabilities::set_pos_preset( $user_id, $pos_preset );

		wp_safe_redirect( self::list_redirect_url( array( 'added' => '1' ) ) );
		exit();
	}

	/**
	 * Handle the "Save staff" form submission for an existing staff member.
	 *
	 * Lets the admin change the POS role and/or replace the PIN. A blank PIN field
	 * means "keep the existing PIN" — every staff row already has one because PIN
	 * is required at add time.
	 */
	private function save(): void {
		check_admin_referer( 'woocommerce-pos-staff-edit', 'woocommerce_pos_staff_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		$user_id    = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$pos_preset = isset( $_POST['pos_preset'] ) ? sanitize_key( wp_unslash( $_POST['pos_preset'] ) ) : '';
		$pin        = isset( $_POST['pos_pin'] ) ? sanitize_text_field( wp_unslash( $_POST['pos_pin'] ) ) : '';

		// Stash the chosen role so the form view can re-render the selected option
		// if we bail below. PIN is deliberately omitted — see $form_retry doc.
		self::$form_retry = array(
			'pos_preset' => $pos_preset,
		);

		if ( ! $user_id || ! POSCapabilities::has_pos_access( $user_id ) ) {
			WC_Admin_Settings::add_error( __( 'Invalid user or user does not have POS access.', 'woocommerce' ) );
			return;
		}

		if ( ! in_array( $pos_preset, POSCapabilities::assignable_pos_presets(), true ) ) {
			WC_Admin_Settings::add_error( __( 'Please choose a valid role.', 'woocommerce' ) );
			return;
		}

		// Apply the PIN first so a uniqueness collision (or any other set_pin
		// failure) doesn't change the preset partway through the request.
		if ( '' !== $pin ) {
			$pin_service = wc_get_container()->get( POSPinService::class );
			$result      = $pin_service->set_pin( $user_id, $pin );
			if ( is_wp_error( $result ) ) {
				WC_Admin_Settings::add_error( $result->get_error_message() );
				return;
			}
		}

		POSCapabilities::set_pos_preset( $user_id, $pos_preset );

		wp_safe_redirect( self::list_redirect_url( array( 'saved' => '1' ) ) );
		exit();
	}

	/**
	 * Remove a staff member.
	 *
	 * Two paths, decided by the user's WP role footprint:
	 *  - User has roles other than pos_staff (e.g. shop_manager): clear the
	 *    preset meta, drop pos_staff role, delete the PIN. WP user stays.
	 *  - User has ONLY pos_staff: the WP user account was created exclusively
	 *    for POS. Removing POS access would orphan them to a roleless state,
	 *    so we delete the WP user entirely. The table-list confirmation prompt
	 *    warns the merchant before the form posts.
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

		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			wp_die( esc_html__( 'Invalid user.', 'woocommerce' ) );
		}

		$pin_service = wc_get_container()->get( POSPinService::class );

		if ( array( POSCapabilities::POS_STAFF_ROLE ) === $user->roles ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			$pin_service->delete_pin( $user_id );
			wp_delete_user( $user_id, get_current_user_id() );

			wp_safe_redirect( self::list_redirect_url( array( 'deleted' => '1' ) ) );
			exit();
		}

		$pin_service->delete_pin( $user_id );
		POSCapabilities::set_pos_preset( $user_id, null );

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
	 * @since 11.0.0
	 * @param WP_User $user User object.
	 * @return string
	 */
	public static function get_pos_preset_label( WP_User $user ): string {
		$pos_preset = POSCapabilities::get_pos_preset( (int) $user->ID );
		return null === $pos_preset ? '' : POSCapabilities::preset_label( $pos_preset );
	}

	/**
	 * Output scoped styles for the POS staff screen.
	 *
	 * @internal
	 *
	 * @since 11.0.0
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

			.woocommerce .wc-pos-staff-page .wc-pos-staff-cap-preview {
				margin-top: 12px;
				padding: 10px 12px;
				background: #f6f7f7;
				border-left: 3px solid #2271b1;
				max-width: 520px;
			}

			.woocommerce .wc-pos-staff-page .wc-pos-staff-cap-preview .description {
				margin: 0 0 6px;
				font-weight: 600;
				color: #1d2327;
			}

			.woocommerce .wc-pos-staff-page .wc-pos-staff-cap-list {
				margin: 0;
				padding-left: 20px;
				color: #50575e;
			}

			.woocommerce .wc-pos-staff-page .wc-pos-staff-cap-list li {
				margin: 2px 0;
			}
		</style>
		<?php
	}

	/**
	 * Display admin notices.
	 *
	 * @since 11.0.0
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

		if ( isset( $_GET['deleted'] ) ) {
			WC_Admin_Settings::add_message( __( 'Staff member deleted.', 'woocommerce' ) );
		}
		// phpcs:enable
	}
}

new WC_Admin_POS_Staff();
