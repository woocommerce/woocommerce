<?php
/**
 * Attributes Page
 *
 * The attributes section lets users add custom attributes to assign to products - they can also be used in the layered nav widget.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Attributes Class.
 */
class WC_Admin_Attributes {

	/**
	 * Handles output of the attributes page in admin.
	 *
	 * Shows the created attributes and lets you add new ones or edit existing ones.
	 * The added attributes are stored in the database and can be used for layered navigation.
	 */
	public static function output() {
		$result = '';
		$action = '';

		// Action to perform: add, edit, delete or none
		if ( ! empty( $_POST['add_new_attribute'] ) ) {
			$action = 'add';
		} elseif ( ! empty( $_POST['save_attribute'] ) && ! empty( $_GET['edit'] ) ) {
			$action = 'edit';
		} elseif ( ! empty( $_GET['delete'] ) ) {
			$action = 'delete';
		}

		switch ( $action ) {
			case 'add' :
				$result = self::process_add_attribute();
			break;
			case 'edit' :
				$result = self::process_edit_attribute();
			break;
			case 'delete' :
				$result = self::process_delete_attribute();
			break;
		}

		if ( is_wp_error( $result ) ) {
			echo '<div id="woocommerce_errors" class="error"><p>' . $result->get_error_message() . '</p></div>';
		}

		// Show admin interface
		if ( ! empty( $_GET['edit'] ) ) {
			self::edit_attribute();
		} else {
			self::add_attribute();
		}
	}

	/**
	 * Get and sanitize posted attribute data.
	 * @return array
	 */
	private static function get_posted_attribute() {
		$attribute = array(
			'attribute_label'   => isset( $_POST['attribute_label'] )   ? wc_clean( stripslashes( $_POST['attribute_label'] ) ) : '',
			'attribute_name'    => isset( $_POST['attribute_name'] )    ? wc_sanitize_taxonomy_name( stripslashes( $_POST['attribute_name'] ) ) : '',
			'attribute_type'    => isset( $_POST['attribute_type'] )    ? wc_clean( $_POST['attribute_type'] ) : 'select',
			'attribute_orderby' => isset( $_POST['attribute_orderby'] ) ? wc_clean( $_POST['attribute_orderby'] ) : '',
			'attribute_public'  => isset( $_POST['attribute_public'] )  ? 1 : 0
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
	 * See if an attribute name is valid.
	 * @param  string $attribute_name
	 * @return bool|WP_error result
	 */
	private static function valid_attribute_name( $attribute_name ) {
		if ( strlen( $attribute_name ) >= 28 ) {
			return new WP_Error( 'error', sprintf( __( 'Slug "%s" is too long (28 characters max). Shorten it, please.', 'woocommerce' ), sanitize_title( $attribute_name ) ) );
		} elseif ( wc_check_if_attribute_name_is_reserved( $attribute_name ) ) {
			return new WP_Error( 'error', sprintf( __( 'Slug "%s" is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), sanitize_title( $attribute_name ) ) );
		}

		return true;
	}

	/**
	 * Add an attribute.
	 * @return bool|WP_Error
	 */
	private static function process_add_attribute() {
		global $wpdb;
		check_admin_referer( 'woocommerce-add-new_attribute' );

		$attribute = self::get_posted_attribute();

		if ( empty( $attribute['attribute_name'] ) || empty( $attribute['attribute_label'] ) ) {
			return new WP_Error( 'error', __( 'Please, provide an attribute name and slug.', 'woocommerce' ) );
		} elseif ( ( $valid_attribute_name = self::valid_attribute_name( $attribute['attribute_name'] ) ) && is_wp_error( $valid_attribute_name ) ) {
			return $valid_attribute_name;
		} elseif ( taxonomy_exists( wc_attribute_taxonomy_name( $attribute['attribute_name'] ) ) ) {
			return new WP_Error( 'error', sprintf( __( 'Slug "%s" is already in use. Change it, please.', 'woocommerce' ), sanitize_title( $attribute['attribute_name'] ) ) );
		}

		$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );

		do_action( 'woocommerce_attribute_added', $wpdb->insert_id, $attribute );

		flush_rewrite_rules();
		delete_transient( 'wc_attribute_taxonomies' );

		return true;
	}

	/**
	 * Edit an attribute.
	 * @return bool|WP_Error
	 */
	private static function process_edit_attribute() {
		global $wpdb;
		$attribute_id = absint( $_GET['edit'] );
		check_admin_referer( 'woocommerce-save-attribute_' . $attribute_id );

		$attribute = self::get_posted_attribute();

		if ( empty( $attribute['attribute_name'] ) || empty( $attribute['attribute_label'] ) ) {
			return new WP_Error( 'error', __( 'Please, provide an attribute name and slug.', 'woocommerce' ) );
		} elseif ( ( $valid_attribute_name = self::valid_attribute_name( $attribute['attribute_name'] ) ) && is_wp_error( $valid_attribute_name ) ) {
			return $valid_attribute_name;
		}

		$taxonomy_exists    = taxonomy_exists( wc_attribute_taxonomy_name( $attribute['attribute_name'] ) );
		$old_attribute_name = $wpdb->get_var( "SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = $attribute_id" );
		if ( $old_attribute_name != $attribute['attribute_name'] && wc_sanitize_taxonomy_name( $old_attribute_name ) != $attribute['attribute_name'] && $taxonomy_exists ) {
			return new WP_Error( 'error', sprintf( __( 'Slug "%s" is already in use. Change it, please.', 'woocommerce' ), sanitize_title( $attribute['attribute_name'] ) ) );
		}

		$wpdb->update( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute, array( 'attribute_id' => $attribute_id ) );

		do_action( 'woocommerce_attribute_updated', $attribute_id, $attribute, $old_attribute_name );

		if ( $old_attribute_name != $attribute['attribute_name'] && ! empty( $old_attribute_name ) ) {
			// Update taxonomies in the wp term taxonomy table
			$wpdb->update(
				$wpdb->term_taxonomy,
				array( 'taxonomy' => wc_attribute_taxonomy_name( $attribute['attribute_name'] ) ),
				array( 'taxonomy' => 'pa_' . $old_attribute_name )
			);

			// Update taxonomy ordering term meta
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_termmeta',
				array( 'meta_key' => 'order_pa_' . sanitize_title( $attribute['attribute_name'] ) ),
				array( 'meta_key' => 'order_pa_' . sanitize_title( $old_attribute_name ) )
			);

			// Update product attributes which use this taxonomy
			$old_attribute_name_length = strlen( $old_attribute_name ) + 3;
			$attribute_name_length     = strlen( $attribute['attribute_name'] ) + 3;

			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE( meta_value, %s, %s ) WHERE meta_key = '_product_attributes'",
				's:' . $old_attribute_name_length . ':"pa_' . $old_attribute_name . '"',
				's:' . $attribute_name_length . ':"pa_' . $attribute['attribute_name'] . '"'
			) );

			// Update variations which use this taxonomy
			$wpdb->update(
				$wpdb->postmeta,
				array( 'meta_key' => 'attribute_pa_' . sanitize_title( $attribute['attribute_name'] ) ),
				array( 'meta_key' => 'attribute_pa_' . sanitize_title( $old_attribute_name ) )
			);
		}

		echo '<div class="updated"><p>' . __( 'Attribute updated successfully', 'woocommerce' ) . '</p></div>';

		flush_rewrite_rules();
		delete_transient( 'wc_attribute_taxonomies' );

		return true;
	}

	/**
	 * Delete an attribute.
	 * @return bool
	 */
	private static function process_delete_attribute() {
		global $wpdb;
		$attribute_id = absint( $_GET['delete'] );
		check_admin_referer( 'woocommerce-delete-attribute_' . $attribute_id );

		$attribute_name = $wpdb->get_var( "SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = $attribute_id" );

		if ( $attribute_name && $wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = $attribute_id" ) ) {

			$taxonomy = wc_attribute_taxonomy_name( $attribute_name );

			if ( taxonomy_exists( $taxonomy ) ) {
				$terms = get_terms( $taxonomy, 'orderby=name&hide_empty=0' );
				foreach ( $terms as $term ) {
					wp_delete_term( $term->term_id, $taxonomy );
				}
			}

			do_action( 'woocommerce_attribute_deleted', $attribute_id, $attribute_name, $taxonomy );
			delete_transient( 'wc_attribute_taxonomies' );

			return true;
		}

		return false;
	}

	/**
	 * Edit Attribute admin panel.
	 *
	 * Shows the interface for changing an attributes type between select and text.
	 */
	public static function edit_attribute() {
		global $wpdb;

		$edit = absint( $_GET['edit'] );

		$attribute_to_edit = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_id = '$edit'" );

		?>
		<div class="wrap woocommerce">
			<div class="icon32 icon32-attributes" id="icon-woocommerce"><br/></div>
			<h1><?php _e( 'Edit Attribute', 'woocommerce' ) ?></h1>

			<?php

				if ( ! $attribute_to_edit ) {
					echo '<div id="woocommerce_errors" class="error"><p>' . __( 'Error: non-existing attribute ID.', 'woocommerce' ) . '</p></div>';
				} else {
					$att_type    = $attribute_to_edit->attribute_type;
					$att_label   = $attribute_to_edit->attribute_label;
					$att_name    = $attribute_to_edit->attribute_name;
					$att_orderby = $attribute_to_edit->attribute_orderby;
					$att_public  = $attribute_to_edit->attribute_public;

				?>

				<form action="edit.php?post_type=product&amp;page=product_attributes&amp;edit=<?php echo absint( $edit ); ?>" method="post">
					<table class="form-table">
						<tbody>
							<tr class="form-field form-required">
								<th scope="row" valign="top">
									<label for="attribute_label"><?php _e( 'Name', 'woocommerce' ); ?></label>
								</th>
								<td>
									<input name="attribute_label" id="attribute_label" type="text" value="<?php echo esc_attr( $att_label ); ?>" />
									<p class="description"><?php _e( 'Name for the attribute (shown on the front-end).', 'woocommerce' ); ?></p>
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top">
									<label for="attribute_name"><?php _e( 'Slug', 'woocommerce' ); ?></label>
								</th>
								<td>
									<input name="attribute_name" id="attribute_name" type="text" value="<?php echo esc_attr( $att_name ); ?>" maxlength="28" />
									<p class="description"><?php _e( 'Unique slug/reference for the attribute; must be shorter than 28 characters.', 'woocommerce' ); ?></p>
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top">
									<label for="attribute_public"><?php _e( 'Enable Archives?', 'woocommerce' ); ?></label>
								</th>
								<td>
									<input name="attribute_public" id="attribute_public" type="checkbox" value="1" <?php checked( $att_public, 1 ); ?> />
									<p class="description"><?php _e( 'Enable this if you want this attribute to have product archives in your store.', 'woocommerce' ); ?></p>
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top">
									<label for="attribute_type"><?php _e( 'Type', 'woocommerce' ); ?></label>
								</th>
								<td>
									<select name="attribute_type" id="attribute_type">
										<?php foreach ( wc_get_attribute_types() as $key => $value ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $att_type, $key ); ?>><?php echo esc_attr( $value ); ?></option>
										<?php endforeach; ?>

										<?php

											/**
											 * Deprecated action in favor of product_attributes_type_selector filter.
											 *
											 * @deprecated 2.4.0
											 */
											do_action( 'woocommerce_admin_attribute_types' );
										?>
									</select>
									<p class="description"><?php _e( 'Determines how you select attributes for products. Under admin panel -> products -> product data -> attributes -> values, <strong>Text</strong> allows manual entry whereas <strong>select</strong> allows pre-configured terms in a drop-down list.', 'woocommerce' ); ?></p>
								</td>
							</tr>
							<tr class="form-field form-required">
								<th scope="row" valign="top">
									<label for="attribute_orderby"><?php _e( 'Default sort order', 'woocommerce' ); ?></label>
								</th>
								<td>
									<select name="attribute_orderby" id="attribute_orderby">
										<option value="menu_order" <?php selected( $att_orderby, 'menu_order' ); ?>><?php _e( 'Custom ordering', 'woocommerce' ); ?></option>
										<option value="name" <?php selected( $att_orderby, 'name' ); ?>><?php _e( 'Name', 'woocommerce' ); ?></option>
										<option value="name_num" <?php selected( $att_orderby, 'name_num' ); ?>><?php _e( 'Name (numeric)', 'woocommerce' ); ?></option>
										<option value="id" <?php selected( $att_orderby, 'id' ); ?>><?php _e( 'Term ID', 'woocommerce' ); ?></option>
									</select>
									<p class="description"><?php _e( 'Determines the sort order of the terms on the frontend shop product pages. If using custom ordering, you can drag and drop the terms in this attribute.', 'woocommerce' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit"><input type="submit" name="save_attribute" id="submit" class="button-primary" value="<?php esc_attr_e( 'Update', 'woocommerce' ); ?>"></p>
					<?php wp_nonce_field( 'woocommerce-save-attribute_' . $edit ); ?>
				</form>
			<?php } ?>
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
			<div class="icon32 icon32-attributes" id="icon-woocommerce"><br/></div>
			<h1><?php _e( 'Attributes', 'woocommerce' ); ?></h1>
			<br class="clear" />
			<div id="col-container">
				<div id="col-right">
					<div class="col-wrap">
						<table class="widefat attributes-table wp-list-table ui-sortable" style="width:100%">
							<thead>
								<tr>
									<th scope="col"><?php _e( 'Name', 'woocommerce' ); ?></th>
									<th scope="col"><?php _e( 'Slug', 'woocommerce' ); ?></th>
									<th scope="col"><?php _e( 'Type', 'woocommerce' ); ?></th>
									<th scope="col"><?php _e( 'Order by', 'woocommerce' ); ?></th>
									<th scope="col" colspan="2"><?php _e( 'Terms', 'woocommerce' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
									if ( $attribute_taxonomies = wc_get_attribute_taxonomies() ) :
										foreach ( $attribute_taxonomies as $tax ) :
											?><tr>

												<td>
													<strong><a href="edit-tags.php?taxonomy=<?php echo esc_html( wc_attribute_taxonomy_name( $tax->attribute_name ) ); ?>&amp;post_type=product"><?php echo esc_html( $tax->attribute_label ); ?></a></strong>

													<div class="row-actions"><span class="edit"><a href="<?php echo esc_url( add_query_arg( 'edit', $tax->attribute_id, 'edit.php?post_type=product&amp;page=product_attributes' ) ); ?>"><?php _e( 'Edit', 'woocommerce' ); ?></a> | </span><span class="delete"><a class="delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'delete', $tax->attribute_id, 'edit.php?post_type=product&amp;page=product_attributes' ), 'woocommerce-delete-attribute_' . $tax->attribute_id ) ); ?>"><?php _e( 'Delete', 'woocommerce' ); ?></a></span></div>
												</td>
												<td><?php echo esc_html( $tax->attribute_name ); ?></td>
												<td><?php echo esc_html( ucfirst( $tax->attribute_type ) ); ?> <?php echo $tax->attribute_public ? '(' . __( 'Public', 'woocommerce' ) . ')' : ''; ?></td>
												<td><?php
													switch ( $tax->attribute_orderby ) {
														case 'name' :
															_e( 'Name', 'woocommerce' );
														break;
														case 'name_num' :
															_e( 'Name (numeric)', 'woocommerce' );
														break;
														case 'id' :
															_e( 'Term ID', 'woocommerce' );
														break;
														default:
															_e( 'Custom ordering', 'woocommerce' );
														break;
													}
												?></td>
												<td class="attribute-terms"><?php
													$taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );

													if ( taxonomy_exists( $taxonomy ) ) {
														$terms = get_terms( $taxonomy, 'hide_empty=0' );

														switch ( $tax->attribute_orderby ) {
															case 'name_num' :
																usort( $terms, '_wc_get_product_terms_name_num_usort_callback' );
															break;
															case 'parent' :
																usort( $terms, '_wc_get_product_terms_parent_usort_callback' );
															break;
														}

														$terms_string = implode( ', ', wp_list_pluck( $terms, 'name' ) );
														if ( $terms_string ) {
															echo $terms_string;
														} else {
															echo '<span class="na">&ndash;</span>';
														}
													} else {
														echo '<span class="na">&ndash;</span>';
													}
												?></td>
												<td class="attribute-actions"><a href="edit-tags.php?taxonomy=<?php echo esc_html( wc_attribute_taxonomy_name( $tax->attribute_name ) ); ?>&amp;post_type=product" class="button alignright tips configure-terms" data-tip="<?php esc_attr_e( 'Configure terms', 'woocommerce' ); ?>"><?php _e( 'Configure terms', 'woocommerce' ); ?></a></td>
											</tr><?php
										endforeach;
									else :
										?><tr><td colspan="6"><?php _e( 'No attributes currently exist.', 'woocommerce' ) ?></td></tr><?php
									endif;
								?>
							</tbody>
						</table>
					</div>
				</div>
				<div id="col-left">
					<div class="col-wrap">
						<div class="form-wrap">
							<h3><?php _e( 'Add New Attribute', 'woocommerce' ); ?></h3>
							<p><?php _e( 'Attributes let you define extra product data, such as size or colour. You can use these attributes in the shop sidebar using the "layered nav" widgets. Please note: you cannot rename an attribute later on.', 'woocommerce' ); ?></p>
							<form action="edit.php?post_type=product&amp;page=product_attributes" method="post">
								<div class="form-field">
									<label for="attribute_label"><?php _e( 'Name', 'woocommerce' ); ?></label>
									<input name="attribute_label" id="attribute_label" type="text" value="" />
									<p class="description"><?php _e( 'Name for the attribute (shown on the front-end).', 'woocommerce' ); ?></p>
								</div>

								<div class="form-field">
									<label for="attribute_name"><?php _e( 'Slug', 'woocommerce' ); ?></label>
									<input name="attribute_name" id="attribute_name" type="text" value="" maxlength="28" />
									<p class="description"><?php _e( 'Unique slug/reference for the attribute; must be shorter than 28 characters.', 'woocommerce' ); ?></p>
								</div>

								<div class="form-field">
									<label for="attribute_public"><input name="attribute_public" id="attribute_public" type="checkbox" value="1" /> <?php _e( 'Enable Archives?', 'woocommerce' ); ?></label>

									<p class="description"><?php _e( 'Enable this if you want this attribute to have product archives in your store.', 'woocommerce' ); ?></p>
								</div>

								<div class="form-field">
									<label for="attribute_type"><?php _e( 'Type', 'woocommerce' ); ?></label>
									<select name="attribute_type" id="attribute_type">
										<?php foreach ( wc_get_attribute_types() as $key => $value ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $value ); ?></option>
										<?php endforeach; ?>

										<?php

											/**
											 * Deprecated action in favor of product_attributes_type_selector filter.
											 *
											 * @deprecated 2.4.0
											 */
											do_action( 'woocommerce_admin_attribute_types' );
										?>
									</select>
									<p class="description"><?php _e( 'Determines how you select attributes for products. Under admin panel -> products -> product data -> attributes -> values, <strong>Text</strong> allows manual entry whereas <strong>select</strong> allows pre-configured terms in a drop-down list.', 'woocommerce' ); ?></p>
								</div>

								<div class="form-field">
									<label for="attribute_orderby"><?php _e( 'Default sort order', 'woocommerce' ); ?></label>
									<select name="attribute_orderby" id="attribute_orderby">
										<option value="menu_order"><?php _e( 'Custom ordering', 'woocommerce' ); ?></option>
										<option value="name"><?php _e( 'Name', 'woocommerce' ); ?></option>
										<option value="name_num"><?php _e( 'Name (numeric)', 'woocommerce' ); ?></option>
										<option value="id"><?php _e( 'Term ID', 'woocommerce' ); ?></option>
									</select>
									<p class="description"><?php _e( 'Determines the sort order of the terms on the frontend shop product pages. If using custom ordering, you can drag and drop the terms in this attribute.', 'woocommerce' ); ?></p>
								</div>

								<p class="submit"><input type="submit" name="add_new_attribute" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Add Attribute', 'woocommerce' ); ?>"></p>
								<?php wp_nonce_field( 'woocommerce-add-new_attribute' ); ?>
							</form>
						</div>
					</div>
				</div>
			</div>
			<script type="text/javascript">
			/* <![CDATA[ */

				jQuery( 'a.delete' ).click( function() {
					if ( window.confirm( '<?php _e( "Are you sure you want to delete this attribute?", "woocommerce" ); ?>' ) ) {
						return true;
					}
					return false;
				});

			/* ]]> */
			</script>
		</div>
		<?php
	}
}
