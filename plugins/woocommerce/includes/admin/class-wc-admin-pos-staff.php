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
	 * Query flag marking a WP user-edit link that came from the staff list, so the
	 * edit screen can offer a way back. Public so the staff table's User column
	 * can set it on its links.
	 */
	public const EDIT_USER_RETURN_PARAM = 'pos_staff_return';

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
		add_action( 'all_admin_notices', array( $this, 'render_back_to_staff_link' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_pick_user_cta' ), 20 );
		add_filter( 'woocommerce_json_search_found_customers', array( $this, 'relabel_picker_search_results' ) );
	}

	/**
	 * On the picker, relabel the submit button to "Edit staff" for existing staff.
	 *
	 * A picked user who already has POS access carries the "already staff" tag on
	 * their search result, so the form will route them to their edit screen — flip
	 * the CTA to match. Attached to wc-enhanced-select (which the picker's
	 * customer-search field already depends on) so jQuery and select2 load first.
	 *
	 * @internal
	 *
	 * @since 11.0.0
	 */
	public function enqueue_pick_user_cta(): void {
		if ( ! $this->is_pos_staff_settings_page() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen check, no state change.
		$edit_param = isset( $_GET['edit-staff'] ) ? sanitize_text_field( wp_unslash( $_GET['edit-staff'] ) ) : '';
		if ( self::EDIT_PICK_USER !== $edit_param ) {
			return;
		}

		wp_enqueue_script( 'wc-enhanced-select' );
		wp_add_inline_script(
			'wc-enhanced-select',
			sprintf(
				'jQuery( function( $ ) {
					var picker = $( "#user_id" ), cta = $( "#pick_pos_staff" );
					if ( ! picker.length || ! cta.length ) { return; }
					var continueLabel = cta.val(), editLabel = %1$s, staffMarker = %2$s;
					picker.on( "select2:select", function( e ) {
						var text = ( e.params.data && e.params.data.text ) || "";
						cta.val( staffMarker && -1 !== text.indexOf( staffMarker ) ? editLabel : continueLabel );
					} );
					picker.on( "select2:unselect select2:clear", function() {
						cta.val( continueLabel );
					} );
				} );',
				wp_json_encode( __( 'Edit staff', 'woocommerce' ) ),
				wp_json_encode( esc_html__( 'Already POS staff', 'woocommerce' ) )
			)
		);
	}

	/**
	 * Render a "Back to staff" link atop the core user screens we deep-link into.
	 *
	 * The Staff list links out to two WP screens: user-new.php (create a POS-only
	 * account, flagged with pos_staff=1) and the user edit screen (review a staff
	 * member, flagged with EDIT_USER_RETURN_PARAM). Give both the same back
	 * affordance as the rest of the POS staff pages. all_admin_notices outputs
	 * near the top of the admin content area, above the page title.
	 *
	 * @internal
	 *
	 * @since 11.0.0
	 */
	public function render_back_to_staff_link(): void {
		global $pagenow;

		if ( ! self::is_enabled() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only screen detection, no state change.
		$from_add  = 'user-new.php' === $pagenow
			&& isset( $_GET[ UserFormIntegration::REQUEST_FLAG_PARAM ] )
			&& '1' === sanitize_text_field( wp_unslash( $_GET[ UserFormIntegration::REQUEST_FLAG_PARAM ] ) );
		$from_edit = in_array( $pagenow, array( 'user-edit.php', 'profile.php' ), true )
			&& isset( $_GET[ self::EDIT_USER_RETURN_PARAM ] )
			&& '1' === sanitize_text_field( wp_unslash( $_GET[ self::EDIT_USER_RETURN_PARAM ] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $from_add && ! $from_edit ) {
			return;
		}

		printf(
			'<p class="wc-pos-staff-back-to-staff" style="margin:1.5em 0 0 2px;"><a href="%1$s" style="text-decoration:none;"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true" style="vertical-align:middle;"></span> %2$s</a></p>',
			esc_url( self::list_url() ),
			esc_html__( 'Back to staff', 'woocommerce' )
		);
	}

	/**
	 * Re-label the picker's customer-search results and tag existing staff.
	 *
	 * WC's customer search labels users by billing name + customer email, both
	 * empty for POS-only accounts (collapsing to "(#18 &ndash; )"). Scoped — by
	 * the picker referer — to this screen only, rebuild each label from the
	 * display name (falling back to login), drop the dash when there's no email,
	 * and append an "Already POS staff" tag for users who already have access, so
	 * both the dropdown and the chosen value make their status clear.
	 *
	 * @internal
	 *
	 * @param array<int|string, string> $found Found users keyed by ID.
	 * @return array<int|string, string>
	 */
	public function relabel_picker_search_results( array $found ): array {
		$referer = (string) wp_get_referer();
		if (
			false === strpos( $referer, 'section=staff' )
			|| false === strpos( $referer, 'edit-staff=' . self::EDIT_PICK_USER )
		) {
			return $found;
		}

		// Plain text, not an HTML span: select2 renders the label as HTML in the
		// dropdown but shows the chosen value's markup literally, so a tag would
		// leak "<span ...>" into the selection.
		$staff_tag = ' — ' . esc_html__( 'Already POS staff', 'woocommerce' );

		$relabeled = array();
		foreach ( $found as $id => $label ) {
			$user = get_userdata( (int) $id );
			if ( ! $user ) {
				$relabeled[ $id ] = $label;
				continue;
			}

			$name = '' !== trim( (string) $user->display_name ) ? $user->display_name : $user->user_login;

			if ( '' !== trim( (string) $user->user_email ) ) {
				$relabeled[ $id ] = sprintf(
					/* translators: 1: user name, 2: user ID, 3: user email. */
					esc_html__( '%1$s (#%2$d &ndash; %3$s)', 'woocommerce' ),
					esc_html( $name ),
					(int) $id,
					esc_html( $user->user_email )
				);
			} else {
				$relabeled[ $id ] = sprintf(
					/* translators: 1: user name, 2: user ID. */
					esc_html__( '%1$s (#%2$d)', 'woocommerce' ),
					esc_html( $name ),
					(int) $id
				);
			}

			if ( POSCapabilities::has_pos_access( (int) $id ) ) {
				$relabeled[ $id ] .= $staff_tag;
			}
		}

		return $relabeled;
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
		$grant_url = self::list_url( array( 'edit-staff' => self::EDIT_PICK_USER ) );

		// Use core's list-header pattern (as on Webhooks / REST API): a normal
		// block <h2> with inline .page-title-action buttons. The standard top
		// margin lines the heading up with the General sub-page's section title.
		echo '<div class="wc-pos-staff-page">';
		echo '<h2 class="wc-table-list-header">'
			. esc_html__( 'Staff', 'woocommerce' )
			. ' <a href="' . esc_url( $add_url ) . '" class="page-title-action">'
			. esc_html__( 'Add new staff', 'woocommerce' ) . '</a>'
			. ' <a href="' . esc_url( $grant_url ) . '" class="page-title-action">'
			. esc_html__( 'Grant access to existing user', 'woocommerce' ) . '</a>'
			. '</h2>';
		echo '<p class="wc-pos-staff-description">';
		esc_html_e( 'Assign a Point of Sale role to a user and set their PIN.', 'woocommerce' );
		echo '</p>';
		$staff_table->display();
		echo '</div>';
	}

	/**
	 * Render the "Grant POS access to existing user" picker page.
	 *
	 * Fronts the regular edit screen: the admin picks a user via the user-search
	 * autocomplete, the form POSTs back to this page, and the action handler
	 * redirects them to ?edit-staff=<id> to finish setup.
	 *
	 * @since 11.0.0
	 */
	private static function pick_user_output(): void {
		$form_action_url = self::list_url( array( 'edit-staff' => self::EDIT_PICK_USER ) );
		$list_url        = self::list_url();

		echo '<div class="wc-pos-staff-page">';
		include __DIR__ . '/settings/views/html-pos-staff-pick-user.php';
		echo '</div>';
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

		// Post back to the same edit URL so a failed save (e.g. PIN collision)
		// re-renders the form pre-filled instead of bouncing to the list view.
		$form_action_url = self::list_url( array( 'edit-staff' => $user_id ) );
		$list_url        = self::list_url();

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

		// Remove is a nonced GET link (WP's list-table convention), so handle it
		// before the POST-only gate below.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified in remove_staff().
		if ( isset( $_GET['remove-pos-staff'] ) ) {
			$this->remove_staff();
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

		// DEBUG ONLY (see html-pos-staff-edit.php): write the target user's POS caps
		// from the per-cap checkbox group, so a single cap can be toggled off to
		// test client behavior. Gated behind WP_DEBUG, matching the form control.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && isset( $_POST['debug_set_pos_caps'] ) ) {
			$this->debug_set_pos_caps();
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

		wp_safe_redirect( self::list_redirect_url( array( 'edit-staff' => $user_id ) ) );
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
	 * DEBUG ONLY — set the target user's POS capabilities from the checkbox group.
	 *
	 * Writes each woocommerce_pos_* cap directly with add_cap()/remove_cap() to
	 * match exactly what was checked, bypassing the preset bundle so a single cap
	 * can be removed to test client behavior when a staff member lacks it. Only
	 * the known caps in all_pos_capabilities() are touched, so an injected cap
	 * name in the POST is ignored. The preset meta is intentionally left as-is
	 * (it may no longer describe the cap set). Reuses the edit form's nonce and
	 * is gated behind WP_DEBUG at both the render and dispatch sites. Remove
	 * before release.
	 */
	private function debug_set_pos_caps(): void {
		check_admin_referer( 'woocommerce-pos-staff-edit', 'woocommerce_pos_staff_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified by check_admin_referer above.
		$user_id = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
		$checked = isset( $_POST['debug_pos_caps'] )
			? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['debug_pos_caps'] ) )
			: array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$user = $user_id ? get_userdata( $user_id ) : false;
		if ( ! $user ) {
			WC_Admin_Settings::add_error( __( 'Invalid user.', 'woocommerce' ) );
			return;
		}

		foreach ( POSCapabilities::all_pos_capabilities() as $cap ) {
			if ( in_array( $cap, $checked, true ) ) {
				$user->add_cap( $cap );
			} else {
				$user->remove_cap( $cap );
			}
		}

		wp_safe_redirect(
			self::list_redirect_url(
				array(
					'edit-staff'       => $user_id,
					'debug_caps_saved' => '1',
				)
			)
		);
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
		check_admin_referer( 'remove-pos-staff' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified by check_admin_referer above.
		$user_id = isset( $_GET['remove-pos-staff'] ) ? absint( wp_unslash( $_GET['remove-pos-staff'] ) ) : 0;
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
	 * Build the staff list URL, optionally with extra query args merged in.
	 *
	 * Single source of truth for the WooCommerce → Settings → Point of Sale →
	 * Staff route, reused by the list redirect and by the edit/pick screens'
	 * back, cancel, and form-action links so the route isn't hand-written in
	 * several places.
	 *
	 * @param array<string, scalar> $extra Extra query args to merge in.
	 * @return string
	 */
	private static function list_url( array $extra = array() ): string {
		return add_query_arg(
			array_merge(
				array(
					'page'    => 'wc-settings',
					'tab'     => 'point-of-sale',
					'section' => 'staff',
				),
				$extra
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Build a redirect URL back to the staff list with the given query args.
	 *
	 * @param array<string, scalar> $extra Extra query args to merge in.
	 * @return string
	 */
	private static function list_redirect_url( array $extra = array() ): string {
		return esc_url_raw( self::list_url( $extra ) );
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

			.woocommerce .wc-pos-staff-page .wc-pos-staff-description {
				margin: 0 0 8px;
				color: #50575e;
			}

			/*
			Centre the action buttons on the heading text. Core only sets
			vertical-align on .page-title-action in its mobile media query, so on
			desktop they sit on the text baseline and 3px high; override both so
			they line up with "Staff".
			*/
			.woocommerce .wc-pos-staff-page .wc-table-list-header .page-title-action {
				vertical-align: middle;
				top: 0;
			}

			/*
			The User row's value is plain text, so it misses the label-centering
			top padding WP 7.0 adds for tall inputs and floats above the label.
			Push the value down to meet the label rather than moving the label,
			which must stay aligned with the role/PIN labels below it.
			*/
			.woocommerce .wc-pos-staff-page #pos-staff-fields .wc-pos-staff-user-row td {
				vertical-align: top;
				padding-top: 20px;
			}

			.wc-wp-version-gte-70 .woocommerce .wc-pos-staff-page #pos-staff-fields .wc-pos-staff-user-row td {
				padding-top: 25px;
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

			/*
			Remove POS access is destructive: use WP/WC's delete-link red instead
			of the default admin link blue.
			*/
			.woocommerce .wc-pos-staff-page .wc-pos-staff-remove-link {
				color: #b32d2e;
			}

			.woocommerce .wc-pos-staff-page .wc-pos-staff-remove-link:hover {
				color: #8a2424;
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

		// DEBUG ONLY — see debug_set_pos_caps(). Remove before release.
		if ( isset( $_GET['debug_caps_saved'] ) ) {
			WC_Admin_Settings::add_message( __( 'Debug: POS capabilities updated.', 'woocommerce' ) );
		}
		// phpcs:enable
	}
}

new WC_Admin_POS_Staff();
