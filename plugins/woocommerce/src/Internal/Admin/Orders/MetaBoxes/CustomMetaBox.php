<?php
/**
 * Meta box to edit and add custom meta values for an order.
 */

namespace Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes;

/**
 * Class CustomMetaBox.
 */
class CustomMetaBox {

	/**
	 * Constructor, adds necessary hooks.
	 */
	public function __construct() {
		add_filter( 'postmeta_form_keys', array( $this, 'order_meta_keys_autofill' ), 10, 2 );
	}

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
				'meta_value' => $data['value'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- False positive, not a meta query.
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
	 * @param array|null         $keys Keys to display in autofill.
	 * @param \WP_Post|\WC_Order $order Order object.
	 *
	 * @return array|mixed Array of keys to display in autofill.
	 */
	public function order_meta_keys_autofill( $keys, $order ) {
		if ( is_a( $order, \WC_Order::class ) ) {
			return array();
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
								if ( is_protected_meta( $key, 'post' ) || ! current_user_can( 'edit_others_shop_order', $order->get_id() ) ) {
									continue;
								}
								echo "\n<option value='" . esc_attr( $key ) . "'>" . esc_html( $key ) . '</option>';
							}
							?>
						</select>
						<input class="hide-if-js" type="text" id="metakeyinput" name="metakeyinput" value="" />
						<a href="#postcustomstuff" class="hide-if-no-js" onclick="jQuery('#metakeyinput, #metakeyselect, #enternew, #cancelnew').toggle();return false;">
							<span id="enternew"><?php esc_html_e( 'Enter new', 'woocommerce' ); ?></span>
							<span id="cancelnew" class="hidden"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></span></a>
					<?php } else { ?>
						<input type="text" id="metakeyinput" name="metakeyinput" value="" />
					<?php } ?>
				</td>
				<td><textarea id="metavalue" name="metavalue" rows="2" cols="25"></textarea></td>
			</tr>

			<tr><td colspan="2">
					<div class="submit">
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
					<?php wp_nonce_field( 'add-meta', '_ajax_nonce-add-meta', false ); ?>
				</td></tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save custom meta data for an order.
	 *
	 * @param int       $order_id Order ID.
	 * @param \WC_Order $order Order object.
	 *
	 * @return void
	 */
	public function save( int $order_id, \WC_Order $order ) {
		// handle meta delete.
		if ( empty( $_POST['deletemeta'] ) ) {
			return;
		}

		$delete_meta_id     = (int) sanitize_text_field( wp_unslash( key( $_POST['deletemeta'] ?? array() ) ) );
		$delete_meta_object = wp_list_filter( $order->get_meta_data(), array( 'id' => $delete_meta_id ) );

		if ( empty( $delete_meta_object ) ) {
			return;
		}

		$order->delete_meta_data_by_mid( $delete_meta_id );
		$order->save();
	}

}
