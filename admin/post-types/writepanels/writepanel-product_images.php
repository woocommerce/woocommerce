<?php
/**
 * Product Images
 *
 * Function for displaying the product images meta box.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/WritePanels
 * @version     1.7.0
 */

/**
 * Display the product images meta box.
 *
 * @access public
 * @return void
 */
function woocommerce_product_images_box() {
	global $post;
	
	$thumbnail_id 	= get_post_thumbnail_id( $post->ID );
	$ajax_nonce 	= wp_create_nonce( "set_post_thumbnail-$post->ID" );
	?>
	<div id="product_images_container">
		<ul>
			<!-- Current images -->
			<?php 
				$attachments =& get_children( 'post_parent=' . $post->ID . '&numberposts=-1&post_type=attachment&orderby=menu_order&order=ASC&post_mime_type=image' ); 
				
				foreach ( $attachments as $attachment_id => $attachment ) {

					echo '<li class="image" data-post_id="' . $attachment_id . '"><a href="' . admin_url( 'media-upload.php?post_id=' . $post->ID . '&attachment_id=' . $attachment_id . '&tab=library&width=640&height=553&TB_iframe=1' ) . '" class="thickbox" onclick="return false;">' . wp_get_attachment_image( $attachment_id, 'full' ) . '<span class="loading"></span></a></li>';
					
				}
			?>
			
			<!-- Uploader section -->
			<li id="plupload-upload-ui" class="hide-if-no-js add">
				<div id="drag-drop-area">
					<p class="drag-drop-info"><?php _e('Drop files here'); ?></p>
					<p><?php _ex('or', 'Uploader: Drop files here - or - Select Files'); ?></p>
					<p class="drag-drop-buttons"><input id="plupload-browse-button" type="button" value="<?php esc_attr_e('Select Files'); ?>" class="button" /></p>
				</div>	
			</li>
		</ul>
	</div>
	<?php
	// Drag and drop code adapted from Drag & Drop Featured Image by Jonathan Lundström
	$plupload_init = array(
		'runtimes'            => 'html5,silverlight,flash,html4',
		'browse_button'       => 'plupload-browse-button',
		'container'           => 'plupload-upload-ui',
		'drop_element'        => 'drag-drop-area',
		'file_data_name'      => 'async-upload',            
		'multiple_queues'     => true,
		'max_file_size'       => wp_max_upload_size() . 'b',
		'url'                 => admin_url('admin-ajax.php'),
		'flash_swf_url'       => includes_url('js/plupload/plupload.flash.swf'),
		'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
		'filters'             => array( array( 'title' => __( 'Allowed Files' ), 'extensions' => '*') ),
		'multipart'           => true,
		'urlstream_upload'    => true,
		'multipart_params'    => array(
			'_ajax_nonce' 		=> wp_create_nonce( 'product-images-box-upload' ),
			'action'      		=> 'woocommerce_product_images_box_upload',
			'post_id'	  		=> $post->ID
		)
	);

	// Apply filters to initiate plupload:
	$plupload_init = apply_filters( 'plupload_init', $plupload_init ); 
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($){
		
			// Attribute ordering
			$('#product_images_container ul').sortable({
				items: 'li.image',
				cursor: 'move',
				scrollSensitivity:40,
				forcePlaceholderSize: true,
				forceHelperSize: false,
				helper: 'clone',
				opacity: 0.65,
				placeholder: 'wc-metabox-sortable-placeholder',
				start:function(event,ui){
					ui.item.css('background-color','#f6f6f6');
				},
				stop:function(event,ui){
					ui.item.removeAttr('style');
				},
				update: function(event, ui) {	
					$('#product_images_container ul li').css('cursor','default');
					$('#product_images_container ul').sortable('disable');
					
					var post_id				= <?php echo $post->ID; ?>;
					var attachment_id 		= ui.item.attr( 'data-post_id' );
					var prev_attachment_id 	= ui.item.prev().attr( 'data-post_id' );
					var next_attachment_id 	= ui.item.next().attr( 'data-post_id' );	
								
					// show spinner
					ui.item.addClass('loading');
					
					// go do the sorting stuff via ajax
					jQuery.post( ajaxurl, { 
							action: 'woocommerce_product_image_ordering', 
							post_id: post_id, 
							attachment_id: attachment_id, 
							prev_attachment_id: prev_attachment_id, 
							next_attachment_id: next_attachment_id,
							_ajax_nonce: '<?php echo wp_create_nonce( 'product-image-ordering' ); ?>'
						}, function( response ) {		
							ui.item.removeClass('loading');
							$('#product_images_container ul li').css('cursor','move');
							$('#product_images_container ul').sortable('enable');
						}
					);
				}
			});
		
			// Drag and drop uploading of images
			var uploader = new plupload.Uploader(<?php echo json_encode( $plupload_init ); ?>);

			// Check for drag'n'drop functionality:
			uploader.bind('Init', function(up){
				var uploaddiv = $('#plupload-upload-ui');
				
				// Add classes and bind actions:
				if(up.features.dragdrop){
					uploaddiv.addClass('drag-drop');
					$('#drag-drop-area')
						.bind('dragover.wp-uploader', function() { uploaddiv.addClass('drag-over'); })
						.bind('dragleave.wp-uploader, drop.wp-uploader', function(){ uploaddiv.removeClass('drag-over'); });

				} else{
					uploaddiv.removeClass('drag-drop');
					$('#drag-drop-area').unbind('.wp-uploader');
				}
			});

			// Initiate uploading script:
			uploader.init();
			
			// File queue handler
			uploader.bind('FilesAdded', function(up, files){
				var hundredmb = 100 * 1024 * 1024, max = parseInt(up.settings.max_file_size, 10);
				
				// Loop through files:
				plupload.each(files, function(file){
					if ( max > hundredmb && file.size > hundredmb && up.runtime != 'html5' ) {
						alert( "<?php _e( 'The file you selected exceeds the maximum filesize specified in this installation.', 'woocommerce' ); ?>" );
					}
				});

				// Refresh and start:
				up.refresh();
				up.start();
				
				// Block the UI
				$('#product_images_container').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });
			});
			
			// Handle new uploads
			uploader.bind( 'FileUploaded', function( up, file, response ) {
				
				response = $.parseJSON( response.response );
				
				if ( response.error ) {
				
					alert( response.error );
					
				} else {
				
					$('#plupload-upload-ui').before('<li class="image" data-post_id="' + response.post_id + '"><img src="' + response.src + '" /><span class="loading"></span></li>');
				
				}
				
				$('#product_images_container').unblock();

			});
			
		});
	</script>
	<?php
}