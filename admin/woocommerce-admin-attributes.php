<?php
/**
 * Functions used for the attributes section in WordPress Admin
 *
 * The attributes section lets users add custom attributes to assign to products - they can also be used in the layered nav widget.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     1.6.4
 */


/**
 * Attributes admin panel
 *
 * Shows the created attributes and lets you add new ones.
 * The added attributes are stored in the database and can be used for layered navigation.
 *
 * @access public
 * @return void
 */
function woocommerce_attributes() {
	global $wpdb, $woocommerce;

	if ( ! empty( $_POST['add_new_attribute'] ) ) {

		check_admin_referer( 'woocommerce-add-new_attribute' );

		$attribute_name 	= sanitize_title( esc_attr( $_POST['attribute_name'] ) );
		$attribute_type 	= esc_attr( $_POST['attribute_type'] );
		$attribute_label 	= esc_attr( $_POST['attribute_label'] );

		if ( ! $attribute_label )
			$attribute_label = ucwords( $attribute_name );

		if ( ! $attribute_name )
			$attribute_name = sanitize_title( $attribute_label );

		if ( $attribute_name && strlen( $attribute_name ) < 30 && $attribute_type && ! taxonomy_exists( $woocommerce->attribute_taxonomy_name( $attribute_name ) ) ) {

			$wpdb->insert(
				$wpdb->prefix . "woocommerce_attribute_taxonomies",
				array(
					'attribute_name' 	=> $attribute_name,
					'attribute_label' 	=> $attribute_label,
					'attribute_type' 	=> $attribute_type
				)
			);

			wp_safe_redirect( get_admin_url() . 'edit.php?post_type=product&page=woocommerce_attributes' );
			exit;
		}

	} elseif ( ! empty( $_POST['save_attribute'] ) && ! empty( $_GET['edit'] ) ) {

		$edit = absint( $_GET['edit'] );
		check_admin_referer( 'woocommerce-save-attribute_' . $edit );

		$attribute_name 	= sanitize_title( esc_attr( $_POST['attribute_name'] ) );
		$attribute_type	 	= esc_attr( $_POST['attribute_type'] );
		$attribute_label 	= esc_attr( $_POST['attribute_label'] );

		if ( ! $attribute_label )
			$attribute_label = ucwords( $attribute_name );

		if ( ! $attribute_name )
			$attribute_name = sanitize_title( $attribute_label );

		$old_attribute_name = sanitize_title( $wpdb->get_var( "SELECT attribute_name FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_id = $edit" ) );

		if ( $old_attribute_name != $attribute_name && taxonomy_exists( $woocommerce->attribute_taxonomy_name( $attribute_name ) ) ) {

			echo '<div id="woocommerce_errors" class="error fade"><p>' . __( 'Taxonomy exists - please change the slug', 'woocommerce' ) . '</p></div>';

		} elseif ( $attribute_name && strlen( $attribute_name ) < 30 && $attribute_type ) {

			$wpdb->update(
				$wpdb->prefix . "woocommerce_attribute_taxonomies",
				array(
					'attribute_name' 	=> $attribute_name,
					'attribute_label' 	=> $attribute_label,
					'attribute_type' 	=> $attribute_type
				),
				array(
					'attribute_id' 		=> $edit
				)
			);

			if ( $old_attribute_name != $attribute_name && ! empty( $old_attribute_name ) ) {

				// Update taxonomies in the wp term taxonomy table
				$wpdb->update(
					$wpdb->term_taxonomy,
					array(
						'taxonomy' 	=> $woocommerce->attribute_taxonomy_name( $attribute_name )
					),
					array(
						'taxonomy' 	=> $woocommerce->attribute_taxonomy_name( $old_attribute_name )
					)
				);

				// Update taxonomy ordering term meta
				$wpdb->update(
					$wpdb->prefix . "woocommerce_termmeta",
					array(
						'meta_key' 	=> 'order_pa_' . sanitize_title( $attribute_name )
					),
					array(
						'meta_key' 	=> 'order_pa_' . sanitize_title( $old_attribute_name )
					)
				);

				// Update product attributes which use this taxonomy
				$old_attribute_name_length = strlen($old_attribute_name) + 3;
				$attribute_name_length = strlen($attribute_name) + 3;

				$wpdb->query( "
					UPDATE {$wpdb->postmeta}
					SET meta_value = replace( meta_value, 's:{$old_attribute_name_length}:\"pa_{$old_attribute_name}\"', 's:{$attribute_name_length}:\"pa_{$attribute_name}\"' )
					WHERE meta_key = '_product_attributes'"
					);

				// Update variations which use this taxonomy
				$wpdb->update(
					$wpdb->postmeta,
					array(
						'meta_key' 	=> 'attribute_' . sanitize_title( $attribute_name )
					),
					array(
						'meta_key' 	=> 'attribute_' . sanitize_title( $old_attribute_name )
					)
				);

				// Clear post cache
				wp_cache_flush();
			}

			wp_safe_redirect( get_admin_url() . 'edit.php?post_type=product&page=woocommerce_attributes' );
			exit;

		}

	} elseif ( ! empty( $_GET['delete'] ) ) {

		$delete = absint( $_GET['delete'] );
		check_admin_referer( 'woocommerce-delete-attribute_' . $delete );

		$att_name = $wpdb->get_var( "SELECT attribute_name FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_id = $delete" );

		if ( $att_name && $wpdb->query( "DELETE FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_id = $delete" ) ) {

			$taxonomy = $woocommerce->attribute_taxonomy_name( $att_name );

			if ( taxonomy_exists( $taxonomy ) ) {

				$terms = get_terms( $taxonomy, 'orderby=name&hide_empty=0');
				foreach ( $terms as $term )
					wp_delete_term( $term->term_id, $taxonomy );

			}

			wp_safe_redirect( get_admin_url() . 'edit.php?post_type=product&page=woocommerce_attributes' );
			exit;

		}

	}

	if ( ! empty( $_GET['edit'] ) && $_GET['edit'] > 0 )
		woocommerce_edit_attribute();
	else
		woocommerce_add_attribute();

}


/**
 * Edit Attribute admin panel
 *
 * Shows the interface for changing an attributes type between select and text
 *
 * @access public
 * @return void
 */
function woocommerce_edit_attribute() {
	global $wpdb;

	$edit = absint( $_GET['edit'] );

	$attribute_to_edit = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_id = '$edit'");

	$att_type 	= $attribute_to_edit->attribute_type;
	$att_label 	= $attribute_to_edit->attribute_label;
	$att_name 	= $attribute_to_edit->attribute_name;
	?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-attributes" id="icon-woocommerce"><br/></div>
	    <h2><?php _e('Edit Attribute', 'woocommerce') ?></h2>
		<form action="admin.php?page=woocommerce_attributes&amp;edit=<?php echo absint( $edit ); ?>" method="post">
			<table class="form-table">
				<tbody>
					<tr class="form-field form-required">
						<th scope="row" valign="top">
							<label for="attribute_label"><?php _e('Name', 'woocommerce'); ?></label>
						</th>
						<td>
							<input name="attribute_label" id="attribute_label" type="text" value="<?php echo esc_attr( $att_label ); ?>" />
							<p class="description"><?php _e('Name for the attribute (shown on the front-end).', 'woocommerce'); ?></p>
						</td>
					</tr>
					<tr class="form-field form-required">
						<th scope="row" valign="top">
							<label for="attribute_name"><?php _e('Slug', 'woocommerce'); ?></label>
						</th>
						<td>
							<input name="attribute_name" id="attribute_name" type="text" value="<?php echo esc_attr( $att_name ); ?>" maxlength="28" />
							<p class="description"><?php _e('Unique slug/reference for the attribute; must be shorter than 28 characters.', 'woocommerce'); ?></p>
						</td>
					</tr>
					<tr class="form-field form-required">
						<th scope="row" valign="top">
							<label for="attribute_type"><?php _e('Type', 'woocommerce'); ?></label>
						</th>
						<td>
							<select name="attribute_type" id="attribute_type">
								<option value="select" <?php selected( $att_type, 'select' ); ?>><?php _e('Select', 'woocommerce') ?></option>
								<option value="text" <?php selected( $att_type, 'text' ); ?>><?php _e('Text', 'woocommerce') ?></option>
							</select>
							<p class="description"><?php _e('Determines how you select attributes for products. <strong>Text</strong> allows manual entry via the product page, whereas <strong>select</strong> attribute terms can be defined from this section. If you plan on using an attribute for variations use <strong>select</strong>.', 'woocommerce'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit"><input type="submit" name="save_attribute" id="submit" class="button-primary" value="<?php _e('Update', 'woocommerce'); ?>"></p>
			<?php wp_nonce_field( 'woocommerce-save-attribute_' . $edit ); ?>
		</form>
	</div>
	<?php

}


/**
 * Add Attribute admin panel
 *
 * Shows the interface for adding new attributes
 *
 * @access public
 * @return void
 */
function woocommerce_add_attribute() {
	global $woocommerce;
	?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-attributes" id="icon-woocommerce"><br/></div>
	    <h2><?php _e('Attributes', 'woocommerce') ?></h2>
	    <br class="clear" />
	    <div id="col-container">
	    	<div id="col-right">
	    		<div class="col-wrap">
		    		<table class="widefat fixed" style="width:100%">
				        <thead>
				            <tr>
				                <th scope="col"><?php _e('Name', 'woocommerce') ?></th>
				                <th scope="col"><?php _e('Slug', 'woocommerce') ?></th>
				                <th scope="col"><?php _e('Type', 'woocommerce') ?></th>
				                <th scope="col" colspan="2"><?php _e('Terms', 'woocommerce') ?></th>
				            </tr>
				        </thead>
				        <tbody>
				        	<?php
				        		$attribute_taxonomies = $woocommerce->get_attribute_taxonomies();
				        		if ( $attribute_taxonomies ) :
				        			foreach ($attribute_taxonomies as $tax) :
				        				?><tr>

				        					<td><a href="edit-tags.php?taxonomy=<?php echo esc_html($woocommerce->attribute_taxonomy_name($tax->attribute_name)); ?>&amp;post_type=product"><?php echo esc_html( $tax->attribute_label ); ?></a>

				        					<div class="row-actions"><span class="edit"><a href="<?php echo esc_url( add_query_arg('edit', $tax->attribute_id, 'admin.php?page=woocommerce_attributes') ); ?>"><?php _e('Edit', 'woocommerce'); ?></a> | </span><span class="delete"><a class="delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg('delete', $tax->attribute_id, 'admin.php?page=woocommerce_attributes'), 'woocommerce-delete-attribute_' . $tax->attribute_id ) ); ?>"><?php _e('Delete', 'woocommerce'); ?></a></span></div>
				        					</td>
				        					<td><?php echo esc_html( $tax->attribute_name ); ?></td>
				        					<td><?php echo esc_html( ucwords( $tax->attribute_type ) ); ?></td>
				        					<td><?php
				        						if (taxonomy_exists($woocommerce->attribute_taxonomy_name($tax->attribute_name))) :
					        						$terms_array = array();
					        						$terms = get_terms( $woocommerce->attribute_taxonomy_name($tax->attribute_name), 'orderby=name&hide_empty=0' );
					        						if ($terms) :
						        						foreach ($terms as $term) :
															$terms_array[] = $term->name;
														endforeach;
														echo implode(', ', $terms_array);
													else :
														echo '<span class="na">&ndash;</span>';
													endif;
												else :
													echo '<span class="na">&ndash;</span>';
												endif;
				        					?></td>
				        					<td><a href="edit-tags.php?taxonomy=<?php echo esc_html($woocommerce->attribute_taxonomy_name($tax->attribute_name)); ?>&amp;post_type=product" class="button alignright"><?php _e('Configure&nbsp;terms', 'woocommerce'); ?></a></td>
				        				</tr><?php
				        			endforeach;
				        		else :
				        			?><tr><td colspan="5"><?php _e('No attributes currently exist.', 'woocommerce') ?></td></tr><?php
				        		endif;
				        	?>
				        </tbody>
				    </table>
	    		</div>
	    	</div>
	    	<div id="col-left">
	    		<div class="col-wrap">
	    			<div class="form-wrap">
	    				<h3><?php _e('Add New Attribute', 'woocommerce') ?></h3>
	    				<p><?php _e('Attributes let you define extra product data, such as size or colour. You can use these attributes in the shop sidebar using the "layered nav" widgets. Please note: you cannot rename an attribute later on.', 'woocommerce') ?></p>
	    				<form action="admin.php?page=woocommerce_attributes" method="post">
							<div class="form-field">
								<label for="attribute_label"><?php _e('Name', 'woocommerce'); ?></label>
								<input name="attribute_label" id="attribute_label" type="text" value="" />
								<p class="description"><?php _e('Name for the attribute (shown on the front-end).', 'woocommerce'); ?></p>
							</div>

							<div class="form-field">
								<label for="attribute_name"><?php _e('Slug', 'woocommerce'); ?></label>
								<input name="attribute_name" id="attribute_name" type="text" value="" maxlength="28" />
								<p class="description"><?php _e('Unique slug/reference for the attribute; must be shorter than 28 characters.', 'woocommerce'); ?></p>
							</div>

							<div class="form-field">
								<label for="attribute_type"><?php _e('Type', 'woocommerce'); ?></label>
								<select name="attribute_type" id="attribute_type">
									<option value="select"><?php _e('Select', 'woocommerce') ?></option>
									<option value="text"><?php _e('Text', 'woocommerce') ?></option>
								</select>
								<p class="description"><?php _e('Determines how you select attributes for products. <strong>Text</strong> allows manual entry via the product page, whereas <strong>select</strong> attribute terms can be defined from this section. If you plan on using an attribute for variations use <strong>select</strong>.', 'woocommerce'); ?></p>
							</div>

							<p class="submit"><input type="submit" name="add_new_attribute" id="submit" class="button" value="<?php _e('Add Attribute', 'woocommerce'); ?>"></p>
							<?php wp_nonce_field( 'woocommerce-add-new_attribute' ); ?>
	    				</form>
	    			</div>
	    		</div>
	    	</div>
	    </div>
	    <script type="text/javascript">
		/* <![CDATA[ */

			jQuery('a.delete').click(function(){
	    		var answer = confirm ("<?php _e('Are you sure you want to delete this attribute?', 'woocommerce'); ?>");
				if (answer) return true;
				return false;
	    	});

		/* ]]> */
		</script>
	</div>
	<?php
}