<?php
/**
 * Meta box to edit and add custom meta values for an order.
 */

namespace Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes;

use Automattic\WooCommerce\Internal\DataStores\CustomMetaDataStore;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStoreMeta;
use WC_Order;
use WP_Ajax_Response;

/**
 * Class CustomMetaBox.
 */
class CustomMetaBox {

	/**
	 * Update nonce shared among different meta rows.
	 *
	 * @var string
	 */
	private $update_nonce;

	/**
	 * Helper method to get formatted meta data array with proper keys. This can be directly fed to `list_meta()` method.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return array Meta data.
	 */
	private function get_formatted_order_meta_data( \WC_Order $order ) {
		$metadata         = $order->get_meta_data();
		$metadata_to_list = array();
		foreach ( $metadata as $meta ) {
			$data = $meta->get_data();
			if ( is_protected_meta( $data['key'], 'order' ) ) {
				continue;
			}
			$metadata_to_list[] = array(
				'meta_id'    => $data['id'],
				'meta_key'   => $data['key'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- False positive, not a meta query.
				'meta_value' => maybe_serialize( $data['value'] ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- False positive, not a meta query.
			);
		}
		return $metadata_to_list;
	}

	/**
	 * Renders the meta box to manage custom meta.
	 *
	 * @param \WP_Post|\WC_Order $order_or_post Post or order object that we are rendering for.
	 */
	public function output( $order_or_post ) {
		if ( is_a( $order_or_post, \WP_Post::class ) ) {
			$order = wc_get_order( $order_or_post );
		} else {
			$order = $order_or_post;
		}
		$this->render_custom_meta_form( $this->get_formatted_order_meta_data( $order ), $order );
	}

	/**
	 * Helper method to render layout and actual HTML
	 *
	 * @param array     $metadata_to_list List of metadata to render.
	 * @param \WC_Order $order Order object.
	 */
	private function render_custom_meta_form( array $metadata_to_list, \WC_Order $order ) {
		?>
		<div id="postcustomstuff">
			<div id="ajax-response"></div>
			<?php
			list_meta( $metadata_to_list );
			$this->render_meta_form( $order );
			?>
		</div>
		<p>
			<?php
			printf(
				/* translators: 1: opening documentation tag 2: closing documentation tag. */
				esc_html( __( 'Custom fields can be used to add extra metadata to an order that you can %1$suse in your theme%2$s.', 'woocommerce' ) ),
				'<a href="' . esc_attr__( 'https://wordpress.org/support/article/custom-fields/', 'woocommerce' ) . '">',
				'</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Compute keys to display in autofill when adding new meta key entry in custom meta box.
	 * Currently, returns empty keys, will be implemented after caching is merged.
	 *
	 * @param mixed              $deprecated Unused argument. For backwards compatibility.
	 * @param \WP_Post|\WC_Order $order      Order object.
	 *
	 * @return array Array of keys to display in autofill.
	 */
	public function order_meta_keys_autofill( $deprecated, $order ) {
		if ( ! is_a( $order, \WC_Order::class ) ) {
			return array();
		}

		/**
		 * Filters values for the meta key dropdown in the Custom Fields meta box.
		 *
		 * Compatibility filter for `postmeta_form_keys` filter.
		 *
		 * @since 6.9.0
		 *
		 * @param array|null $keys Pre-defined meta keys to be used in place of a postmeta query. Default null.
		 * @param \WC_Order  $order The current post object.
		 */
		$keys = apply_filters( 'postmeta_form_keys', null, $order );
		if ( null === $keys || ! is_array( $keys ) ) {
			/**
			 * Compatibility filter for 'postmeta_form_limit', which filters the number of custom fields to retrieve
			 * for the drop-down in the Custom Fields meta box.
			 *
			 * @since 8.8.0
			 *
			 * @param int $limit Number of custom fields to retrieve. Default 30.
			 */
			$limit = apply_filters( 'postmeta_form_limit', 30 );
			$keys  = wc_get_container()->get( OrdersTableDataStoreMeta::class )->get_meta_keys( $limit );
		}

		if ( $keys ) {
			natcasesort( $keys );
		}

		return $keys;
	}

	/**
	 * Reimplementation of WP core's `meta_form` function. Renders meta form box.
	 *
	 * @param \WC_Order $order WC_Order object.
	 *
	 * @return void
	 */
	public function render_meta_form( \WC_Order $order ) : void {
		$meta_key_input_id = 'metakeyselect';

		$keys = $this->order_meta_keys_autofill( null, $order );
		?>
		<p><strong><?php esc_html_e( 'Add New Custom Field:', 'woocommerce' ); ?></strong></p>
		<table id="newmeta">
			<thead>
			<tr>
				<th class="left"><label for="<?php echo esc_attr( $meta_key_input_id ); ?>"><?php esc_html_e( 'Name', 'woocommerce' ); ?></label></th>
				<th><label for="metavalue"><?php esc_html_e( 'Value', 'woocommerce' ); ?></label></th>
			</tr>
			</thead>

			<tbody>
			<tr>
				<td id="newmetaleft" class="left">
					<?php if ( $keys ) { ?>
						<select id="metakeyselect" name="metakeyselect">
							<option value="#NONE#"><?php esc_html_e( '&mdash; Select &mdash;', 'woocommerce' ); ?></option>
							<?php
							foreach ( $keys as $key ) {
								if ( is_protected_meta( $key, 'post' ) || ! current_user_can( 'edit_others_shop_orders' ) ) {
									continue;
								}
								echo "\n<option value='" . esc_attr( $key ) . "'>" . esc_html( $key ) . '</option>';
							}
							?>
						</select>
						<input class="hidden" type="text" id="metakeyinput" name="metakeyinput" value="" aria-label="<?php esc_attr_e( 'New custom field name', 'woocommerce' ); ?>" />
						<button type="button" id="newmeta-button" class="button button-small hide-if-no-js" onclick="jQuery('#metakeyinput, #metakeyselect, #enternew, #cancelnew').toggleClass('hidden');jQuery('#metakeyinput, #metakeyselect').filter(':visible').trigger('focus');">
						<span id="enternew"><?php esc_html_e( 'Enter new', 'woocommerce' ); ?></span>
						<span id="cancelnew" class="hidden"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></span>
					<?php } else { ?>
						<input type="text" id="metakeyinput" name="metakeyinput" value="" />
					<?php } ?>
				</td>
				<td><textarea id="metavalue" name="metavalue" rows="2" cols="25"></textarea>
				<?php wp_nonce_field( 'add-meta', '_ajax_nonce-add-meta', false ); ?>
				</td>
			</tr>
			</tbody>
		</table>

		<div class="submit add-custom-field">
			<?php
			submit_button(
				__( 'Add Custom Field', 'woocommerce' ),
				'',
				'addmeta',
				false,
				array(
					'id'            => 'newmeta-submit',
					'data-wp-lists' => 'add:the-list:newmeta',
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Helper method to verify order edit permissions.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return ?WC_Order WC_Order object if the user can edit the order, die otherwise.
	 */
	private function verify_order_edit_permission_for_ajax( int $order_id ): ?WC_Order {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_others_shop_orders' ) ) {
			wp_send_json_error( 'missing_capabilities' );
			wp_die();
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( 'invalid_order_id' );
			wp_die();
		}
		return $order;
	}

	/**
	 * Reimplementation of WP core's `wp_ajax_add_meta` method to support order custom meta updates with custom tables.
	 */
	public function add_meta_ajax() {
		if ( ! check_ajax_referer( 'add-meta', '_ajax_nonce-add-meta' ) ) {
			wp_send_json_error( 'invalid_nonce' );
			wp_die();
		}

		$order_id = (int) $_POST['order_id'] ?? 0;
		$order    = $this->verify_order_edit_permission_for_ajax( $order_id );

		$select_meta_key = trim( sanitize_text_field( wp_unslash( $_POST['metakeyselect'] ?? '' ) ) );
		$input_meta_key  = trim( sanitize_text_field( wp_unslash( $_POST['metakeyinput'] ?? '' ) ) );

		if ( empty( $_POST['meta'] ) && in_array( $select_meta_key, array( '', '#NONE#' ), true ) && ! $input_meta_key ) {
			wp_die( 1 );
		}

		if ( ! empty( $_POST['meta'] ) ) { // update.
			$meta = wp_unslash( $_POST['meta'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitization done below in array_walk.
			$this->handle_update_meta( $order, $meta );
		} else { // add meta.
			$meta_value = sanitize_text_field( wp_unslash( $_POST['metavalue'] ?? '' ) );
			$meta_key   = $input_meta_key ? $input_meta_key : $select_meta_key;
			$this->handle_add_meta( $order, $meta_key, $meta_value );
		}
	}

	/**
	 * Part of WP Core's `wp_ajax_add_meta`. This is re-implemented to support updating meta for custom tables.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $meta_key Meta key.
	 * @param string   $meta_value Meta value.
	 *
	 * @return void
	 */
	private function handle_add_meta( WC_Order $order, string $meta_key, string $meta_value ) {
		$count = 0;
		if ( is_protected_meta( $meta_key ) ) {
			wp_send_json_error( 'protected_meta' );
			wp_die();
		}
		$metas_for_current_key = wp_list_filter( $order->get_meta_data(), array( 'key' => $meta_key ) );
		$meta_ids              = wp_list_pluck( $metas_for_current_key, 'id' );
		$order->add_meta_data( $meta_key, $meta_value );
		$order->save_meta_data();
		$metas_for_current_key_with_new = wp_list_filter( $order->get_meta_data(), array( 'key' => $meta_key ) );
		$meta_id                        = 0;
		$new_meta_ids                   = wp_list_pluck( $metas_for_current_key_with_new, 'id' );
		$new_meta_ids                   = array_values( array_diff( $new_meta_ids, $meta_ids ) );
		if ( count( $new_meta_ids ) > 0 ) {
			$meta_id = $new_meta_ids[0];
		}
		$response = new WP_Ajax_Response(
			array(
				'what'     => 'meta',
				'id'       => $meta_id,
				'data'     => $this->list_meta_row(
					array(
						'meta_id'    => $meta_id,
						'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- false positive, not a meta query.
						'meta_value' => $meta_value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- false positive, not a meta query.
					),
					$count
				),
				'position' => 1,
			)
		);
		$response->send();
	}

	/**
	 * Handles updating metadata.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $meta Meta object to update.
	 *
	 * @return void
	 */
	private function handle_update_meta( WC_Order $order, array $meta ) {
		if ( ! is_array( $meta ) ) {
			wp_send_json_error( 'invalid_meta' );
			wp_die();
		}
		array_walk( $meta, 'sanitize_text_field' );
		$mid = (int) key( $meta );
		if ( ! $mid ) {
			wp_send_json_error( 'invalid_meta_id' );
			wp_die();
		}
		$key   = $meta[ $mid ]['key'];
		$value = $meta[ $mid ]['value'];
		if ( is_protected_meta( $key ) ) {
			wp_send_json_error( 'protected_meta' );
			wp_die();
		}
		if ( '' === trim( $key ) ) {
			wp_send_json_error( 'invalid_meta_key' );
			wp_die();
		}

		$count = 0;
		$order->update_meta_data( $key, $value, $mid );
		$order->save_meta_data();
		$response = new WP_Ajax_Response(
			array(
				'what'     => 'meta',
				'id'       => $mid,
				'old_id'   => $mid,
				'data'     => $this->list_meta_row(
					array(
						'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- false positive, not a meta query.
						'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- false positive, not a meta query.
						'meta_id'    => $mid,
					),
					$count
				),
				'position' => 0,
			)
		);
		$response->send();
	}

	/**
	 * Outputs a single row of public meta data in the Custom Fields meta box.
	 *
	 * @since 2.5.0
	 *
	 * @param array $entry Meta entry.
	 * @param int   $count Sequence number of meta entries.
	 * @return string
	 */
	private function list_meta_row( array $entry, int &$count ) : string {
		if ( is_protected_meta( $entry['meta_key'], 'post' ) ) {
			return '';
		}

		if ( ! $this->update_nonce ) {
			$this->update_nonce = wp_create_nonce( 'add-meta' );
		}

		$r = '';
		++ $count;

		if ( is_serialized( $entry['meta_value'] ) ) {
			if ( is_serialized_string( $entry['meta_value'] ) ) {
				// This is a serialized string, so we should display it.
				$entry['meta_value'] = maybe_unserialize( $entry['meta_value'] ); // // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- false positive, not a meta query.
			} else {
				// This is a serialized array/object so we should NOT display it.
				--$count;
				return '';
			}
		}

		$entry['meta_key']   = esc_attr( $entry['meta_key'] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- false positive, not a meta query.
		$entry['meta_value'] = esc_textarea( $entry['meta_value'] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- false positive, not a meta query.
		$entry['meta_id']    = (int) $entry['meta_id'];

		$delete_nonce = wp_create_nonce( 'delete-meta_' . $entry['meta_id'] );

		$r .= "\n\t<tr id='meta-{$entry['meta_id']}'>";
		$r .= "\n\t\t<td class='left'><label class='screen-reader-text' for='meta-{$entry['meta_id']}-key'>" . __( 'Key', 'woocommerce' ) . "</label><input name='meta[{$entry['meta_id']}][key]' id='meta-{$entry['meta_id']}-key' type='text' size='20' value='{$entry['meta_key']}' />";

		$r .= "\n\t\t<div class='submit'>";
		$r .= get_submit_button( __( 'Delete', 'woocommerce' ), 'deletemeta small', "deletemeta[{$entry['meta_id']}]", false, array( 'data-wp-lists' => "delete:the-list:meta-{$entry['meta_id']}::_ajax_nonce:$delete_nonce" ) );
		$r .= "\n\t\t";
		$r .= get_submit_button( __( 'Update', 'woocommerce' ), 'updatemeta small', "meta-{$entry['meta_id']}-submit", false, array( 'data-wp-lists' => "add:the-list:meta-{$entry['meta_id']}::_ajax_nonce-add-meta={$this->update_nonce}" ) );
		$r .= '</div>';
		$r .= wp_nonce_field( 'change-meta', '_ajax_nonce', false, false );
		$r .= '</td>';

		$r .= "\n\t\t<td><label class='screen-reader-text' for='meta-{$entry['meta_id']}-value'>" . __( 'Value', 'woocommerce' ) . "</label><textarea name='meta[{$entry['meta_id']}][value]' id='meta-{$entry['meta_id']}-value' rows='2' cols='30'>{$entry['meta_value']}</textarea></td>\n\t</tr>";
		return $r;
	}

	/**
	 * Reimplementation of WP core's `wp_ajax_delete_meta` method to support order custom meta updates with custom tables.
	 *
	 * @return void
	 */
	public function delete_meta_ajax() {
		$meta_id  = (int) $_POST['id'] ?? 0;
		$order_id = (int) $_POST['order_id'] ?? 0;
		if ( ! $meta_id || ! $order_id ) {
			wp_send_json_error( 'invalid_meta_id' );
			wp_die();
		}
		check_ajax_referer( "delete-meta_$meta_id" );

		$order          = $this->verify_order_edit_permission_for_ajax( $order_id );
		$meta_to_delete = wp_list_filter( $order->get_meta_data(), array( 'id' => $meta_id ) );

		if ( empty( $meta_to_delete ) ) {
			wp_send_json_error( 'invalid_meta_id' );
			wp_die();
		}

		$order->delete_meta_data_by_mid( $meta_id );
		if ( $order->save() ) {
			wp_die( 1 );
		}
		wp_die( 0 );
	}

	/**
	 * Handle the possible changes in order metadata coming from an order edit page in admin
	 * (labeled "custom fields" in the UI).
	 *
	 * This method expects the $_POST array to contain a 'meta' key that is an associative
	 * array of [meta item id => [ 'key' => meta item name, 'value' => meta item value ];
	 * and also to contain (possibly empty) 'metakeyinput' and 'metavalue' keys.
	 *
	 * @param WC_Order $order The order to handle.
	 */
	public function handle_metadata_changes( $order ) {
		$has_meta_changes = false;

		$order_meta = $order->get_meta_data();

		$order_meta =
			array_combine(
				array_map( fn( $meta ) => $meta->id, $order_meta ),
				$order_meta
			);

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing

		foreach ( ( $_POST['meta'] ?? array() ) as $request_meta_id => $request_meta_data ) {
			$request_meta_id    = wp_unslash( $request_meta_id );
			$request_meta_key   = wp_unslash( $request_meta_data['key'] );
			$request_meta_value = wp_unslash( $request_meta_data['value'] );
			if ( array_key_exists( $request_meta_id, $order_meta ) &&
				( $order_meta[ $request_meta_id ]->key !== $request_meta_key || $order_meta[ $request_meta_id ]->value !== $request_meta_value ) ) {
				$order->update_meta_data( $request_meta_key, $request_meta_value, $request_meta_id );
				$has_meta_changes = true;
			}
		}

		$request_new_key   = wp_unslash( $_POST['metakeyinput'] ?? '' );
		$request_new_value = wp_unslash( $_POST['metavalue'] ?? '' );
		if ( '' !== $request_new_key ) {
			$order->add_meta_data( $request_new_key, $request_new_value );
			$has_meta_changes = true;
		}

		// phpcs:enable WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing

		if ( $has_meta_changes ) {
			$order->save();
		}
	}
}
