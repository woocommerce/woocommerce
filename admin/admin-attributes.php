<?php
/**
 * Functions used for the attributes section in WordPress Admin
 * 
 * The attributes section lets users add custom attributes to assign to products - they can also be used in the layered nav widget.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */

/**
 * Attributes admin panel
 * 
 * Shows the created attributes and lets you add new ones.
 * The added attributes are stored in the database and can be used for layered navigation.
 */
function woocommerce_attributes() {
	
	global $wpdb, $woocommerce;
	
	if (isset($_POST['add_new_attribute']) && $_POST['add_new_attribute']) :
		check_admin_referer( 'woocommerce-add-new_attribute' );
		$attribute_name = (string) sanitize_title($_POST['attribute_name']);
		$attribute_type = (string) $_POST['attribute_type'];
		$attribute_label = (string) $_POST['attribute_label'];
		
		if ($attribute_name && strlen($attribute_name)<30 && $attribute_type && !taxonomy_exists( $woocommerce->attribute_taxonomy_name($attribute_name) )) :
		
			$wpdb->insert( $wpdb->prefix . "woocommerce_attribute_taxonomies", array( 'attribute_name' => $attribute_name, 'attribute_label' => $attribute_label, 'attribute_type' => $attribute_type ), array( '%s', '%s' ) );
						
			wp_safe_redirect( get_admin_url() . 'admin.php?page=woocommerce_attributes' );
			exit;
			
		endif;
		
	elseif (isset($_POST['save_attribute']) && $_POST['save_attribute'] && isset($_GET['edit'])) :
		
		$edit = absint($_GET['edit']);
		check_admin_referer( 'woocommerce-save-attribute_' . $edit );
		if ($edit>0) :
		
			$attribute_type = $_POST['attribute_type'];
			$attribute_label = (string) $_POST['attribute_label'];
		
			$wpdb->update( $wpdb->prefix . "woocommerce_attribute_taxonomies", array( 'attribute_type' => $attribute_type, 'attribute_label' => $attribute_label ), array( 'attribute_id' => $_GET['edit'] ), array( '%s', '%s' ) );
		
		endif;
		
		wp_safe_redirect( get_admin_url() . 'admin.php?page=woocommerce_attributes' );
		exit;
			
	elseif (isset($_GET['delete'])) :
		check_admin_referer( 'woocommerce-delete-attribute_' . absint( $_GET['delete'] ) );
		$delete = absint($_GET['delete']);
		
		if ($delete>0) :
		
			$att_name = $wpdb->get_var("SELECT attribute_name FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_id = '$delete'");
			
			if ($att_name && $wpdb->query("DELETE FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_id = '$delete'")) :
				
				$taxonomy = $woocommerce->attribute_taxonomy_name($att_name); 
				
				if (taxonomy_exists($taxonomy)) :
				
					$terms = get_terms($taxonomy, 'orderby=name&hide_empty=0'); 
					foreach ($terms as $term) {
						wp_delete_term( $term->term_id, $taxonomy );
					}
				
				endif;
				
				wp_safe_redirect( get_admin_url() . 'admin.php?page=woocommerce_attributes' );
				exit;
										
			endif;
			
		endif;
		
	endif;
	
	if (isset($_GET['edit']) && $_GET['edit'] > 0) :
		woocommerce_edit_attribute();
	else :	
		woocommerce_add_attribute();
	endif;
	
}

/**
 * Edit Attribute admin panel
 * 
 * Shows the interface for changing an attributes type between select and text
 */
function woocommerce_edit_attribute() {
	
	global $wpdb;
	
	$edit = absint($_GET['edit']);
		
	$att_type = $wpdb->get_var("SELECT attribute_type FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_id = '$edit'");	
	$att_label = $wpdb->get_var("SELECT attribute_label FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_id = '$edit'");		
	?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-attributes" id="icon-woocommerce"><br/></div>
	    <h2><?php _e('Attributes', 'woothemes') ?></h2>
	    <br class="clear" />
	    <div id="col-container">
	    	<div id="col-left">
	    		<div class="col-wrap">
	    			<div class="form-wrap">
	    				<h3><?php _e('Edit Attribute', 'woothemes') ?></h3>
	    				<p><?php _e('Attribute taxonomy names cannot be changed; you may only change an attributes type.', 'woothemes') ?></p>
	    				<form action="admin.php?page=woocommerce_attributes&amp;edit=<?php echo absint( $edit ); ?>" method="post">
							
							<div class="form-field">
								<label for="attribute_label"><?php _e('Attribute Label', 'woothemes'); ?></label>
								<input name="attribute_label" id="attribute_label" type="text" value="<?php echo esc_attr( $att_label ); ?>" />
								<p class="description"><?php _e('Label for the attribute (shown on the front-end).', 'woothemes'); ?></p>
							</div>
							<div class="form-field">
								<label for="attribute_type"><?php _e('Attribute type', 'woothemes'); ?></label>
								<select name="attribute_type" id="attribute_type">
									<option value="select" <?php selected($att_type, 'select'); ?>><?php _e('Select', 'woothemes') ?></option>
									<option value="text" <?php selected($att_type, 'text'); ?>><?php _e('Text', 'woothemes') ?></option>										
								</select>
							</div>
							
							<p class="submit"><input type="submit" name="save_attribute" id="submit" class="button" value="<?php _e('Save Attribute', 'woothemes'); ?>"></p>
							<?php wp_nonce_field( 'woocommerce-save-attribute_' . $edit ); ?>
	    				</form>
	    			</div>
	    		</div>
	    	</div>
	    </div>
	</div>
	<?php
	
}

/**
 * Add Attribute admin panel
 * 
 * Shows the interface for adding new attributes
 */
function woocommerce_add_attribute() {
	global $woocommerce;
	?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-attributes" id="icon-woocommerce"><br/></div>
	    <h2><?php _e('Attributes', 'woothemes') ?></h2>
	    <br class="clear" />
	    <div id="col-container">
	    	<div id="col-right">
	    		<div class="col-wrap">
		    		<table class="widefat fixed" style="width:100%">
				        <thead>
				            <tr>
				                <th scope="col"><?php _e('Name', 'woothemes') ?></th>
				                <th scope="col"><?php _e('Label', 'woothemes') ?></th>
				                <th scope="col"><?php _e('Type', 'woothemes') ?></th>
				                <th scope="col" colspan="2"><?php _e('Terms', 'woothemes') ?></th>
				            </tr>
				        </thead>
				        <tbody>
				        	<?php
				        		$attribute_taxonomies = $woocommerce->get_attribute_taxonomies();
				        		if ( $attribute_taxonomies ) :
				        			foreach ($attribute_taxonomies as $tax) :
				        				$att_title = $tax->attribute_name;
				        				if ( isset( $tax->attribute_label ) ) { $att_title = $tax->attribute_label; }
				        				?><tr>

				        					<td><a href="edit-tags.php?taxonomy=<?php echo $woocommerce->attribute_taxonomy_name($tax->attribute_name); ?>&amp;post_type=product"><?php echo $tax->attribute_name; ?></a>
				        					
				        					<div class="row-actions"><span class="edit"><a href="<?php echo esc_url( add_query_arg('edit', $tax->attribute_id, 'admin.php?page=woocommerce_attributes') ); ?>"><?php _e('Edit', 'woothemes'); ?></a> | </span><span class="delete"><a class="delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg('delete', $tax->attribute_id, 'admin.php?page=woocommerce_attributes'), 'woocommerce-delete-attribute_' . $tax->attribute_id ) ); ?>"><?php _e('Delete', 'woothemes'); ?></a></span></div>				        					
				        					</td>
				        					<td><?php echo esc_html( ucwords( $att_title ) ); ?></td>
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
				        					<td><a href="edit-tags.php?taxonomy=<?php echo $woocommerce->attribute_taxonomy_name($tax->attribute_name); ?>&amp;post_type=product" class="button alignright"><?php _e('Configure&nbsp;terms', 'woothemes'); ?></a></td>
				        				</tr><?php
				        			endforeach;
				        		else :
				        			?><tr><td colspan="5"><?php _e('No attributes currently exist.', 'woothemes') ?></td></tr><?php
				        		endif;
				        	?>
				        </tbody>
				    </table>
	    		</div>
	    	</div>
	    	<div id="col-left">
	    		<div class="col-wrap">
	    			<div class="form-wrap">
	    				<h3><?php _e('Add New Attribute', 'woothemes') ?></h3>
	    				<p><?php _e('Attributes let you define extra product data, such as size or colour. You can use these attributes in the shop sidebar using the "layered nav" widgets. Please note: you cannot rename an attribute later on.', 'woothemes') ?></p>
	    				<form action="admin.php?page=woocommerce_attributes" method="post">
							<div class="form-field">
								<label for="attribute_name"><?php _e('Attribute Name', 'woothemes'); ?></label>
								<input name="attribute_name" id="attribute_name" type="text" value="" maxlength="29" />
								<p class="description"><?php _e('Unique name/reference for the attribute; must be shorter than 28 characters.', 'woothemes'); ?></p>
							</div>
							<div class="form-field">
								<label for="attribute_label"><?php _e('Attribute Label', 'woothemes'); ?></label>
								<input name="attribute_label" id="attribute_label" type="text" value="" />
								<p class="description"><?php _e('Label for the attribute (shown on the front-end).', 'woothemes'); ?></p>
							</div>
							<div class="form-field">
								<label for="attribute_type"><?php _e('Attribute type', 'woothemes'); ?></label>
								<select name="attribute_type" id="attribute_type">
									<option value="select"><?php _e('Select', 'woothemes') ?></option>
									<option value="text"><?php _e('Text', 'woothemes') ?></option>										
								</select>
								<p class="description"><?php _e('Determines how you select attributes for products. <strong>Text</strong> allows manual entry via the product page, whereas <strong>select</strong> attribute terms can be defined from this section. If you plan on using an attribute for variations use <strong>select</strong>.', 'woothemes'); ?></p>
							</div>
							
							<p class="submit"><input type="submit" name="add_new_attribute" id="submit" class="button" value="<?php _e('Add Attribute', 'woothemes'); ?>"></p>
							<?php wp_nonce_field( 'woocommerce-add-new_attribute' ); ?>
	    				</form>
	    			</div>
	    		</div>
	    	</div>
	    </div>
	    <script type="text/javascript">
		/* <![CDATA[ */
		
			jQuery('a.delete').click(function(){
	    		var answer = confirm ("<?php _e('Are you sure you want to delete this attribute?', 'woothemes'); ?>");
				if (answer) return true;
				return false;
	    	});
		    	
		/* ]]> */
		</script>
	</div>
	<?php
}