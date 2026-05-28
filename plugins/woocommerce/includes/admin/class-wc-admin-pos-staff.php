<?php
/**
 * WooCommerce Admin POS Staff (actors) Class
 *
 * @package WooCommerce\Admin
 * @since   10.9.0
 */

declare(strict_types=1);

use Automattic\WooCommerce\Internal\POS\Actors\AccessProfileRegistry;
use Automattic\WooCommerce\Internal\POS\POSController;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use Automattic\WooCommerce\Internal\StoreActors\ActorAccessRepository;
use Automattic\WooCommerce\Internal\StoreActors\ActorRepository;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_POS_Staff.
 *
 * wp-admin UI for managing store actors with POS access. Backed by the
 * wc_store_actors and wc_store_actor_access tables (NOT wp_users). Gated
 * behind the `point_of_sale_actors` dev feature flag.
 *
 * Rendered under Settings → Point of Sale → Staff. Supports:
 *   - List active actors with display name, profile, PIN status.
 *   - Add new actor (POS-only, with optional wp_user_id link, profile selector, optional initial PIN).
 *   - Edit actor (display name, profile, status).
 *   - Set / clear PIN.
 *   - Soft-delete actor.
 *
 * @internal Owned by the Point of Sale staff (actors) feature.
 * @since 10.9.0
 */
class WC_Admin_POS_Staff {

	private const NONCE_NEW    = 'wc_pos_actors_new';
	private const NONCE_EDIT   = 'wc_pos_actors_edit';
	private const NONCE_DELETE = 'wc_pos_actors_delete';

	private const NOTICE_TRANSIENT_PREFIX = 'wc_pos_staff_notice_';

	/**
	 * Cached notice for the current page render. Use set_notice() to write —
	 * it can also persist the notice via transient for cross-redirect display.
	 *
	 * @var array{type:string,message:string}|null
	 */
	private static ?array $notice = null;

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
		add_action( 'wp_ajax_wc_pos_actor_check_wp_user_role', array( $this, 'ajax_check_wp_user_role' ) );
	}

	/**
	 * Set the form notice. When $persist is true, the notice is also stored
	 * in a per-user transient so it survives a subsequent wp_safe_redirect.
	 *
	 * @param string $type    'success' or 'error'.
	 * @param string $message Translated, plain-text message.
	 * @param bool   $persist Persist across redirect via transient.
	 * @return void
	 */
	private static function set_notice( string $type, string $message, bool $persist = false ): void {
		self::$notice = array( 'type' => $type, 'message' => $message );
		if ( $persist ) {
			set_transient(
				self::NOTICE_TRANSIENT_PREFIX . get_current_user_id(),
				self::$notice,
				60
			);
		}
	}

	/**
	 * Read a persisted notice from the transient (if any) into the static
	 * cache, then delete it so it only renders once.
	 *
	 * @return void
	 */
	private static function consume_persisted_notice(): void {
		if ( null !== self::$notice ) {
			return;
		}
		$key    = self::NOTICE_TRANSIENT_PREFIX . get_current_user_id();
		$stored = get_transient( $key );
		if ( is_array( $stored ) && isset( $stored['type'], $stored['message'] ) ) {
			delete_transient( $key );
			self::$notice = array(
				'type'    => (string) $stored['type'],
				'message' => (string) $stored['message'],
			);
		}
	}

	/**
	 * admin_notices callback. Restricted to the Staff sub-section so we don't
	 * leak notices onto other wp-admin pages.
	 *
	 * @return void
	 */
	public function render_admin_notices(): void {
		if ( ! self::is_enabled() || ! $this->is_staff_page() ) {
			return;
		}
		self::notices();
	}

	/**
	 * AJAX handler used by the staff form to detect whether a freshly-selected
	 * WP user is an administrator or shop_manager. The client toggles the
	 * access-profile selector vs. the "POS Admin auto-assigned" display
	 * based on the response.
	 *
	 * @return void
	 */
	public function ajax_check_wp_user_role(): void {
		check_ajax_referer( 'wc_pos_actor_check_wp_user_role', '_wpnonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		wp_send_json_success(
			array(
				'is_admin_or_shop_manager' => self::is_admin_or_shop_manager( $user_id ),
			)
		);
	}

	/**
	 * Whether the staff actors admin UI is currently enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( 'point_of_sale' )
			&& FeaturesUtil::feature_is_enabled( POSController::FEATURE_FLAG );
	}

	/**
	 * Output notices set by this page's POST handlers. Reads from the static
	 * cache first; falls back to the per-user transient so notices set right
	 * before a redirect (e.g. successful create → redirect to edit) still
	 * display on the next request.
	 *
	 * @return void
	 */
	public static function notices(): void {
		self::consume_persisted_notice();
		if ( null === self::$notice ) {
			return;
		}
		$class = 'error' === self::$notice['type'] ? 'notice-error' : 'notice-success';
		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( self::$notice['message'] )
		);
		self::$notice = null;
	}

	/**
	 * Page output entry point. Routes to list / new / edit views based on query args.
	 *
	 * @return void
	 */
	public static function page_output(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage POS staff.', 'woocommerce' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['new-actor'] ) ) {
			self::render_form( null );
			return;
		}

		if ( isset( $_GET['edit-actor'] ) ) {
			$actor_id = absint( $_GET['edit-actor'] );
			self::render_form( $actor_id );
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		self::render_list();
	}

	/**
	 * Render the list of actors.
	 *
	 * @return void
	 */
	private static function render_list(): void {
		$actor_repo  = wc_get_container()->get( ActorRepository::class );
		$access_repo = wc_get_container()->get( ActorAccessRepository::class );
		$profiles    = wc_get_container()->get( AccessProfileRegistry::class );

		$actors = $actor_repo->list_active( 200 );
		$new_url    = add_query_arg(
			array(
				'page'       => 'wc-settings',
				'tab'        => 'point-of-sale',
				'section'    => 'staff',
				'new-actor'  => '1',
			),
			admin_url( 'admin.php' )
		);

		echo '<div class="wc-pos-staff-page">';
		echo '<h2>' . esc_html__( 'POS staff', 'woocommerce' ) . '</h2>';
		echo '<p>' . esc_html__( 'Manage Point of Sale staff who sign in to the POS app with a PIN. Staff are stored independently of WordPress users; any existing WordPress user can optionally be linked.', 'woocommerce' ) . '</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( $new_url ) . '">' . esc_html__( 'Add POS staff', 'woocommerce' ) . '</a></p>';

		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Name', 'woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'Profile', 'woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'Linked WP user', 'woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'PIN', 'woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'woocommerce' ) . '</th>';
		echo '</tr></thead><tbody>';

		if ( empty( $actors ) ) {
			echo '<tr><td colspan="6">' . esc_html__( 'No POS staff yet.', 'woocommerce' ) . '</td></tr>';
		} else {
			foreach ( $actors as $actor ) {
				$actor_id = (int) $actor['actor_id'];
				$access   = $access_repo->get_for_actor( $actor_id );
				$profile  = $access ? $profiles->get( (string) $access['access_profile_key'] ) : null;
				$wp_user  = ! empty( $actor['wp_user_id'] ) ? get_userdata( (int) $actor['wp_user_id'] ) : null;
				$has_pin  = $access && ! empty( $access['credential_hash'] );

				$edit_url   = add_query_arg(
					array(
						'page'       => 'wc-settings',
						'tab'        => 'point-of-sale',
						'section'    => 'staff',
						'edit-actor' => $actor_id,
					),
					admin_url( 'admin.php' )
				);
				$delete_url = wp_nonce_url(
					add_query_arg(
						array(
							'page'         => 'wc-settings',
							'tab'          => 'point-of-sale',
							'section'      => 'staff',
							'delete-actor' => $actor_id,
						),
						admin_url( 'admin.php' )
					),
					self::NONCE_DELETE,
					'_wpnonce'
				);

				echo '<tr>';
				echo '<td>' . esc_html( (string) $actor['display_name'] ) . '</td>';
				echo '<td>' . esc_html( $profile ? (string) $profile['name'] : ( $access ? (string) $access['access_profile_key'] : '—' ) ) . '</td>';
				echo '<td>' . ( $wp_user ? esc_html( $wp_user->user_login ) : '—' ) . '</td>';
				echo '<td>' . ( $has_pin ? esc_html__( 'Set', 'woocommerce' ) : esc_html__( 'Not set', 'woocommerce' ) ) . '</td>';
				echo '<td>' . esc_html( (string) $actor['status'] ) . '</td>';
				echo '<td>';
				echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'woocommerce' ) . '</a> | ';
				echo '<a href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete this POS staff member?', 'woocommerce' ) ) . '\');">' . esc_html__( 'Delete', 'woocommerce' ) . '</a>';
				echo '</td>';
				echo '</tr>';
			}
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	/**
	 * Render the new/edit form. When $actor_id is null, renders the "new" form.
	 *
	 * @param int|null $actor_id Actor ID to edit, or null for new.
	 * @return void
	 */
	private static function render_form( ?int $actor_id ): void {
		$actor_repo  = wc_get_container()->get( ActorRepository::class );
		$access_repo = wc_get_container()->get( ActorAccessRepository::class );
		$profiles    = wc_get_container()->get( AccessProfileRegistry::class );
		$pin_service = wc_get_container()->get( POSPinService::class );

		$actor   = $actor_id ? $actor_repo->get_by_id( $actor_id ) : null;
		$access  = $actor_id ? $access_repo->get_for_actor( $actor_id ) : null;
		$has_pin = $actor_id ? $pin_service->has_pin( $actor_id ) : false;

		if ( $actor_id && null === $actor ) {
			wp_die( esc_html__( 'POS staff member not found.', 'woocommerce' ) );
		}

		$back_url = add_query_arg(
			array(
				'page'    => 'wc-settings',
				'tab'     => 'point-of-sale',
				'section' => 'staff',
			),
			admin_url( 'admin.php' )
		);

		$is_new = null === $actor_id;
		$nonce  = $is_new ? self::NONCE_NEW : self::NONCE_EDIT;

		echo '<div class="wc-pos-staff-page">';
		echo '<h2>' . esc_html( $is_new ? __( 'Add POS staff', 'woocommerce' ) : __( 'Edit POS staff', 'woocommerce' ) ) . '</h2>';
		echo '<p><a href="' . esc_url( $back_url ) . '">&larr; ' . esc_html__( 'Back to list', 'woocommerce' ) . '</a></p>';

		echo '<form method="post" action="">';
		wp_nonce_field( $nonce, '_wpnonce' );

		if ( ! $is_new ) {
			echo '<input type="hidden" name="actor_id" value="' . esc_attr( (string) $actor_id ) . '" />';
		}

		echo '<table class="form-table"><tbody>';

		self::field_text( 'display_name', __( 'Display name', 'woocommerce' ), $actor['display_name'] ?? '', true );
		self::field_text( 'first_name', __( 'First name', 'woocommerce' ), $actor['first_name'] ?? '' );
		self::field_text( 'last_name', __( 'Last name', 'woocommerce' ), $actor['last_name'] ?? '' );
		self::field_text( 'email', __( 'Email (optional)', 'woocommerce' ), $actor['email'] ?? '', false, 'email' );
		self::field_user_search( (int) ( $actor['wp_user_id'] ?? 0 ) );

		$linked_is_admin = self::is_admin_or_shop_manager( (int) ( $actor['wp_user_id'] ?? 0 ) );
		$current_profile = $access['access_profile_key'] ?? AccessProfileRegistry::PROFILE_CASHIER;
		$admin_profile   = $profiles->get( AccessProfileRegistry::PROFILE_ADMIN );
		$admin_label     = null !== $admin_profile ? (string) $admin_profile['name'] : 'POS Admin';

		// Selector row — shown when the linked user is not admin/shop_manager (or no link).
		echo '<tr class="wc-pos-profile-selector-row" style="' . ( $linked_is_admin ? 'display:none;' : '' ) . '">';
		echo '<th><label for="access_profile_key">' . esc_html__( 'Access profile', 'woocommerce' ) . '</label></th><td>';
		echo '<select id="access_profile_key" name="access_profile_key">';
		foreach ( array( AccessProfileRegistry::PROFILE_CASHIER, AccessProfileRegistry::PROFILE_MANAGER ) as $key ) {
			$profile = $profiles->get( $key );
			if ( null === $profile ) {
				continue;
			}
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $current_profile, $key, false ) . '>'
				. esc_html( (string) $profile['name'] ) . '</option>';
		}
		echo '</select>';
		echo '</td></tr>';

		// Auto-assigned row — shown when the linked user is admin/shop_manager.
		echo '<tr class="wc-pos-profile-admin-row" style="' . ( $linked_is_admin ? '' : 'display:none;' ) . '">';
		echo '<th>' . esc_html__( 'Access profile', 'woocommerce' ) . '</th><td>';
		echo '<strong>' . esc_html( $admin_label ) . '</strong>';
		echo '<p class="description">' . esc_html__( 'Linked to an administrator or shop manager — POS Admin access is assigned automatically.', 'woocommerce' ) . '</p>';
		echo '</td></tr>';

		self::field_pin( $has_pin, $is_new );

		if ( ! $is_new ) {
			echo '<tr><th><label for="status">' . esc_html__( 'Status', 'woocommerce' ) . '</label></th><td>';
			echo '<select id="status" name="status">';
			foreach ( array( ActorRepository::STATUS_ACTIVE, ActorRepository::STATUS_INACTIVE ) as $s ) {
				echo '<option value="' . esc_attr( $s ) . '"' . selected( $actor['status'] ?? '', $s, false ) . '>' . esc_html( $s ) . '</option>';
			}
			echo '</select></td></tr>';
		}

		echo '</tbody></table>';

		echo '<p class="submit">';
		submit_button( $is_new ? __( 'Create POS staff', 'woocommerce' ) : __( 'Save changes', 'woocommerce' ), 'primary', 'wc_pos_actor_submit', false );
		if ( ! $is_new && $has_pin ) {
			echo ' ';
			submit_button( __( 'Remove PIN', 'woocommerce' ), 'delete', 'wc_pos_actor_remove_pin', false );
		}
		echo '</p>';
		echo '</form>';

		self::print_form_toggle_script();

		echo '</div>';
	}

	/**
	 * Print the inline script that listens for changes on the wp_user_id
	 * select (wc-customer-search) and toggles between the access-profile
	 * selector and the "POS Admin auto-assigned" notice based on the chosen
	 * user's role. The result is fetched via a lightweight admin-ajax call
	 * to keep the role lookup out of the page payload.
	 *
	 * @return void
	 */
	private static function print_form_toggle_script(): void {
		$nonce = wp_create_nonce( 'wc_pos_actor_check_wp_user_role' );
		?>
		<script type="text/javascript">
		(function ($) {
			$(function () {
				var $userSelect    = $('#wp_user_id');
				var $selectorRow   = $('.wc-pos-profile-selector-row');
				var $adminRow      = $('.wc-pos-profile-admin-row');
				if (!$userSelect.length) {
					return;
				}

				function applyState(isAdmin) {
					if (isAdmin) {
						$selectorRow.hide();
						$adminRow.show();
					} else {
						$selectorRow.show();
						$adminRow.hide();
					}
				}

				$userSelect.on('change', function () {
					var userId = parseInt($userSelect.val(), 10);
					if (!userId) {
						applyState(false);
						return;
					}
					$.post(ajaxurl, {
						action: 'wc_pos_actor_check_wp_user_role',
						_wpnonce: <?php echo wp_json_encode( $nonce ); ?>,
						user_id: userId
					}).done(function (response) {
						if (response && response.success) {
							applyState(!!response.data.is_admin_or_shop_manager);
						}
					});
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Render the "Linked WordPress user" row as a wc-customer-search enhanced
	 * select. WC's admin assets pick the class up and wire it to the
	 * `woocommerce_json_search_customers` ajax endpoint with select2-style
	 * search by name / email / login.
	 *
	 * @param int $current_user_id Currently linked user ID (0 when unlinked).
	 * @return void
	 */
	private static function field_user_search( int $current_user_id ): void {
		$pre_selected = '';
		if ( $current_user_id > 0 ) {
			$user = get_userdata( $current_user_id );
			if ( $user ) {
				$pre_selected = sprintf(
					'<option value="%1$d" selected="selected">%2$s</option>',
					$current_user_id,
					esc_html( sprintf( '%s (#%d – %s)', $user->display_name, $user->ID, $user->user_email ) )
				);
			}
		}

		echo '<tr><th><label for="wp_user_id">' . esc_html__( 'Linked WordPress user (optional)', 'woocommerce' ) . '</label></th><td>';
		echo '<select id="wp_user_id" name="wp_user_id" class="wc-customer-search" style="width:50%;" '
			. 'data-placeholder="' . esc_attr__( 'Search by name, login, or email&hellip;', 'woocommerce' ) . '" '
			. 'data-allow_clear="true" '
			. 'data-action="woocommerce_json_search_customers">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $pre_selected is escaped above.
		echo $pre_selected;
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Link this POS staff member to any existing WordPress user. Leave blank for POS-only staff with no WordPress account.', 'woocommerce' ) . '</p>';
		echo '</td></tr>';
	}

	/**
	 * Render the inline PIN row. Required when creating a new staff member
	 * (a staff member without a PIN can't operate POS, so there's no value
	 * in creating one). Optional on edit — empty means "keep current PIN".
	 *
	 * @param bool $has_pin Whether the staff member currently has a PIN set.
	 * @param bool $is_new  Whether this is the create form.
	 * @return void
	 */
	private static function field_pin( bool $has_pin, bool $is_new ): void {
		$required_attr = $is_new ? ' required' : '';
		$label         = $is_new
			? __( '4-digit PIN', 'woocommerce' )
			: __( '4-digit PIN', 'woocommerce' );

		echo '<tr><th><label for="pin">' . esc_html( $label ) . ( $is_new ? ' <span class="description">*</span>' : '' ) . '</label></th><td>';
		echo '<input type="text" inputmode="numeric" pattern="\\d{4}" maxlength="4" id="pin" name="pin" autocomplete="off"' . $required_attr . ' />';
		echo '<p class="description">';
		if ( $is_new ) {
			esc_html_e( 'Required. Enter a 4-digit PIN the staff member will use to sign in to the POS app.', 'woocommerce' );
		} elseif ( $has_pin ) {
			esc_html_e( 'A PIN is currently set. Enter a new value to replace it, or leave blank to keep it.', 'woocommerce' );
		} else {
			esc_html_e( 'No PIN is currently set. Enter a 4-digit PIN to allow this staff member to sign in to the POS app.', 'woocommerce' );
		}
		echo '</p>';
		echo '</td></tr>';
	}

	/**
	 * Decide which access profile a staff member should be assigned.
	 *
	 * If linked to an administrator or shop_manager, the POS Admin profile is
	 * forced (the form hides the selector in this case). Otherwise, the
	 * profile key from POST is used — restricted to the two selectable keys
	 * (cashier, manager) so a hand-crafted request can't sneak `pos_admin`
	 * in without the role check.
	 *
	 * @param int $wp_user_id Linked WP user ID (0 for POS-only staff).
	 * @return string Profile key.
	 */
	private static function resolve_access_profile_key( int $wp_user_id ): string {
		if ( self::is_admin_or_shop_manager( $wp_user_id ) ) {
			return AccessProfileRegistry::PROFILE_ADMIN;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonces are checked in action_create / action_update before this is called.
		$submitted = isset( $_POST['access_profile_key'] ) ? sanitize_text_field( wp_unslash( $_POST['access_profile_key'] ) ) : '';
		if ( in_array( $submitted, array( AccessProfileRegistry::PROFILE_CASHIER, AccessProfileRegistry::PROFILE_MANAGER ), true ) ) {
			return $submitted;
		}

		// Default to cashier when nothing valid was submitted.
		return AccessProfileRegistry::PROFILE_CASHIER;
	}

	/**
	 * Whether the given WP user holds the administrator or shop_manager role.
	 * Used to decide whether to auto-assign the POS Admin access profile.
	 *
	 * @param int $wp_user_id WordPress user ID (0 means no link).
	 * @return bool
	 */
	private static function is_admin_or_shop_manager( int $wp_user_id ): bool {
		if ( $wp_user_id <= 0 ) {
			return false;
		}
		$user = get_userdata( $wp_user_id );
		if ( ! $user ) {
			return false;
		}
		$roles = (array) $user->roles;
		return in_array( 'administrator', $roles, true ) || in_array( 'shop_manager', $roles, true );
	}

	/**
	 * Helper to render a labeled text field row.
	 *
	 * @param string $name     Field name (and id).
	 * @param string $label    Label.
	 * @param string $value    Current value.
	 * @param bool   $required Whether required.
	 * @param string $type     HTML input type.
	 * @return void
	 */
	private static function field_text( string $name, string $label, $value, bool $required = false, string $type = 'text' ): void {
		printf(
			'<tr><th><label for="%1$s">%2$s</label></th><td><input type="%3$s" id="%1$s" name="%1$s" value="%4$s"%5$s /></td></tr>',
			esc_attr( $name ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( (string) $value ),
			$required ? ' required' : ''
		);
	}

	/**
	 * POST handler. Routes to create / update / delete / set-pin / clear-pin.
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		if ( ! self::is_enabled() ) {
			return;
		}
		if ( ! $this->is_staff_page() ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Delete (GET with nonce).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['delete-actor'] ) ) {
			$this->action_delete();
			return;
		}

		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '' ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['wc_pos_actor_remove_pin'] ) ) {
			$actor_id = isset( $_POST['actor_id'] ) ? absint( $_POST['actor_id'] ) : 0;
			if ( $actor_id > 0 ) {
				$this->action_remove_pin( $actor_id );
			}
			return;
		}
		if ( isset( $_POST['wc_pos_actor_submit'] ) ) {
			$actor_id = isset( $_POST['actor_id'] ) ? absint( $_POST['actor_id'] ) : 0;
			if ( $actor_id > 0 ) {
				$this->action_update( $actor_id );
			} else {
				$this->action_create();
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Whether the current request is on the Staff sub-section.
	 *
	 * @return bool
	 */
	private function is_staff_page(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['page'], $_GET['tab'], $_GET['section'] )
			&& 'wc-settings' === $_GET['page']
			&& 'point-of-sale' === $_GET['tab']
			&& 'staff' === $_GET['section'];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Create handler.
	 *
	 * @return void
	 */
	private function action_create(): void {
		check_admin_referer( self::NONCE_NEW );

		$display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
		$wp_user_id   = ! empty( $_POST['wp_user_id'] ) ? absint( $_POST['wp_user_id'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is validated by check_admin_referer above.
		$pin = isset( $_POST['pin'] ) ? (string) wp_unslash( $_POST['pin'] ) : '';

		if ( '' === $display_name ) {
			self::$notice = array( 'type' => 'error', 'message' => __( 'Display name is required.', 'woocommerce' ) );
			return;
		}

		$pin_service = wc_get_container()->get( POSPinService::class );
		if ( ! $pin_service->validate_pin_format( $pin ) ) {
			self::$notice = array( 'type' => 'error', 'message' => __( 'A 4-digit PIN is required to create a POS staff member.', 'woocommerce' ) );
			return;
		}

		$access_profile_key = self::resolve_access_profile_key( $wp_user_id );
		$profiles           = wc_get_container()->get( AccessProfileRegistry::class );
		if ( ! $profiles->exists( $access_profile_key ) ) {
			self::$notice = array( 'type' => 'error', 'message' => __( 'Unknown access profile.', 'woocommerce' ) );
			return;
		}

		$actor_repo  = wc_get_container()->get( ActorRepository::class );
		$access_repo = wc_get_container()->get( ActorAccessRepository::class );

		$actor_id = $actor_repo->insert(
			array(
				'display_name'       => $display_name,
				'first_name'         => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : null,
				'last_name'          => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : null,
				'email'              => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : null,
				'wp_user_id'         => $wp_user_id > 0 ? $wp_user_id : null,
				'created_by_user_id' => get_current_user_id(),
				'updated_by_user_id' => get_current_user_id(),
			)
		);
		if ( 0 === $actor_id ) {
			self::$notice = array( 'type' => 'error', 'message' => __( 'Failed to create POS staff member.', 'woocommerce' ) );
			return;
		}

		$access_repo->insert(
			array(
				'actor_id'           => $actor_id,
				'access_profile_key' => $access_profile_key,
				'created_by_user_id' => get_current_user_id(),
				'updated_by_user_id' => get_current_user_id(),
			)
		);

		$result = $pin_service->set_pin( $actor_id, $pin );
		if ( is_wp_error( $result ) ) {
			// Atomic create: roll back the half-created staff member so the merchant
			// can retry the whole submission with a different PIN.
			$access_repo->delete_all_for_actor( $actor_id );
			$actor_repo->delete( $actor_id );
			self::set_notice( 'error', $result->get_error_message() );
			return;
		}

		self::set_notice( 'success', __( 'POS staff member created.', 'woocommerce' ), true );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'wc-settings',
					'tab'        => 'point-of-sale',
					'section'    => 'staff',
					'edit-actor' => $actor_id,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Update handler.
	 *
	 * @param int $actor_id Actor ID.
	 * @return void
	 */
	private function action_update( int $actor_id ): void {
		check_admin_referer( self::NONCE_EDIT );

		$actor_repo  = wc_get_container()->get( ActorRepository::class );
		$access_repo = wc_get_container()->get( ActorAccessRepository::class );
		$profiles    = wc_get_container()->get( AccessProfileRegistry::class );

		$existing = $actor_repo->get_by_id( $actor_id );
		if ( null === $existing ) {
			self::$notice = array( 'type' => 'error', 'message' => __( 'POS staff member not found.', 'woocommerce' ) );
			return;
		}

		$wp_user_id = ! empty( $_POST['wp_user_id'] ) ? absint( $_POST['wp_user_id'] ) : 0;

		$update = array(
			'display_name'       => isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : $existing['display_name'],
			'first_name'         => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : null,
			'last_name'          => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : null,
			'email'              => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : null,
			'wp_user_id'         => $wp_user_id > 0 ? $wp_user_id : null,
			'status'             => isset( $_POST['status'] ) && ActorRepository::STATUS_INACTIVE === $_POST['status'] ? ActorRepository::STATUS_INACTIVE : ActorRepository::STATUS_ACTIVE,
			'updated_by_user_id' => get_current_user_id(),
		);
		$actor_repo->update( $actor_id, $update );

		$access_profile_key = self::resolve_access_profile_key( $wp_user_id );
		if ( $profiles->exists( $access_profile_key ) ) {
			$access = $access_repo->get_for_actor( $actor_id );
			if ( null === $access ) {
				$access_repo->insert(
					array(
						'actor_id'           => $actor_id,
						'access_profile_key' => $access_profile_key,
						'created_by_user_id' => get_current_user_id(),
						'updated_by_user_id' => get_current_user_id(),
					)
				);
			} else {
				$access_repo->update(
					(int) $access['access_id'],
					array(
						'access_profile_key' => $access_profile_key,
						'updated_by_user_id' => get_current_user_id(),
					)
				);
			}
		}

		// Optional inline PIN — empty means "keep current PIN".
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is validated by check_admin_referer above.
		$pin = isset( $_POST['pin'] ) ? (string) wp_unslash( $_POST['pin'] ) : '';
		if ( '' !== $pin ) {
			$pin_service = wc_get_container()->get( POSPinService::class );
			$result      = $pin_service->set_pin( $actor_id, $pin );
			if ( is_wp_error( $result ) ) {
				self::$notice = array( 'type' => 'error', 'message' => $result->get_error_message() );
				return;
			}
		}

		self::$notice = array( 'type' => 'success', 'message' => __( 'POS staff member updated.', 'woocommerce' ) );
	}

	/**
	 * Delete handler (soft-delete).
	 *
	 * @return void
	 */
	private function action_delete(): void {
		check_admin_referer( self::NONCE_DELETE );

		$actor_id = isset( $_GET['delete-actor'] ) ? absint( $_GET['delete-actor'] ) : 0;
		if ( $actor_id <= 0 ) {
			return;
		}

		$actor_repo = wc_get_container()->get( ActorRepository::class );
		$actor_repo->soft_delete( $actor_id );

		self::set_notice( 'success', __( 'POS staff member deleted.', 'woocommerce' ), true );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wc-settings',
					'tab'     => 'point-of-sale',
					'section' => 'staff',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Remove the PIN from a staff member. Shares the edit-form nonce since
	 * the "Remove PIN" button lives inside the main edit form.
	 *
	 * @param int $actor_id Actor ID.
	 * @return void
	 */
	private function action_remove_pin( int $actor_id ): void {
		check_admin_referer( self::NONCE_EDIT );

		$pin_service = wc_get_container()->get( POSPinService::class );
		$pin_service->delete_pin( $actor_id );

		self::$notice = array( 'type' => 'success', 'message' => __( 'PIN removed.', 'woocommerce' ) );
	}
}

return new WC_Admin_POS_Staff();
