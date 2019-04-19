/*global woocommerce_admin_meta_boxes */
jQuery( function( $ ) {

	// Scroll to first checked category
	// https://github.com/scribu/wp-category-checklist-tree/blob/d1c3c1f449e1144542efa17dde84a9f52ade1739/category-checklist-tree.php
	$( function() {
		$( '[id$="-all"] > ul.categorychecklist' ).each( function() {
			var $list = $( this );
			var $firstChecked = $list.find( ':checked' ).first();

			if ( ! $firstChecked.length ) {
				return;
			}

			var pos_first   = $list.find( 'input' ).position().top;
			var pos_checked = $firstChecked.position().top;

			$list.closest( '.tabs-panel' ).scrollTop( pos_checked - pos_first + 5 );
		});
	});

	// Prevent enter submitting post form.
	$( '#upsell_product_data' ).bind( 'keypress', function( e ) {
		if ( e.keyCode === 13 ) {
			return false;
		}
	});

	// Type box.
	$( '.type_box' ).appendTo( '#woocommerce-product-data .hndle span' );

	$( function() {
		// Prevent inputs in meta box headings opening/closing contents.
		$( '#woocommerce-product-data' ).find( '.hndle' ).unbind( 'click.postboxes' );

		$( '#woocommerce-product-data' ).on( 'click', '.hndle', function( event ) {

			// If the user clicks on some form input inside the h3 the box should not be toggled.
			if ( $( event.target ).filter( 'input, option, label, select' ).length ) {
				return;
			}

			$( '#woocommerce-product-data' ).toggleClass( 'closed' );
		});
	});

	// Catalog Visibility.
	$( '#catalog-visibility' ).find( '.edit-catalog-visibility' ).click( function() {
		if ( $( '#catalog-visibility-select' ).is( ':hidden' ) ) {
			$( '#catalog-visibility-select' ).slideDown( 'fast' );
			$( this ).hide();
		}
		return false;
	});
	$( '#catalog-visibility' ).find( '.save-post-visibility' ).click( function() {
		$( '#catalog-visibility-select' ).slideUp( 'fast' );
		$( '#catalog-visibility' ).find( '.edit-catalog-visibility' ).show();

		var label = $( 'input[name=_visibility]:checked' ).attr( 'data-label' );

		if ( $( 'input[name=_featured]' ).is( ':checked' ) ) {
			label = label + ', ' + woocommerce_admin_meta_boxes.featured_label;
			$( 'input[name=_featured]' ).attr( 'checked', 'checked' );
		}

		$( '#catalog-visibility-display' ).text( label );
		return false;
	});
	$( '#catalog-visibility' ).find( '.cancel-post-visibility' ).click( function() {
		$( '#catalog-visibility-select' ).slideUp( 'fast' );
		$( '#catalog-visibility' ).find( '.edit-catalog-visibility' ).show();

		var current_visibility = $( '#current_visibility' ).val();
		var current_featured   = $( '#current_featured' ).val();

		$( 'input[name=_visibility]' ).removeAttr( 'checked' );
		$( 'input[name=_visibility][value=' + current_visibility + ']' ).attr( 'checked', 'checked' );

		var label = $( 'input[name=_visibility]:checked' ).attr( 'data-label' );

		if ( 'yes' === current_featured ) {
			label = label + ', ' + woocommerce_admin_meta_boxes.featured_label;
			$( 'input[name=_featured]' ).attr( 'checked', 'checked' );
		} else {
			$( 'input[name=_featured]' ).removeAttr( 'checked' );
		}

		$( '#catalog-visibility-display' ).text( label );
		return false;
	});

	// Product type specific options.
	$( 'select#product-type' ).change( function() {

		// Get value.
		var select_val = $( this ).val();

		if ( 'variable' === select_val ) {
			$( 'input#_manage_stock' ).change();
			$( 'input#_downloadable' ).prop( 'checked', false );
			$( 'input#_virtual' ).removeAttr( 'checked' );
		} else if ( 'grouped' === select_val ) {
			$( 'input#_downloadable' ).prop( 'checked', false );
			$( 'input#_virtual' ).removeAttr( 'checked' );
		} else if ( 'external' === select_val ) {
			$( 'input#_downloadable' ).prop( 'checked', false );
			$( 'input#_virtual' ).removeAttr( 'checked' );
		}

		show_and_hide_panels();

		$( 'ul.wc-tabs li:visible' ).eq( 0 ).find( 'a' ).click();

		$( document.body ).trigger( 'woocommerce-product-type-change', select_val, $( this ) );

	}).change();

	$( 'input#_downloadable, input#_virtual' ).change( function() {
		show_and_hide_panels();
	});

	function show_and_hide_panels() {
		var product_type    = $( 'select#product-type' ).val();
		var is_virtual      = $( 'input#_virtual:checked' ).length;
		var is_downloadable = $( 'input#_downloadable:checked' ).length;

		// Hide/Show all with rules.
		var hide_classes = '.hide_if_downloadable, .hide_if_virtual';
		var show_classes = '.show_if_downloadable, .show_if_virtual';

		$.each( woocommerce_admin_meta_boxes.product_types, function( index, value ) {
			hide_classes = hide_classes + ', .hide_if_' + value;
			show_classes = show_classes + ', .show_if_' + value;
		});

		$( hide_classes ).show();
		$( show_classes ).hide();

		// Shows rules.
		if ( is_downloadable ) {
			$( '.show_if_downloadable' ).show();
		}
		if ( is_virtual ) {
			$( '.show_if_virtual' ).show();
		}

        $( '.show_if_' + product_type ).show();

		// Hide rules.
		if ( is_downloadable ) {
			$( '.hide_if_downloadable' ).hide();
		}
		if ( is_virtual ) {
			$( '.hide_if_virtual' ).hide();
		}

		$( '.hide_if_' + product_type ).hide();

		$( 'input#_manage_stock' ).change();

		// Hide empty panels/tabs after display.
		$( '.woocommerce_options_panel' ).each( function() {
			var $children = $( this ).children( '.options_group' );

			if ( 0 === $children.length ) {
				return;
			}

			var $invisble = $children.filter( function() {
				return 'none' === $( this ).css( 'display' );
			});

			// Hide panel.
			if ( $invisble.length === $children.length ) {
				var $id = $( this ).prop( 'id' );
				$( '.product_data_tabs' ).find( 'li a[href="#' + $id + '"]' ).parent().hide();
			}
		});
	}

	// Sale price schedule.
	$( '.sale_price_dates_fields' ).each( function() {
		var $these_sale_dates = $( this );
		var sale_schedule_set = false;
		var $wrap = $these_sale_dates.closest( 'div, table' );

		$these_sale_dates.find( 'input' ).each( function() {
			if ( '' !== $( this ).val() ) {
				sale_schedule_set = true;
			}
		});

		if ( sale_schedule_set ) {
			$wrap.find( '.sale_schedule' ).hide();
			$wrap.find( '.sale_price_dates_fields' ).show();
		} else {
			$wrap.find( '.sale_schedule' ).show();
			$wrap.find( '.sale_price_dates_fields' ).hide();
		}
	});

	$( '#woocommerce-product-data' ).on( 'click', '.sale_schedule', function() {
		var $wrap = $( this ).closest( 'div, table' );

		$( this ).hide();
		$wrap.find( '.cancel_sale_schedule' ).show();
		$wrap.find( '.sale_price_dates_fields' ).show();

		return false;
	});
	$( '#woocommerce-product-data' ).on( 'click', '.cancel_sale_schedule', function() {
		var $wrap = $( this ).closest( 'div, table' );

		$( this ).hide();
		$wrap.find( '.sale_schedule' ).show();
		$wrap.find( '.sale_price_dates_fields' ).hide();
		$wrap.find( '.sale_price_dates_fields' ).find( 'input' ).val('');

		return false;
	});

	// File inputs.
	$( '#woocommerce-product-data' ).on( 'click','.downloadable_files a.insert', function() {
		$( this ).closest( '.downloadable_files' ).find( 'tbody' ).append( $( this ).data( 'row' ) );
		return false;
	});
	$( '#woocommerce-product-data' ).on( 'click','.downloadable_files a.delete',function() {
		$( this ).closest( 'tr' ).remove();
		return false;
	});

	// Stock options.
	$( 'input#_manage_stock' ).change( function() {
		if ( $( this ).is( ':checked' ) ) {
			$( 'div.stock_fields' ).show();
			$( 'p.stock_status_field' ).hide();
		} else {
			var product_type = $( 'select#product-type' ).val();

			$( 'div.stock_fields' ).hide();
			$( 'p.stock_status_field:not( .hide_if_' + product_type + ' )' ).show();
		}

		$( 'input.variable_manage_stock' ).change();
	}).change();

	// Date picker fields.
	function date_picker_select( datepicker ) {
		var option         = $( datepicker ).next().is( '.hasDatepicker' ) ? 'minDate' : 'maxDate',
			otherDateField = 'minDate' === option ? $( datepicker ).next() : $( datepicker ).prev(),
			date           = $( datepicker ).datepicker( 'getDate' );

		$( otherDateField ).datepicker( 'option', option, date );
		$( datepicker ).change();
	}

	$( '.sale_price_dates_fields' ).each( function() {
		$( this ).find( 'input' ).datepicker({
			defaultDate: '',
			dateFormat: 'yy-mm-dd',
			numberOfMonths: 1,
			showButtonPanel: true,
			onSelect: function() {
				date_picker_select( $( this ) );
			}
		});
		$( this ).find( 'input' ).each( function() { date_picker_select( $( this ) ); } );
	});

	// Attribute Tables.

	// Initial order.
	var woocommerce_attribute_items = $( '.product_attributes' ).find( '.woocommerce_attribute' ).get();

	woocommerce_attribute_items.sort( function( a, b ) {
	   var compA = parseInt( $( a ).attr( 'rel' ), 10 );
	   var compB = parseInt( $( b ).attr( 'rel' ), 10 );
	   return ( compA < compB ) ? -1 : ( compA > compB ) ? 1 : 0;
	});
	$( woocommerce_attribute_items ).each( function( index, el ) {
		$( '.product_attributes' ).append( el );
	});

	function attribute_row_indexes() {
		$( '.product_attributes .woocommerce_attribute' ).each( function( index, el ) {
			$( '.attribute_position', el ).val( parseInt( $( el ).index( '.product_attributes .woocommerce_attribute' ), 10 ) );
		});
	}

	$( '.product_attributes .woocommerce_attribute' ).each( function( index, el ) {
		if ( $( el ).css( 'display' ) !== 'none' && $( el ).is( '.taxonomy' ) ) {
			$( 'select.attribute_taxonomy' ).find( 'option[value="' + $( el ).data( 'taxonomy' ) + '"]' ).attr( 'disabled', 'disabled' );
		}
	});

	// Add rows.
	$( 'button.add_attribute' ).on( 'click', function() {
		var size         = $( '.product_attributes .woocommerce_attribute' ).length;
		var attribute    = $( 'select.attribute_taxonomy' ).val();
		var $wrapper     = $( this ).closest( '#product_attributes' );
		var $attributes  = $wrapper.find( '.product_attributes' );
		var product_type = $( 'select#product-type' ).val();
		var data         = {
			action:   'woocommerce_add_attribute',
			taxonomy: attribute,
			i:        size,
			security: woocommerce_admin_meta_boxes.add_attribute_nonce
		};

		$wrapper.block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
			$attributes.append( response );

			if ( 'variable' !== product_type ) {
				$attributes.find( '.enable_variation' ).hide();
			}

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			attribute_row_indexes();

			$attributes.find( '.woocommerce_attribute' ).last().find( 'h3' ).click();

			$wrapper.unblock();

			$( document.body ).trigger( 'woocommerce_added_attribute' );
		});

		if ( attribute ) {
			$( 'select.attribute_taxonomy' ).find( 'option[value="' + attribute + '"]' ).attr( 'disabled','disabled' );
			$( 'select.attribute_taxonomy' ).val( '' );
		}

		return false;
	});

	$( '.product_attributes' ).on( 'blur', 'input.attribute_name', function() {
		$( this ).closest( '.woocommerce_attribute' ).find( 'strong.attribute_name' ).text( $( this ).val() );
	});

	$( '.product_attributes' ).on( 'click', 'button.select_all_attributes', function() {
		$( this ).closest( 'td' ).find( 'select option' ).attr( 'selected', 'selected' );
		$( this ).closest( 'td' ).find( 'select' ).change();
		return false;
	});

	$( '.product_attributes' ).on( 'click', 'button.select_no_attributes', function() {
		$( this ).closest( 'td' ).find( 'select option' ).removeAttr( 'selected' );
		$( this ).closest( 'td' ).find( 'select' ).change();
		return false;
	});

	$( '.product_attributes' ).on( 'click', '.remove_row', function() {
		if ( window.confirm( woocommerce_admin_meta_boxes.remove_attribute ) ) {
			var $parent = $( this ).parent().parent();

			if ( $parent.is( '.taxonomy' ) ) {
				$parent.find( 'select, input[type=text]' ).val( '' );
				$parent.hide();
				$( 'select.attribute_taxonomy' ).find( 'option[value="' + $parent.data( 'taxonomy' ) + '"]' ).removeAttr( 'disabled' );
			} else {
				$parent.find( 'select, input[type=text]' ).val( '' );
				$parent.hide();
				attribute_row_indexes();
			}
		}
		return false;
	});

	// Attribute ordering.
	$( '.product_attributes' ).sortable({
		items: '.woocommerce_attribute',
		cursor: 'move',
		axis: 'y',
		handle: 'h3',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'wc-metabox-sortable-placeholder',
		start: function( event, ui ) {
			ui.item.css( 'background-color', '#f6f6f6' );
		},
		stop: function( event, ui ) {
			ui.item.removeAttr( 'style' );
			attribute_row_indexes();
		}
	});

	// Add a new attribute (via ajax).
	$( '.product_attributes' ).on( 'click', 'button.add_new_attribute', function() {

		$( '.product_attributes' ).block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		var $wrapper           = $( this ).closest( '.woocommerce_attribute' );
		var attribute          = $wrapper.data( 'taxonomy' );
		var new_attribute_name = window.prompt( woocommerce_admin_meta_boxes.new_attribute_prompt );

		if ( new_attribute_name ) {

			var data = {
				action:   'woocommerce_add_new_attribute',
				taxonomy: attribute,
				term:     new_attribute_name,
				security: woocommerce_admin_meta_boxes.add_attribute_nonce
			};

			$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {

				if ( response.error ) {
					// Error.
					window.alert( response.error );
				} else if ( response.slug ) {
					// Success.
					$wrapper.find( 'select.attribute_values' )
						.append( '<option value="' + response.term_id + '" selected="selected">' + response.name + '</option>' );
					$wrapper.find( 'select.attribute_values' ).change();
				}

				$( '.product_attributes' ).unblock();
			});

		} else {
			$( '.product_attributes' ).unblock();
		}

		return false;
	});

	// Save attributes and update variations.
	$( '.save_attributes' ).on( 'click', function() {

		$( '.product_attributes' ).block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});
		var original_data = $( '.product_attributes' ).find( 'input, select, textarea' );
		var data = {
			post_id     : woocommerce_admin_meta_boxes.post_id,
			product_type: $( '#product-type' ).val(),
			data        : original_data.serialize(),
			action      : 'woocommerce_save_attributes',
			security    : woocommerce_admin_meta_boxes.save_attributes_nonce
		};

		$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
			if ( response.error ) {
				// Error.
				window.alert( response.error );
			} else if ( response.data ) {
				// Success.
				$( '.product_attributes' ).html( response.data.html );
				$( '.product_attributes' ).unblock();

				// Hide the 'Used for variations' checkbox if not viewing a variable product
				show_and_hide_panels();

				// Make sure the dropdown is not disabled for empty value attributes.
				$( 'select.attribute_taxonomy' ).find( 'option' ).prop( 'disabled', false );

				$( '.product_attributes .woocommerce_attribute' ).each( function( index, el ) {
					if ( $( el ).css( 'display' ) !== 'none' && $( el ).is( '.taxonomy' ) ) {
						$( 'select.attribute_taxonomy' ).find( 'option[value="' + $( el ).data( 'taxonomy' ) + '"]' ).prop( 'disabled', true );
					}
				});

				// Reload variations panel.
				var this_page = window.location.toString();
				this_page = this_page.replace( 'post-new.php?', 'post.php?post=' + woocommerce_admin_meta_boxes.post_id + '&action=edit&' );

				$( '#variable_product_options' ).load( this_page + ' #variable_product_options_inner', function() {
					$( '#variable_product_options' ).trigger( 'reload' );
				} );
			}
		});
	});

	// Uploading files.
	var downloadable_file_frame;
	var file_path_field;

	$( document.body ).on( 'click', '.upload_file_button', function( event ) {
		var $el = $( this );

		file_path_field = $el.closest( 'tr' ).find( 'td.file_url input' );

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if ( downloadable_file_frame ) {
			downloadable_file_frame.open();
			return;
		}

		var downloadable_file_states = [
			// Main states.
			new wp.media.controller.Library({
				library:   wp.media.query(),
				multiple:  true,
				title:     $el.data('choose'),
				priority:  20,
				filterable: 'uploaded'
			})
		];

		// Create the media frame.
		downloadable_file_frame = wp.media.frames.downloadable_file = wp.media({
			// Set the title of the modal.
			title: $el.data('choose'),
			library: {
				type: ''
			},
			button: {
				text: $el.data('update')
			},
			multiple: true,
			states: downloadable_file_states
		});

		// When an image is selected, run a callback.
		downloadable_file_frame.on( 'select', function() {
			var file_path = '';
			var selection = downloadable_file_frame.state().get( 'selection' );

			selection.map( function( attachment ) {
				attachment = attachment.toJSON();
				if ( attachment.url ) {
					file_path = attachment.url;
				}
			});

			file_path_field.val( file_path ).change();
		});

		// Set post to 0 and set our custom type.
		downloadable_file_frame.on( 'ready', function() {
			downloadable_file_frame.uploader.options.uploader.params = {
				type: 'downloadable_product'
			};
		});

		// Finally, open the modal.
		downloadable_file_frame.open();
	});

	// Download ordering.
	$( '.downloadable_files tbody' ).sortable({
		items: 'tr',
		cursor: 'move',
		axis: 'y',
		handle: 'td.sort',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65
	});

	// Product gallery file uploads.
	var product_gallery_frame;
	var $image_gallery_ids = $( '#product_image_gallery' );
	var $product_images    = $( '#product_images_container' ).find( 'ul.product_images' );

	$( '.add_product_images' ).on( 'click', 'a', function( event ) {
		var $el = $( this );

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if ( product_gallery_frame ) {
			product_gallery_frame.open();
			return;
		}

		// Create the media frame.
		product_gallery_frame = wp.media.frames.product_gallery = wp.media({
			// Set the title of the modal.
			title: $el.data( 'choose' ),
			button: {
				text: $el.data( 'update' )
			},
			states: [
				new wp.media.controller.Library({
					title: $el.data( 'choose' ),
					filterable: 'all',
					multiple: true
				})
			]
		});

		// When an image is selected, run a callback.
		product_gallery_frame.on( 'select', function() {
			var selection = product_gallery_frame.state().get( 'selection' );
			var attachment_ids = $image_gallery_ids.val();

			selection.map( function( attachment ) {
				attachment = attachment.toJSON();

				if ( attachment.id ) {
					attachment_ids   = attachment_ids ? attachment_ids + ',' + attachment.id : attachment.id;
					var attachment_image = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

					$product_images.append(
						'<li class="image" data-attachment_id="' + attachment.id + '"><img src="' + attachment_image +
						'" /><ul class="actions"><li><a href="#" class="delete" title="' + $el.data('delete') + '">' +
						$el.data('text') + '</a></li></ul></li>'
					);
				}
			});

			$image_gallery_ids.val( attachment_ids );
		});

		// Finally, open the modal.
		product_gallery_frame.open();
	});

	// Image ordering.
	$product_images.sortable({
		items: 'li.image',
		cursor: 'move',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		forceHelperSize: false,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'wc-metabox-sortable-placeholder',
		start: function( event, ui ) {
			ui.item.css( 'background-color', '#f6f6f6' );
		},
		stop: function( event, ui ) {
			ui.item.removeAttr( 'style' );
		},
		update: function() {
			var attachment_ids = '';

			$( '#product_images_container' ).find( 'ul li.image' ).css( 'cursor', 'default' ).each( function() {
				var attachment_id = $( this ).attr( 'data-attachment_id' );
				attachment_ids = attachment_ids + attachment_id + ',';
			});

			$image_gallery_ids.val( attachment_ids );
		}
	});

	// Remove images.
	$( '#product_images_container' ).on( 'click', 'a.delete', function() {
		$( this ).closest( 'li.image' ).remove();

		var attachment_ids = '';

		$( '#product_images_container' ).find( 'ul li.image' ).css( 'cursor', 'default' ).each( function() {
			var attachment_id = $( this ).attr( 'data-attachment_id' );
			attachment_ids = attachment_ids + attachment_id + ',';
		});

		$image_gallery_ids.val( attachment_ids );

		// Remove any lingering tooltips.
		$( '#tiptip_holder' ).removeAttr( 'style' );
		$( '#tiptip_arrow' ).removeAttr( 'style' );

		return false;
	});
});
