<?php
/**
 * WooCommerce Admin POS Staff Class
 *
 * @package WooCommerce\Admin
 * @since   11.0.0
 */

declare(strict_types=1);

use Automattic\WooCommerce\Internal\POS\Admin\POSAccessFields;
use Automattic\WooCommerce\Internal\POS\Admin\UserFormIntegration;
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
 * @since 11.0.0
 */
class WC_Admin_POS_Staff {

	private const EDIT_PICK_USER = 'pick-user';

	/**
	 * Submitted form values from a failed save submission, keyed by field name.
	 *
	 * Set by save() before it bails on error so the edit view can re-render the
	 * admin's input — letting them fix only the offending field (typically PIN)
	 * instead of re-choosing the role. PIN is intentionally never stashed: a
	 * collision means the value was unusable, and asking for a fresh PIN entry
	 * on retry is both safer and more obvious to the merchant than pre-filling.
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
		return FeaturesUtil::feature_is_enabled( POSController::FEATURE_FLAG );
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
			if ( self::EDIT_PICK_USER === $edit_param ) {
				self::pick_user_output();
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

		$add_url   = add_query_arg(
			array(
				UserFormIntegration::REQUEST_FLAG_PARAM => '1',
				'role'                                  => POSCapabilities::POS_STAFF_ROLE,
			),
			admin_url( 'user-new.php' )
		);
		$grant_url = add_query_arg(
			array(
				'page'       => 'wc-settings',
				'tab'        => 'point-of-sale',
				'section'    => 'staff',
				'edit-staff' => self::EDIT_PICK_USER,
			),
			admin_url( 'admin.php' )
		);

		echo '<div class="wc-pos-staff-page">';
		echo '<div class="wc-pos-staff-header">';
		echo '<h2 class="wc-table-list-header">' . esc_html__( 'Staff', 'woocommerce' ) . '</h2>';
		echo '<a href="' . esc_url( $add_url ) . '" class="page-title-action">'
			. esc_html__( 'Add new staff', 'woocommerce' ) . '</a>';
		echo '<a href="' . esc_url( $grant_url ) . '" class="page-title-action">'
			. esc_html__( 'Grant access to existing user', 'woocommerce' ) . '</a>';
		echo '</div>';
		echo '<p class="wc-pos-staff-description">';
		esc_html_e( 'Assign a Point of Sale role to a user and set their PIN.', 'woocommerce' );
		echo '</p>';
		$staff_table->display();
		echo '</div>';
	}

	/**
	 * Render the "Grant POS access to existing user" picker page.
	 *
	 * Fronts the regular edit screen: the admin picks a user via the
	 * wc-customer-search autocomplete, the form POSTs back to this page, and
	 * the action handler redirects them to ?edit-staff=<id> to finish setup.
	 *
	 * @since 11.0.0
	 */
	private static function pick_user_output(): void {
		$assigned_user_ids_csv = self::existing_staff_user_ids_csv();

		$form_action_url = add_query_arg(
			array(
				'page'       => 'wc-settings',
				'tab'        => 'point-of-sale',
				'section'    => 'staff',
				'edit-staff' => self::EDIT_PICK_USER,
			),
			admin_url( 'admin.php' )
		);

		echo '<div class="wc-pos-staff-page">';
		include __DIR__ . '/settings/views/html-pos-staff-pick-user.php';
		echo '</div>';
	}

	/**
	 * CSV of user IDs that already have POS access.
	 *
	 * Fed to the wc-customer-search dropdown via its `data-exclude` attribute so
	 * the AJAX search doesn't surface users we'd reject server-side anyway.
	 *
	 * @return string
	 */
	private static function existing_staff_user_ids_csv(): string {
		$user_query = new \WP_User_Query(
			array_merge(
				POSCapabilities::pos_staff_user_query_args(),
				array(
					'fields' => 'ID',
					'number' => -1,
				)
			)
		);

		return implode( ',', array_map( 'intval', $user_query->get_results() ) );
	}

	/**
	 * Edit output for a staff member.
	 *
	 * Handles two flows on the same URL:
	 *  - Edit: target user already has POS access — PIN is optional ("leave blank
	 *    to keep"), submit redirects with ?saved=1.
	 *  - Grant: target user does not yet have POS access — PIN is required,
	 *    submit redirects with ?added=1.
	 *
	 * Distinguishing on has_pos_access() means the screen handles both the
	 * "promote existing user" flow (entered via the picker page) and the
	 * "edit existing POS user" flow without a separate route.
	 *
	 * @since 11.0.0
	 * @param int $user_id User ID.
	 */
	private static function edit_output( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_die( esc_html__( 'Invalid user.', 'woocommerce' ) );
		}

		$pin_service            = wc_get_container()->get( POSPinService::class );
		$has_pos_access         = POSCapabilities::has_pos_access( $user_id );
		$has_pin                = $has_pos_access ? $pin_service->has_pin( $user_id ) : false;
		$current_pos_preset     = $has_pos_access ? (string) POSCapabilities::get_pos_preset( $user_id ) : '';
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
		if ( isset( $_POST['pick_pos_staff'] ) ) {
			$this->handle_pick_user();
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
	 * Handle the picker form submission: validate the chosen user and redirect to edit.
	 *
	 * The picker is the entry point for the "Grant POS access to existing user"
	 * flow, but the edit screen we redirect to already handles both grant and
	 * edit branches — so we accept any valid user_id and let the edit screen do
	 * the right thing for that user's current access state. The autocomplete
	 * excludes users who already have access in the common case; if a hand-crafted
	 * POST sneaks one in, the admin lands harmlessly on their edit screen.
	 */
	private function handle_pick_user(): void {
		check_admin_referer( 'woocommerce-pos-staff-pick', 'woocommerce_pos_staff_pick_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			WC_Admin_Settings::add_error( __( 'Please pick a valid user.', 'woocommerce' ) );
			return;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'wc-settings',
					'tab'        => 'point-of-sale',
					'section'    => 'staff',
					'edit-staff' => $user_id,
				),
				admin_url( 'admin.php' )
			)
		);
		exit();
	}

	/**
	 * Handle the edit-staff form submission.
	 *
	 * Branches on whether the target user currently has POS access:
	 *  - Grant flow (no access yet): PIN is required; on success the user gets
	 *    POS access for the first time and we redirect with ?added=1.
	 *  - Edit flow (already has access): a blank PIN means "keep the existing
	 *    PIN"; on success we redirect with ?saved=1.
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

		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			WC_Admin_Settings::add_error( __( 'Invalid user.', 'woocommerce' ) );
			return;
		}

		$is_granting  = ! POSCapabilities::has_pos_access( $user_id );
		$pin_optional = ! $is_granting;
		$pin_service  = wc_get_container()->get( POSPinService::class );

		$validation = POSAccessFields::validate( $pin_service, $pos_preset, $pin, $user_id, $pin_optional );
		if ( $validation instanceof \WP_Error ) {
			WC_Admin_Settings::add_error( $validation->get_error_message() );
			return;
		}

		$result = POSAccessFields::persist( $pin_service, $user_id, $pos_preset, $pin );
		if ( $result instanceof \WP_Error ) {
			WC_Admin_Settings::add_error( $result->get_error_message() );
			return;
		}

		$flash = $is_granting ? array( 'added' => '1' ) : array( 'saved' => '1' );
		wp_safe_redirect( self::list_redirect_url( $flash ) );
		exit();
	}

	/**
	 * Remove POS access from a staff member.
	 *
	 * A single, non-destructive action: deletes the PIN and clears the preset
	 * (which strips the pos_* caps and the preset meta, revoking access). The
	 * WP account is never deleted from here — account deletion is a Users-screen
	 * concern. The user is never left roleless either: set_pos_preset(null)
	 * keeps the pos_staff label when it is the user's only role.
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

			.woocommerce .wc-pos-staff-page .wc-pos-staff-pos-only {
				color: #50575e;
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
			WC_Admin_Settings::add_message( __( 'POS access removed.', 'woocommerce' ) );
		}
		// phpcs:enable
	}
}

new WC_Admin_POS_Staff();
