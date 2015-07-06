/* global wp, woocommerce_admin_meta_boxes_variations, woocommerce_admin, woocommerce_admin_meta_boxes, accounting */
/*jshint devel: true */
jQuery( function ( $ ) {

	var variation_sortable_options = {
		items: '.woocommerce_variation',
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
		stop: function ( event, ui ) {
			ui.item.removeAttr( 'style' );
			variation_row_indexes();
		}
	};

	// Add a variation
	$( '#variable_product_options' ).on( 'click', 'button.add_variation', function () {

		$('.woocommerce_variations').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		var loop = $('.woocommerce_variation').size();

		var data = {
			action: 'woocommerce_add_variation',
			post_id: woocommerce_admin_meta_boxes_variations.post_id,
			loop: loop,
			security: woocommerce_admin_meta_boxes_variations.add_variation_nonce
		};

		$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function ( response ) {

			$( '.woocommerce_variations' ).append( response );

			$( '.tips' ).tipTip({
				'attribute': 'data-tip',
				'fadeIn': 50,
				'fadeOut': 50
			});

			$( 'input.variable_is_downloadable, input.variable_is_virtual, input.variable_manage_stock' ).change();
			$( '.woocommerce_variations' ).unblock();
			$( '#variable_product_options' ).trigger( 'woocommerce_variations_added' );
		});

		return false;

	});

	$( '#variable_product_options').on( 'click', 'button.link_all_variations', function () {

		var answer = window.confirm( woocommerce_admin_meta_boxes_variations.i18n_link_all_variations );

		if ( answer ) {

			$( '#variable_product_options' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			var data = {
				action: 'woocommerce_link_all_variations',
				post_id: woocommerce_admin_meta_boxes_variations.post_id,
				security: woocommerce_admin_meta_boxes_variations.link_variation_nonce
			};

			$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function ( response ) {

				var count = parseInt( response, 10 );

				if ( 1 === count ) {
					window.alert( count + ' ' + woocommerce_admin_meta_boxes_variations.i18n_variation_added );
				} else if ( 0 === count || count > 1 ) {
					window.alert( count + ' ' + woocommerce_admin_meta_boxes_variations.i18n_variations_added );
				} else {
					window.alert( woocommerce_admin_meta_boxes_variations.i18n_no_variations_added );
				}

				if ( count > 0 ) {
					var this_page = window.location.toString();

					this_page = this_page.replace( 'post-new.php?', 'post.php?post=' + woocommerce_admin_meta_boxes_variations.post_id + '&action=edit&' );

					$( '#variable_product_options' ).load( this_page + ' #variable_product_options_inner', function () {
						$( '#variable_product_options' ).unblock();
						$( '#variable_product_options' ).trigger( 'woocommerce_variations_added' );

						$( '.tips' ).tipTip({
							'attribute': 'data-tip',
							'fadeIn': 50,
							'fadeOut': 50
						});
					} );
				} else {
					$( '#variable_product_options' ).unblock();
				}
			});
		}
		return false;
	});

	$( '#variable_product_options' ).on( 'click', 'button.remove_variation', function ( e ) {
		e.preventDefault();
		var answer = window.confirm( woocommerce_admin_meta_boxes_variations.i18n_remove_variation );
		if ( answer ) {

			var el = $( this ).parent().parent();

			var variation = $( this ).attr( 'rel' );

			if ( variation > 0 ) {

				$( el ).block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				var variation_ids = [];
				variation_ids.push( variation );

				var data = {
					action: 'woocommerce_remove_variations',
					variation_ids: variation_ids,
					security: woocommerce_admin_meta_boxes_variations.delete_variations_nonce
				};

				$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function() {
					// Success
					$( el ).fadeOut( '300', function () {
						$( el ).remove();
					});
				});

			} else {
				$( el ).fadeOut( '300', function () {
					$( el ).remove();
				});
			}

		}
		return false;
	});

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

	// Ordering
	$( '#variable_product_options' ).on( 'woocommerce_variations_added', function () {
		$( '.woocommerce_variations' ).sortable( variation_sortable_options );
	} );

	$( '.woocommerce_variations' ).sortable( variation_sortable_options );

	function variation_row_indexes() {
		$( '.woocommerce_variations .woocommerce_variation' ).each( function ( index, el ) {
			$( '.variation_menu_order', el ).val( parseInt( $( el ).index( '.woocommerce_variations .woocommerce_variation' ), 10 ) );
		});
	}

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
		 * Initialize products variations meta box
		 */
		init: function() {
			$( 'li.variations_tab a' ).on( 'click', this.initial_load );
			$( 'body' ).on( 'change', '#variable_product_options_inner .woocommerce_variations :input', this.input_changed );
		},

		/**
		 * Initial load variations
		 *
		 * @return {bool}
		 */
		initial_load: function() {
			if ( 0 === $( '#variable_product_options_inner .woocommerce_variations .woocommerce_variation' ).length ) {
				wc_meta_boxes_product_variations_ajax.load_variations();
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

			var wrapper = $( '#variable_product_options_inner .woocommerce_variations' );

			$( '#woocommerce-product-data' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

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
					wrapper.empty().append( response ).attr( 'page', page );

					$( '#woocommerce-product-data' ).unblock();
				}
			});
		},

		/**
		 * Add new class when have changes in some input
		 */
		input_changed: function() {
			$( '#variable_product_options_inner .woocommerce_variations' ).attr( 'data-edited', true );
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
			$( '.variations-pagenav .page-selector' ).on( 'change', this.page_selector );
			$( '.variations-pagenav .first-page' ).on( 'click', this.first_page );
			$( '.variations-pagenav .prev-page' ).on( 'click', this.prev_page );
			$( '.variations-pagenav .next-page' ).on( 'click', this.next_page );
			$( '.variations-pagenav .last-page' ).on( 'click', this.last_page );
		},

		/**
		 * Check if have some edition before leave the page
		 *
		 * @return {bool}
		 */
		check_for_editions: function() {
			var wrapper = $( '#variable_product_options_inner .woocommerce_variations' );

			if ( 'true' === wrapper.attr( 'data-edited' ) ) {
				if ( window.confirm( woocommerce_admin_meta_boxes_variations.i18n_edited_variations ) ) {
					wrapper.attr( 'data-edited', false );
				} else {
					return false;
				}
			}

			return true;
		},

		/**
		 * Check button if enabled and if don't have editions
		 *
		 * @return {bool}
		 */
		check_is_enabled: function( current ) {
			return ! $( current ).hasClass( 'disabled' ) && wc_meta_boxes_product_variations_pagenav.check_for_editions();
		},

		/**
		 * Change "disabled" class on pagenav
		 */
		change_classes: function( selected, total ) {
			if ( 1 === selected ) {
				$( '.variations-pagenav .first-page' ).addClass( 'disabled' );
				$( '.variations-pagenav .prev-page' ).addClass( 'disabled' );
			} else {
				$( '.variations-pagenav .first-page' ).removeClass( 'disabled' );
				$( '.variations-pagenav .prev-page' ).removeClass( 'disabled' );
			}

			if ( total === selected ) {
				$( '.variations-pagenav .next-page' ).addClass( 'disabled' );
				$( '.variations-pagenav .last-page' ).addClass( 'disabled' );
			} else {
				$( '.variations-pagenav .next-page' ).removeClass( 'disabled' );
				$( '.variations-pagenav .last-page' ).removeClass( 'disabled' );
			}
		},

		/**
		 * Paginav pagination selector
		 */
		page_selector: function() {
			var selected = parseInt( $( this ).val(), 10 ),
				wrapper  = $( '#variable_product_options_inner .woocommerce_variations' );

			if ( wc_meta_boxes_product_variations_pagenav.check_for_editions() ) {
				wc_meta_boxes_product_variations_pagenav.change_classes();
				wc_meta_boxes_product_variations_ajax.load_variations( selected );
			} else {
				$( this ).val( parseInt( wrapper.attr( 'page' ), 10 ) );
			}
		},

		/**
		 * Go to first page
		 *
		 * @return {bool}
		 */
		first_page: function() {
			if ( wc_meta_boxes_product_variations_pagenav.check_is_enabled( this ) ) {
				$( '.variations-pagenav .page-selector' ).val( 1 ).change();
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
				var wrapper   = $( '#variable_product_options_inner .woocommerce_variations' ),
					prev_page = parseInt( wrapper.attr( 'page' ), 10 ) - 1,
					new_page  = ( 0 < prev_page ) ? prev_page : 1;

				$( '.variations-pagenav .page-selector' ).val( new_page ).change();
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
				var wrapper     = $( '#variable_product_options_inner .woocommerce_variations' ),
					total_pages = wrapper.data( 'total_pages' ),
					next_page   = parseInt( wrapper.attr( 'page' ), 10 ) + 1,
					new_page    = ( total_pages >= next_page ) ? next_page : total_pages;

				$( '.variations-pagenav .page-selector' ).val( new_page ).change();
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
				var last_page = $( '#variable_product_options_inner .woocommerce_variations' ).data( 'total_pages' );

				$( '.variations-pagenav .page-selector' ).val( last_page ).change();
			}

			return false;
		}
	};

	wc_meta_boxes_product_variations_ajax.init();
	wc_meta_boxes_product_variations_pagenav.init();

});
