<?php
/**
 * Attributes Page
 *
 * The attributes section lets users add custom attributes to assign to products - they can also be used in the "Filter Products by Attribute" widget.
 *
 * @package WooCommerce\Admin
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\ProductAttributes\AttributeSlugLength;
use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermAdmin;

/**
 * WC_Admin_Attributes Class.
 */
class WC_Admin_Attributes {

	/**
	 * Edited attribute ID.
	 *
	 * @var int
	 */
	private static $edited_attribute_id;

	/**
	 * Handles output of the attributes page in admin.
	 *
	 * Shows the created attributes and lets you add new ones or edit existing ones.
	 * The added attributes are stored in the database and can be used for layered navigation.
	 */
	public static function output() {
		$result = '';
		$action = '';

		// Action to perform: add, edit, delete or none.
		if ( ! empty( $_POST['add_new_attribute'] ) ) { // WPCS: CSRF ok.
			$action = 'add';
		} elseif ( ! empty( $_POST['save_attribute'] ) && ! empty( $_GET['edit'] ) ) { // WPCS: CSRF ok.
			$action = 'edit';
		} elseif ( ! empty( $_GET['delete'] ) ) {
			$action = 'delete';
		}

		switch ( $action ) {
			case 'add':
				$result = self::process_add_attribute();
				break;
			case 'edit':
				$result = self::process_edit_attribute();
				break;
			case 'delete':
				$result = self::process_delete_attribute();
				break;
		}

		if ( is_wp_error( $result ) ) {
			echo '<div id="woocommerce_errors" class="error"><p>' . wp_kses_post( $result->get_error_message() ) . '</p></div>';
		}

		// Show admin interface.
		if ( ! empty( $_GET['edit'] ) ) {
			self::edit_attribute();
		} else {
			self::add_attribute();
		}
	}

	/**
	 * Get and sanitize posted attribute data.
	 *
	 * @return array
	 */
	private static function get_posted_attribute() {
		$attribute = array(
			'attribute_label'   => isset( $_POST['attribute_label'] ) ? wc_clean( wp_unslash( $_POST['attribute_label'] ) ) : '', // WPCS: input var ok, CSRF ok.
			'attribute_name'    => isset( $_POST['attribute_name'] ) ? wc_sanitize_taxonomy_name( wp_unslash( $_POST['attribute_name'] ) ) : '', // WPCS: input var ok, CSRF ok, sanitization ok.
			'attribute_type'    => isset( $_POST['attribute_type'] ) ? wc_clean( wp_unslash( $_POST['attribute_type'] ) ) : 'select', // WPCS: input var ok, CSRF ok.
			'attribute_orderby' => isset( $_POST['attribute_orderby'] ) ? wc_clean( wp_unslash( $_POST['attribute_orderby'] ) ) : '', // WPCS: input var ok, CSRF ok.
			'attribute_public'  => isset( $_POST['attribute_public'] ) ? 1 : 0, // WPCS: input var ok, CSRF ok.
		);

		if ( empty( $attribute['attribute_type'] ) ) {
			$attribute['attribute_type'] = 'select';
		}
		if ( empty( $attribute['attribute_label'] ) ) {
			$attribute['attribute_label'] = ucfirst( $attribute['attribute_name'] );
		}
		if ( empty( $attribute['attribute_name'] ) ) {
			$attribute['attribute_name'] = wc_sanitize_taxonomy_name( $attribute['attribute_label'] );
		}

		return $attribute;
	}

	/**
	 * Add an attribute.
	 *
	 * @return bool|WP_Error
	 */
	private static function process_add_attribute() {
		check_admin_referer( 'woocommerce-add-new_attribute' );

		$attribute = self::get_posted_attribute();
		$args      = array(
			'name'         => $attribute['attribute_label'],
			'slug'         => $attribute['attribute_name'],
			'type'         => $attribute['attribute_type'],
			'order_by'     => $attribute['attribute_orderby'],
			'has_archives' => $attribute['attribute_public'],
		);

		$id = wc_create_attribute( $args );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		VisualAttributeTermAdmin::seed_visual_attribute_terms( $id );

		return true;
	}

	/**
	 * Edit an attribute.
	 *
	 * @return bool|WP_Error
	 */
	private static function process_edit_attribute() {
		$attribute_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		check_admin_referer( 'woocommerce-save-attribute_' . $attribute_id );

		$attribute = self::get_posted_attribute();
		$args      = array(
			'name'         => $attribute['attribute_label'],
			'slug'         => $attribute['attribute_name'],
			'type'         => $attribute['attribute_type'],
			'order_by'     => $attribute['attribute_orderby'],
			'has_archives' => $attribute['attribute_public'],
		);

		$id = wc_update_attribute( $attribute_id, $args );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		self::$edited_attribute_id = $id;

		return true;
	}

	/**
	 * Delete an attribute.
	 *
	 * @return bool
	 */
	private static function process_delete_attribute() {
		$attribute_id = isset( $_GET['delete'] ) ? absint( $_GET['delete'] ) : 0;
		check_admin_referer( 'woocommerce-delete-attribute_' . $attribute_id );

		return wc_delete_attribute( $attribute_id );
	}

	/**
	 * Build the slug field description shown to users (and served to search engines and
	 * no-JavaScript clients as the static copy).
	 *
	 * States the authoritative byte limit alongside a character estimate tailored to the
	 * script of the user's language, so users on non-Latin scripts get a meaningful
	 * figure rather than a raw byte count they must translate into characters themselves.
	 *
	 * @return string
	 */
	private static function slug_field_description(): string {
		return sprintf(
			/* translators: 1: maximum slug length in bytes, 2: approximate maximum number of characters for the user's language. */
			__( 'Unique slug/reference for the attribute. Limited to %1$d bytes — roughly %2$d characters in your language; non-ASCII characters use 2–4 bytes each.', 'woocommerce' ),
			wc_get_attribute_slug_max_byte_length(),
			AttributeSlugLength::get_character_estimate()
		);
	}

	/**
	 * Output an inline script that shows a live UTF-8 byte count next to the
	 * slug input and visually warns when the value approaches or exceeds the
	 * server-side byte limit.
	 *
	 * The HTML `maxlength` attribute counts characters, not bytes, so a
	 * multibyte slug (e.g. Cyrillic, Chinese) can pass the browser's guard
	 * and still be rejected by `register_taxonomy()`'s 32-byte name limit.
	 * This counter closes that feedback gap before submission.
	 *
	 * When the live counter activates, it also swaps the server-rendered field
	 * description for a leaner note: the counter now carries the concrete byte
	 * numbers, so the locale-tailored character estimate becomes redundant.
	 */
	private static function slug_byte_counter_script(): void {
		$max_bytes = wc_get_attribute_slug_max_byte_length();
		/* translators: 1: current byte count, 2: maximum allowed bytes. */
		$template = wp_json_encode( __( '%1$d / %2$d bytes', 'woocommerce' ), JSON_HEX_TAG | JSON_HEX_AMP );
		if ( false === $template ) {
			// Encoding the counter template failed (e.g. invalid UTF-8 in the translated
			// string); skip the counter rather than emit broken inline JavaScript.
			return;
		}
		// Leaner description for JS clients: the live counter below carries the concrete
		// numbers, so the locale-tailored character estimate becomes redundant noise.
		$js_description = wp_json_encode(
			/* translators: %d: maximum slug length in bytes. */
			sprintf( __( 'Unique slug/reference for the attribute. Non-ASCII characters use 2–4 bytes of the %d-byte limit.', 'woocommerce' ), $max_bytes ),
			JSON_HEX_TAG | JSON_HEX_AMP
		);
		if ( false === $js_description ) {
			// Keep the server-rendered description if encoding fails.
			$js_description = 'null';
		}
		?>
		<script type="text/javascript">
			( function() {
				var input = document.getElementById( 'attribute_name' );
				if ( ! input || ! ( 'TextEncoder' in window ) ) {
					return;
				}
				var wrapper = input.closest( 'td, .form-field' );
				var description = wrapper ? wrapper.querySelector( 'p.description' ) : null;
				if ( ! description ) {
					return;
				}
				var maxBytes = <?php echo (int) $max_bytes; ?>;
				var template = <?php echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode with JSON_HEX_TAG produces JS-safe output. ?>;
				var jsDescription = <?php echo $js_description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode with JSON_HEX_TAG produces JS-safe output; 'null' fallback is a literal. ?>;
				var encoder = new TextEncoder();
				var counter = document.createElement( 'span' );
				counter.style.display = 'block';
				counter.style.marginTop = '4px';
				if ( jsDescription ) {
					// Swap the verbose server-rendered guidance for the lean note now that
					// the live counter provides the concrete byte feedback.
					description.textContent = jsDescription;
				}
				description.appendChild( counter );
				function update() {
					var bytes = encoder.encode( input.value ).length;
					if ( 0 === bytes ) {
						counter.textContent = '';
						counter.style.color = '';
						return;
					}
					counter.textContent = template
						.replace( '%1$d', bytes )
						.replace( '%2$d', maxBytes );
					if ( bytes > maxBytes ) {
						counter.style.color = '#d63638';
					} else if ( bytes >= maxBytes - 3 ) {
						// Within one multibyte character (up to 3 bytes) of the limit.
						counter.style.color = '#996800';
					} else {
						counter.style.color = '';
					}
				}
				input.addEventListener( 'input', update );
				update();
			} )();
		</script>
		<?php
	}

	/**
	 * Edit Attribute admin panel.
	 *
	 * Shows the interface for changing an attributes type between select and text.
	 */
	public static function edit_attribute() {
		global $wpdb;

		$edit = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;

		$attribute_to_edit = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT attribute_type, attribute_label, attribute_name, attribute_orderby, attribute_public
				FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = %d
				",
				$edit
			)
		);

		?>
		<div class="wrap woocommerce">
			<h1><?php esc_html_e( 'Edit attribute', 'woocommerce' ); ?></h1>

			<?php
			if ( ! $attribute_to_edit ) {
				echo '<div id="woocommerce_errors" class="error"><p>' . esc_html__( 'Error: non-existing attribute ID.', 'woocommerce' ) . '</p></div>';
			} else {
				if ( self::$edited_attribute_id > 0 ) {
					echo '<div id="message" class="updated"><p>' . esc_html__( 'Attribute updated successfully', 'woocommerce' ) . '</p><p><a href="' . esc_url( admin_url( 'edit.php?post_type=product&amp;page=product_attributes' ) ) . '">' . esc_html__( 'Back to Attributes', 'woocommerce' ) . '</a></p></div>';
					self::$edited_attribute_id = null;
				}
				$att_type    = $attribute_to_edit->attribute_type;
				$att_label   = format_to_edit( $attribute_to_edit->attribute_label );
				$att_name    = $attribute_to_edit->attribute_name;
				$att_orderby = $attribute_to_edit->attribute_orderby;
				$att_public  = $attribute_to_edit->attribute_public;
				?>
				<form action="edit.php?post_type=product&amp;page=product_attributes&amp;edit=<?php echo absint( $edit ); ?>" method="post">
					<table class="form-table">
						<tbody>
							<?php do_action( 'woocommerce_before_edit_attribute_fields' ); ?>
							<tr class="form-field form-required">
								<th scope="row" valign="top">
									<label for="attribute_label"><?php esc_html_e( 'Name', 'woocommerce' ); ?></label>
								</th>
								<td>
									<input name="attribute_label" id="attribute_label" type="text" value="<?php echo esc_attr( $att_label ); ?>" />
									<p class="description"><?php esc_html_e( 'Name for the attribute (shown on the front-end).', 'woocommerce' ); ?></p>
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top">
									<label for="attribute_name"><?php esc_html_e( 'Slug', 'woocommerce' ); ?></label>
								</th>
								<td>
									<input name="attribute_name" id="attribute_name" type="text" value="<?php echo esc_attr( $att_name ); ?>" maxlength="29" />
									<p class="description"><?php echo esc_html( self::slug_field_description() ); ?></p>
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top">
									<label for="attribute_public"><?php esc_html_e( 'Enable archives?', 'woocommerce' ); ?></label>
								</th>
								<td>
									<input name="attribute_public" id="attribute_public" type="checkbox" value="1" <?php checked( $att_public, 1 ); ?> />
									<p class="description"><?php esc_html_e( 'Enable this if you want this attribute to have product archives in your store.', 'woocommerce' ); ?></p>
								</td>
							</tr>
							<?php
							/**
							 * Attribute types can change the way attributes are displayed on the frontend and admin.
							 *
							 * By Default WooCommerce only includes the `select` type. Others can be added with the
							 * `product_attributes_type_selector` filter. If there is only the default type registered,
							 * this setting will be hidden.
							 */
							if ( wc_has_custom_attribute_types() ) {
								?>
								<tr class="form-field form-required">
									<th scope="row" valign="top">
										<label for="attribute_type"><?php esc_html_e( 'Type', 'woocommerce' ); ?></label>
									</th>
									<td>
										<select name="attribute_type" id="attribute_type">
											<?php foreach ( wc_get_attribute_types() as $key => $value ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $att_type, $key ); ?>><?php echo esc_html( $value ); ?></option>
											<?php endforeach; ?>
											<?php
												/**
												 * Deprecated action in favor of product_attributes_type_selector filter.
												 *
												 * @todo Remove in 4.0.0
												 * @deprecated 2.4.0
												 */
												do_action( 'woocommerce_admin_attribute_types' );
											?>
										</select>
										<p class="description"><?php esc_html_e( "Determines how this attribute's values are displayed.", 'woocommerce' ); ?></p>
									</td>
								</tr>
								<?php
							}//end if
							?>
							<tr class="form-field form-required">
								<th scope="row" valign="top">
									<label for="attribute_orderby"><?php esc_html_e( 'Default sort order', 'woocommerce' ); ?></label>
								</th>
								<td>
									<select name="attribute_orderby" id="attribute_orderby">
										<option value="menu_order" <?php selected( $att_orderby, 'menu_order' ); ?>><?php esc_html_e( 'Custom ordering', 'woocommerce' ); ?></option>
										<option value="name" <?php selected( $att_orderby, 'name' ); ?>><?php esc_html_e( 'Name', 'woocommerce' ); ?></option>
										<option value="name_num" <?php selected( $att_orderby, 'name_num' ); ?>><?php esc_html_e( 'Name (numeric)', 'woocommerce' ); ?></option>
										<option value="id" <?php selected( $att_orderby, 'id' ); ?>><?php esc_html_e( 'Term ID', 'woocommerce' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'Determines the sort order of the terms on the frontend shop product pages. If using custom ordering, you can drag and drop the terms in this attribute.', 'woocommerce' ); ?></p>
								</td>
							</tr>
							<?php do_action( 'woocommerce_after_edit_attribute_fields' ); ?>
						</tbody>
					</table>
					<p class="submit"><button type="submit" name="save_attribute" id="submit" class="button-primary" value="<?php esc_attr_e( 'Update', 'woocommerce' ); ?>"><?php esc_html_e( 'Update', 'woocommerce' ); ?></button></p>
					<?php wp_nonce_field( 'woocommerce-save-attribute_' . $edit ); ?>
				</form>
				<?php self::slug_byte_counter_script(); ?>
				<?php
			}//end if
			?>
		</div>
		<?php
	}

	/**
	 * Add Attribute admin panel.
	 *
	 * Shows the interface for adding new attributes.
	 */
	public static function add_attribute() {
		?>
		<div class="wrap woocommerce">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<br class="clear" />
			<div id="col-container">
				<div id="col-right">
					<div class="col-wrap">
						<table class="widefat attributes-table wp-list-table ui-sortable" style="width:100%">
							<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'Name', 'woocommerce' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Slug', 'woocommerce' ); ?></th>
									<?php if ( wc_has_custom_attribute_types() ) : ?>
										<th scope="col"><?php esc_html_e( 'Type', 'woocommerce' ); ?></th>
									<?php endif; ?>
									<th scope="col"><?php esc_html_e( 'Order by', 'woocommerce' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Terms', 'woocommerce' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$attribute_taxonomies = wc_get_attribute_taxonomies();
								if ( $attribute_taxonomies ) {
									/**
									 * Filters the maximum number of terms that will be displayed for each taxonomy in the Attributes page.
									 *
									 * @param int @default_max_terms_to_display Default value.
									 * @returns int Actual value to use, may be zero.
									 *
									 * @since 6.9.0
									 */
									$max_terms_to_display = apply_filters( 'woocommerce_max_terms_displayed_in_attributes_page', 100 );
									foreach ( $attribute_taxonomies as $tax ) :
										?>
										<tr>
												<td>
													<strong><a href="edit-tags.php?taxonomy=<?php echo esc_attr( wc_attribute_taxonomy_name( $tax->attribute_name ) ); ?>&amp;post_type=product"><?php echo esc_html( $tax->attribute_label ); ?></a></strong>

													<div class="row-actions"><span class="edit"><a href="<?php echo esc_url( add_query_arg( 'edit', $tax->attribute_id, 'edit.php?post_type=product&amp;page=product_attributes' ) ); ?>"><?php esc_html_e( 'Edit', 'woocommerce' ); ?></a> | </span><span class="delete"><a class="delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'delete', $tax->attribute_id, 'edit.php?post_type=product&amp;page=product_attributes' ), 'woocommerce-delete-attribute_' . $tax->attribute_id ) ); ?>"><?php esc_html_e( 'Delete', 'woocommerce' ); ?></a></span></div>
												</td>
												<td><?php echo esc_html( $tax->attribute_name ); ?></td>
												<?php if ( wc_has_custom_attribute_types() ) : ?>
													<td><?php echo esc_html( wc_get_attribute_type_label( $tax->attribute_type ) ); ?> <?php echo $tax->attribute_public ? esc_html__( '(Public)', 'woocommerce' ) : ''; ?></td>
												<?php endif; ?>
												<td>
													<?php
													switch ( $tax->attribute_orderby ) {
														case 'name':
															esc_html_e( 'Name', 'woocommerce' );
															break;
														case 'name_num':
															esc_html_e( 'Name (numeric)', 'woocommerce' );
															break;
														case 'id':
															esc_html_e( 'Term ID', 'woocommerce' );
															break;
														default:
															esc_html_e( 'Custom ordering', 'woocommerce' );
															break;
													}
													?>
												</td>
												<td class="attribute-terms">
													<?php
													$taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );

													if ( taxonomy_exists( $taxonomy ) ) {
														$total_count = (int) get_terms(
															array(
																'taxonomy'   => $taxonomy,
																'fields'     => 'count',
																'hide_empty' => false,
															)
														);
														if ( 0 === $total_count ) {
															echo '<span class="na">&ndash;</span>';
														} elseif ( $max_terms_to_display > 0 ) {
															$terms        = get_terms(
																array(
																	'taxonomy'   => $taxonomy,
																	'number'     => $max_terms_to_display,
																	'fields'     => 'names',
																	'hide_empty' => false,
																)
															);
															$terms_string = implode( ', ', $terms );
															if ( $total_count > $max_terms_to_display ) {
																$remaining = $total_count - $max_terms_to_display;
																/* translators: 1: Comma-separated terms list, 2: how many terms are hidden */
																$terms_string = sprintf( __( '%1$s... (%2$s more)', 'woocommerce' ), $terms_string, $remaining );
															}
															echo esc_html( $terms_string );
														} elseif ( 1 === $total_count ) {
															echo esc_html( __( '1 term', 'woocommerce' ) );
														} else {
															/* translators: %s: Total count of terms available for the attribute */
															echo esc_html( sprintf( __( '%s terms', 'woocommerce' ), $total_count ) );
														}//end if
													} else {
															echo '<span class="na">&ndash;</span><br />';
													}//end if
													?>
													<br /><a href="edit-tags.php?taxonomy=<?php echo esc_attr( wc_attribute_taxonomy_name( $tax->attribute_name ) ); ?>&amp;post_type=product" class="configure-terms"><?php esc_html_e( 'Configure terms', 'woocommerce' ); ?></a>
												</td>
											</tr>
											<?php
										endforeach;
								} else {
									$column_count = wc_has_custom_attribute_types() ? '5' : '4';
									?>
										<tr>
											<td colspan="<?php echo esc_attr( $column_count ); ?>"><?php esc_html_e( 'No attributes currently exist.', 'woocommerce' ); ?></td>
										</tr>
										<?php
								}//end if
								?>
							</tbody>
						</table>
					</div>
				</div>
				<div id="col-left">
					<div class="col-wrap">
						<div class="form-wrap">
							<h2><?php esc_html_e( 'Add new attribute', 'woocommerce' ); ?></h2>
							<p><?php esc_html_e( 'Attributes let you define extra product data, such as size or color. You can use these attributes in the shop sidebar using the "layered nav" widgets.', 'woocommerce' ); ?></p>
							<form action="edit.php?post_type=product&amp;page=product_attributes" method="post">
								<?php do_action( 'woocommerce_before_add_attribute_fields' ); ?>

								<div class="form-field">
									<label for="attribute_label"><?php esc_html_e( 'Name', 'woocommerce' ); ?></label>
									<input name="attribute_label" id="attribute_label" type="text" value="" />
									<p class="description"><?php esc_html_e( 'Name for the attribute (shown on the front-end).', 'woocommerce' ); ?></p>
								</div>

								<div class="form-field">
									<label for="attribute_name"><?php esc_html_e( 'Slug', 'woocommerce' ); ?></label>
									<input name="attribute_name" id="attribute_name" type="text" value="" maxlength="29" />
									<p class="description"><?php echo esc_html( self::slug_field_description() ); ?></p>
								</div>

								<div class="form-field">
									<label for="attribute_public"><input name="attribute_public" id="attribute_public" type="checkbox" value="1" /> <?php esc_html_e( 'Enable Archives?', 'woocommerce' ); ?></label>

									<p class="description"><?php esc_html_e( 'Enable this if you want this attribute to have product archives in your store.', 'woocommerce' ); ?></p>
								</div>

								<?php
								/**
								 * Attribute types can change the way attributes are displayed on the frontend and admin.
								 *
								 * By Default WooCommerce only includes the `select` type. Others can be added with the
								 * `product_attributes_type_selector` filter. If there is only the default type registered,
								 * this setting will be hidden.
								 */
								if ( wc_has_custom_attribute_types() ) {
									?>
									<div class="form-field">
										<label for="attribute_type"><?php esc_html_e( 'Type', 'woocommerce' ); ?></label>
										<select name="attribute_type" id="attribute_type">
											<?php foreach ( wc_get_attribute_types() as $key => $value ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
											<?php endforeach; ?>
											<?php
												/**
												 * Deprecated action in favor of product_attributes_type_selector filter.
												 *
												 * @todo Remove in 4.0.0
												 * @deprecated 2.4.0
												 */
												do_action( 'woocommerce_admin_attribute_types' );
											?>
										</select>
										<p class="description"><?php esc_html_e( "Determines how this attribute's values are displayed.", 'woocommerce' ); ?></p>
									</div>
									<?php
								}//end if
								?>

								<div class="form-field">
									<label for="attribute_orderby"><?php esc_html_e( 'Default sort order', 'woocommerce' ); ?></label>
									<select name="attribute_orderby" id="attribute_orderby">
										<option value="menu_order"><?php esc_html_e( 'Custom ordering', 'woocommerce' ); ?></option>
										<option value="name"><?php esc_html_e( 'Name', 'woocommerce' ); ?></option>
										<option value="name_num"><?php esc_html_e( 'Name (numeric)', 'woocommerce' ); ?></option>
										<option value="id"><?php esc_html_e( 'Term ID', 'woocommerce' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'Determines the sort order of the terms on the frontend shop product pages. If using custom ordering, you can drag and drop the terms in this attribute.', 'woocommerce' ); ?></p>
								</div>

								<?php do_action( 'woocommerce_after_add_attribute_fields' ); ?>

								<p class="submit"><button type="submit" name="add_new_attribute" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Add attribute', 'woocommerce' ); ?>"><?php esc_html_e( 'Add attribute', 'woocommerce' ); ?></button></p>
								<?php wp_nonce_field( 'woocommerce-add-new_attribute' ); ?>
							</form>
						</div>
					</div>
				</div>
			</div>
			<script type="text/javascript">
			/* <![CDATA[ */

				jQuery( 'a.delete' ).on( 'click', function() {
					if ( window.confirm( '<?php esc_html_e( 'Are you sure you want to delete this attribute?', 'woocommerce' ); ?>' ) ) {
						return true;
					}
					return false;
				});

			/* ]]> */
			</script>
			<?php self::slug_byte_counter_script(); ?>
		</div>
		<?php
	}
}
