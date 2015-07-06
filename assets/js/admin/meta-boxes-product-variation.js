/* global wp, woocommerce_admin_meta_boxes_variations, woocommerce_admin, woocommerce_admin_meta_boxes, accounting */
/*jshint devel: true */
jQuery( function ( $ ) {

	$( '.wc-metaboxes-wrapper' ).on( 'click', 'a.bulk_edit', function () {
		var bulk_edit  = $( 'select#field_to_edit' ).val(),
			checkbox,
			answer,
			value;

		switch ( bulk_edit ) {
			case 'toggle_enabled' :
				checkbox = $( 'input[name^="variable_enabled"]' );
				checkbox.attr('checked', ! checkbox.attr( 'checked' ) );
			break;
			case 'toggle_downloadable' :
				checkbox = $( 'input[name^="variable_is_downloadable"]' );
				checkbox.attr( 'checked', ! checkbox.attr( 'checked' ) );
				$('input.variable_is_downloadable').change();
			break;
			case 'toggle_virtual' :
				checkbox = $('input[name^="variable_is_virtual"]');
				checkbox.attr( 'checked', ! checkbox.attr( 'checked' ) );
				$( 'input.variable_is_virtual' ).change();
			break;
			case 'toggle_manage_stock' :
				checkbox = $('input[name^="variable_manage_stock"]');
				checkbox.attr( 'checked', ! checkbox.attr( 'checked' ) );
				$( 'input.variable_manage_stock' ).change();
			break;
			case 'delete_all' :
				answer = window.confirm( woocommerce_admin_meta_boxes_variations.i18n_delete_all_variations );

				if ( answer ) {

					answer = window.confirm( woocommerce_admin_meta_boxes_variations.i18n_last_warning );

					if ( answer ) {

						var variation_ids = [];

						$( '.woocommerce_variations .woocommerce_variation' ).block({
							message: null,
							overlayCSS: {
								background: '#fff',
								opacity: 0.6
							}
						});

						$( '.woocommerce_variations .woocommerce_variation .remove_variation' ).each( function () {

							var variation = $( this ).attr( 'rel' );
							if ( variation > 0 ) {
								variation_ids.push( variation );
							}
						});

						var data = {
							action: 'woocommerce_remove_variations',
							variation_ids: variation_ids,
							security: woocommerce_admin_meta_boxes_variations.delete_variations_nonce
						};

						$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function() {
							$( '.woocommerce_variations .woocommerce_variation' ).fadeOut( '300', function () {
								$( '.woocommerce_variations .woocommerce_variation' ).remove();
							});
						});
					}
				}
			break;
			case 'variable_regular_price_increase':
			case 'variable_regular_price_decrease':
			case 'variable_sale_price_increase':
			case 'variable_sale_price_decrease':
				var edit_field;
				if ( bulk_edit.lastIndexOf( 'variable_regular_price', 0 ) === 0 ) {
					edit_field = 'variable_regular_price';
				} else {
					edit_field = 'variable_sale_price';
				}

				value = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_a_value_fixed_or_percent );

				if ( value == null ) {
					return;
				} else {
					value = value.toString();
				}

				$( ':input[name^="' + edit_field + '"]' ).not('[name*="dates"]').each( function() {
					var current_value = accounting.unformat( $( this ).val(), woocommerce_admin.mon_decimal_point ),
						new_value,
						mod_value;

					if ( value.indexOf( '%' ) >= 0 ) {
						mod_value = ( current_value / 100 ) * accounting.unformat( value.replace( /\%/, '' ), woocommerce_admin.mon_decimal_point );
					} else {
						mod_value = accounting.unformat( value, woocommerce_admin.mon_decimal_point );
					}

					if ( bulk_edit.indexOf( 'increase' ) !== -1 ) {
						new_value = current_value + mod_value;
					} else {
						new_value = current_value - mod_value;
					}

					$( this ).val( accounting.formatNumber(
						new_value,
						woocommerce_admin_meta_boxes.currency_format_num_decimals,
						woocommerce_admin_meta_boxes.currency_format_thousand_sep,
						woocommerce_admin_meta_boxes.currency_format_decimal_sep
					) ).change();
				});
			break;
			case 'variable_regular_price' :
			case 'variable_sale_price' :
			case 'variable_stock' :
			case 'variable_weight' :
			case 'variable_length' :
			case 'variable_width' :
			case 'variable_height' :
			case 'variable_download_limit' :
			case 'variable_download_expiry' :
				value = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_a_value );

				if ( value != null ) {
					$( ':input[name^="' + bulk_edit + '"]').not('[name*="dates"]').val( value ).change();
				}
			break;
			case 'variable_sale_schedule' :
				var start = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_scheduled_sale_start );
				var end   = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_scheduled_sale_end );
				var set   = false;

				if ( start != null && start !== '' ) {
					$('.woocommerce_variable_attributes .sale_schedule').click();
					$( ':input[name^="variable_sale_price_dates_from"]').val( start ).change();
					set = true;
				}
				if ( end != null && end !== '' ) {
					$('.woocommerce_variable_attributes .sale_schedule').click();
					$( ':input[name^="variable_sale_price_dates_to"]').val( end ).change();
					set = true;
				}
				if ( ! set ) {
					$('.woocommerce_variable_attributes .cancel_sale_schedule').click();
				}
			break;
			default:
				$( 'select#field_to_edit' ).trigger( bulk_edit );
			break;

		}
	});

	$( '#variable_product_options' ).on( 'change', 'input.variable_is_downloadable', function () {
		$( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_downloadable' ).hide();

		if ( $( this ).is( ':checked' ) ) {
			$( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_downloadable' ).show();
		}
	});

	$( '#variable_product_options' ).on( 'change', 'input.variable_is_virtual', function () {
		$( this ).closest( '.woocommerce_variation' ).find( '.hide_if_variation_virtual' ).show();

		if ( $( this ).is( ':checked' ) ) {
			$( this ).closest( '.woocommerce_variation' ).find( '.hide_if_variation_virtual' ).hide();
		}
	});

	$( '#variable_product_options' ).on( 'change', 'input.variable_manage_stock', function () {
		$( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_manage_stock' ).hide();

		if ( $( this ).is( ':checked' ) ) {
			$( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_manage_stock' ).show();
		}
	});

	$( 'input.variable_is_downloadable, input.variable_is_virtual, input.variable_manage_stock' ).change();

	// Uploader
	var variable_image_frame;
	var setting_variation_image_id;
	var setting_variation_image;
	var wp_media_post_id = wp.media.model.settings.post.id;

	$( '#variable_product_options' ).on( 'click', '.upload_image_button', function ( event ) {

		var $button                = $( this );
		var post_id                = $button.attr( 'rel' );
		var $parent                = $button.closest( '.upload_image' );
		setting_variation_image    = $parent;
		setting_variation_image_id = post_id;

		event.preventDefault();

		if ( $button.is( '.remove' ) ) {

			setting_variation_image.find( '.upload_image_id' ).val( '' );
			setting_variation_image.find( 'img' ).eq( 0 ).attr( 'src', woocommerce_admin_meta_boxes_variations.woocommerce_placeholder_img_src );
			setting_variation_image.find( '.upload_image_button' ).removeClass( 'remove' );

		} else {

			// If the media frame already exists, reopen it.
			if ( variable_image_frame ) {
				variable_image_frame.uploader.uploader.param( 'post_id', setting_variation_image_id );
				variable_image_frame.open();
				return;
			} else {
				wp.media.model.settings.post.id = setting_variation_image_id;
			}

			// Create the media frame.
			variable_image_frame = wp.media.frames.variable_image = wp.media({
				// Set the title of the modal.
				title: woocommerce_admin_meta_boxes_variations.i18n_choose_image,
				button: {
					text: woocommerce_admin_meta_boxes_variations.i18n_set_image
				},
				states : [
					new wp.media.controller.Library({
						title: woocommerce_admin_meta_boxes_variations.i18n_choose_image,
						filterable :	'all'
					})
				]
			});

			// When an image is selected, run a callback.
			variable_image_frame.on( 'select', function () {

				var attachment = variable_image_frame.state().get( 'selection' ).first().toJSON(),
					url = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

				setting_variation_image.find( '.upload_image_id' ).val( attachment.id );
				setting_variation_image.find( '.upload_image_button' ).addClass( 'remove' );
				setting_variation_image.find( 'img' ).eq( 0 ).attr( 'src', url );

				wp.media.model.settings.post.id = wp_media_post_id;
			});

			// Finally, open the modal.
			variable_image_frame.open();
		}
	});

	// Restore ID
	$( 'a.add_media' ).on( 'click', function() {
		wp.media.model.settings.post.id = wp_media_post_id;
	});

	/**
	 * Product variations metabox ajax methods
	 */
	var wc_meta_boxes_product_variations_ajax = {

		/**
		 * Initialize variations ajax methods
		 */
		init: function() {
			$( 'li.variations_tab a' ).on( 'click', this.initial_load );

			$( '#variable_product_options' )
				.on( 'click', 'button.save-variation-changes', this.save_variations )
				.on( 'click', 'button.add_variation', this.add_variation )
				.on( 'click', 'button.remove_variation', this.remove_variation )
				.on( 'click', 'button.link_all_variations', this.link_all_variations );

			$( 'body' ).on( 'change', '#variable_product_options .woocommerce_variations :input', this.input_changed );
		},

		/**
		 * Check if have some edition before leave the page
		 *
		 * @return {bool}
		 */
		check_for_editions: function() {
			var need_update = $( '#variable_product_options .woocommerce_variations .variation-needs-update' );

			if ( 0 < need_update.legnth ) {
				if ( window.confirm( woocommerce_admin_meta_boxes_variations.i18n_edited_variations ) ) {
					need_update.removeClass( 'variation-needs-update' );
					$( 'button.save-variation-changes' ).removeAttr( 'disabled' );
				} else {
					return false;
				}
			}

			return true;
		},

		/**
		 * Block edit screen
		 */
		block: function() {
			$( '#woocommerce-product-data' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
		},

		/**
		 * Unblock edit screen
		 */
		unblock: function() {
			$( '#woocommerce-product-data' ).unblock();
		},

		/**
		 * Initial load variations
		 *
		 * @return {bool}
		 */
		initial_load: function() {
			if ( 0 === $( '#variable_product_options .woocommerce_variations .woocommerce_variation' ).length ) {
				wc_meta_boxes_product_variations_pagenav.set_paginav( 0 );
				wc_meta_boxes_product_variations_pagenav.set_page( 1 );
			}
		},

		/**
		 * Load variations via Ajax
		 *
		 * @param {int} page (default: 1)
		 * @param {int} per_page (default: 10)
		 */
		load_variations: function( page, per_page ) {
			page     = page || 1;
			per_page = per_page || woocommerce_admin_meta_boxes_variations.variations_per_page;

			var wrapper = $( '#variable_product_options .woocommerce_variations' );

			wc_meta_boxes_product_variations_ajax.block();

			$.ajax({
				url: woocommerce_admin_meta_boxes_variations.ajax_url,
				data: {
					action:     'woocommerce_load_variations',
					security:   woocommerce_admin_meta_boxes_variations.load_variations_nonce,
					product_id: wrapper.data( 'product_id' ),
					attributes: wrapper.data( 'attributes' ),
					page:       page,
					per_page:   per_page
				},
				type: 'POST',
				success: function( response ) {
					wrapper.empty().append( response ).attr( 'data-page', page );

					wc_meta_boxes_product_variations_ajax.unblock();
				}
			});
		},

		/**
		 * Save variations
		 *
		 * @return {bool}
		 */
		save_variations: function() {
			var button      = $( this ),
				wrapper     = $( '#variable_product_options .woocommerce_variations' ),
				need_update = $( '.variation-needs-update', wrapper );

			// Save only with products need update.
			if ( 0 < need_update.length ) {
				wc_meta_boxes_product_variations_ajax.block();

				$.ajax({
					url: woocommerce_admin_meta_boxes_variations.ajax_url,
					data: {
						action:     'woocommerce_save_variations',
						security:   woocommerce_admin_meta_boxes_variations.save_variations_nonce,
						product_id: wrapper.data( 'product_id' ),
						data:       $( ':input', need_update ).serialize()
					},
					type: 'POST',
					success: function() {
						// Allow change page, delete and add new variations
						need_update.removeClass( 'variation-needs-update' );
						button.attr( 'disabled', 'disabled' );

						wc_meta_boxes_product_variations_ajax.unblock();
					}
				});
			}

			return false;
		},

		/**
		 * Add variation
		 *
		 * @return {bool}
		 */
		add_variation: function() {
			if ( ! wc_meta_boxes_product_variations_ajax.check_for_editions() ) {
				return false;
			}

			wc_meta_boxes_product_variations_ajax.block();

			var data = {
				action: 'woocommerce_add_variation',
				post_id: woocommerce_admin_meta_boxes_variations.post_id,
				loop: $( '.woocommerce_variation' ).size(),
				security: woocommerce_admin_meta_boxes_variations.add_variation_nonce
			};

			$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function() {
				wc_meta_boxes_product_variations_pagenav.set_paginav( 1 );
				wc_meta_boxes_product_variations_pagenav.set_page( 1 );

				wc_meta_boxes_product_variations_ajax.unblock();
				$( '#variable_product_options' ).trigger( 'woocommerce_variations_added' );
			});

			return false;
		},

		/**
		 * Remove variation
		 *
		 * @return {bool}
		 */
		remove_variation: function() {
			if ( ! wc_meta_boxes_product_variations_ajax.check_for_editions() ) {
				return false;
			}

			if ( window.confirm( woocommerce_admin_meta_boxes_variations.i18n_remove_variation ) ) {
				var variation     = $( this ).attr( 'rel' ),
					variation_ids = [],
					data          = {
						action: 'woocommerce_remove_variations'
					};

				wc_meta_boxes_product_variations_ajax.block();

				if ( 0 < variation ) {
					variation_ids.push( variation );

					data.variation_ids = variation_ids;
					data.security      = woocommerce_admin_meta_boxes_variations.delete_variations_nonce;

					$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function() {
						var current = parseInt( $( '#variable_product_options .woocommerce_variations' ).attr( 'data-page' ), 10 );

						wc_meta_boxes_product_variations_pagenav.set_paginav( -1 );
						wc_meta_boxes_product_variations_pagenav.set_page( current );
					});

				} else {
					wc_meta_boxes_product_variations_ajax.unblock();
				}
			}

			return false;
		},

		/**
		 * Link all variations (or at least try :p)
		 *
		 * @return {bool}
		 */
		link_all_variations: function() {
			if ( ! wc_meta_boxes_product_variations_ajax.check_for_editions() ) {
				return false;
			}

			if ( window.confirm( woocommerce_admin_meta_boxes_variations.i18n_link_all_variations ) ) {
				wc_meta_boxes_product_variations_ajax.block();

				var data = {
					action: 'woocommerce_link_all_variations',
					post_id: woocommerce_admin_meta_boxes_variations.post_id,
					security: woocommerce_admin_meta_boxes_variations.link_variation_nonce
				};

				$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function( response ) {
					var count = parseInt( response, 10 );

					if ( 1 === count ) {
						window.alert( count + ' ' + woocommerce_admin_meta_boxes_variations.i18n_variation_added );
					} else if ( 0 === count || count > 1 ) {
						window.alert( count + ' ' + woocommerce_admin_meta_boxes_variations.i18n_variations_added );
					} else {
						window.alert( woocommerce_admin_meta_boxes_variations.i18n_no_variations_added );
					}

					if ( count > 0 ) {
						wc_meta_boxes_product_variations_pagenav.set_paginav( count );
						wc_meta_boxes_product_variations_pagenav.set_page( 1 );

						$( '#variable_product_options' ).trigger( 'woocommerce_variations_added' );
					} else {
						wc_meta_boxes_product_variations_ajax.unblock();
					}
				});
			}

			return false;
		},

		/**
		 * Add new class when have changes in some input
		 */
		input_changed: function() {
			$( this ).closest( '.woocommerce_variation' ).addClass( 'variation-needs-update' );
			$( '.save-variation-changes' ).removeAttr( 'disabled' );
		}
	};

	/**
	 * Product variations pagenav
	 */
	var wc_meta_boxes_product_variations_pagenav = {

		/**
		 * Initialize products variations meta box
		 */
		init: function() {
			$( '.variations-pagenav' )
				.on( 'change', '.page-selector', this.page_selector )
				.on( 'click', '.first-page', this.first_page )
				.on( 'click', '.prev-page', this.prev_page )
				.on( 'click', '.next-page', this.next_page )
				.on( 'click', '.last-page', this.last_page );
		},

		/**
		 * Set the pagenav fields
		 *
		 * @param {int} qty
		 */
		set_paginav: function( qty ) {
			var wrapper        = $( '#variable_product_options .woocommerce_variations' ),
				total          = parseInt( wrapper.attr( 'data-total' ), 10 ),
				new_qty        = total + qty,
				displaying_num = $( '.variations-pagenav .displaying-num' ),
				total_pages    = Math.ceil( new_qty / woocommerce_admin_meta_boxes_variations.variations_per_page ),
				options        = '';

			// Set the new total of variations
			wrapper.attr( 'data-total', new_qty );

			if ( 1 === new_qty ) {
				displaying_num.text( woocommerce_admin_meta_boxes_variations.i18n_item.replace( '%qty%', new_qty ) );
			} else {
				displaying_num.text( woocommerce_admin_meta_boxes_variations.i18n_items.replace( '%qty%', new_qty ) );
			}

			// Set the new total of pages
			wrapper.attr( 'data-total_pages', total_pages );

			$( '.variations-pagenav .total-pages' ).text( total_pages );

			// Set the new pagenav options
			for ( var i = 1; i <= total_pages; i++ ) {
				options += '<option value="' + i + '">' + i + '</option>';
			}

			$( '.page-selector' ).empty().html( options );
		},

		/**
		 * Check button if enabled and if don't have editions
		 *
		 * @return {bool}
		 */
		check_is_enabled: function( current ) {
			return ! $( current ).hasClass( 'disabled' ) && wc_meta_boxes_product_variations_ajax.check_for_editions();
		},

		/**
		 * Change "disabled" class on pagenav
		 */
		change_classes: function( selected, total ) {
			var first_page = $( '.variations-pagenav .first-page' ),
				prev_page  = $( '.variations-pagenav .prev-page' ),
				next_page  = $( '.variations-pagenav .next-page' ),
				last_page  = $( '.variations-pagenav .last-page' );

			if ( 1 === selected ) {
				first_page.addClass( 'disabled' );
				prev_page.addClass( 'disabled' );
			} else {
				first_page.removeClass( 'disabled' );
				prev_page.removeClass( 'disabled' );
			}

			if ( total === selected ) {
				next_page.addClass( 'disabled' );
				last_page.addClass( 'disabled' );
			} else {
				next_page.removeClass( 'disabled' );
				last_page.removeClass( 'disabled' );
			}
		},

		/**
		 * Set page
		 */
		set_page: function( page ) {
			$( '.variations-pagenav .page-selector' ).val( page ).change();
		},

		/**
		 * Paginav pagination selector
		 */
		page_selector: function() {
			var selected = parseInt( $( this ).val(), 10 ),
				wrapper  = $( '#variable_product_options .woocommerce_variations' );

			if ( wc_meta_boxes_product_variations_ajax.check_for_editions() ) {
				wc_meta_boxes_product_variations_pagenav.change_classes( selected, parseInt( wrapper.attr( 'data-total_pages' ), 10 ) );
				wc_meta_boxes_product_variations_ajax.load_variations( selected );
			} else {
				$( this ).val( parseInt( wrapper.data( 'page' ), 10 ) );
			}
		},

		/**
		 * Go to first page
		 *
		 * @return {bool}
		 */
		first_page: function() {
			if ( wc_meta_boxes_product_variations_pagenav.check_is_enabled( this ) ) {
				wc_meta_boxes_product_variations_pagenav.set_page( 1 );
			}

			return false;
		},

		/**
		 * Go to previous page
		 *
		 * @return {bool}
		 */
		prev_page: function() {
			if ( wc_meta_boxes_product_variations_pagenav.check_is_enabled( this ) ) {
				var wrapper   = $( '#variable_product_options .woocommerce_variations' ),
					prev_page = parseInt( wrapper.data( 'page' ), 10 ) - 1,
					new_page  = ( 0 < prev_page ) ? prev_page : 1;

				wc_meta_boxes_product_variations_pagenav.set_page( new_page );
			}

			return false;
		},

		/**
		 * Go to next page
		 *
		 * @return {bool}
		 */
		next_page: function() {
			if ( wc_meta_boxes_product_variations_pagenav.check_is_enabled( this ) ) {
				var wrapper     = $( '#variable_product_options .woocommerce_variations' ),
					total_pages = parseInt( wrapper.data( 'total_pages' ), 10 ),
					next_page   = parseInt( wrapper.data( 'page' ), 10 ) + 1,
					new_page    = ( total_pages >= next_page ) ? next_page : total_pages;

				wc_meta_boxes_product_variations_pagenav.set_page( new_page );
			}

			return false;
		},

		/**
		 * Go to last page
		 *
		 * @return {bool}
		 */
		last_page: function() {
			if ( wc_meta_boxes_product_variations_pagenav.check_is_enabled( this ) ) {
				var last_page = $( '#variable_product_options .woocommerce_variations' ).data( 'total_pages' );

				wc_meta_boxes_product_variations_pagenav.set_page( last_page );
			}

			return false;
		}
	};

	wc_meta_boxes_product_variations_ajax.init();
	wc_meta_boxes_product_variations_pagenav.init();

});
