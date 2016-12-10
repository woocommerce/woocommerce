/*global wc_add_to_cart_variation_params */
/*!
 * Variations Plugin
 */
;(function ( $, window, document, undefined ) {

	$.fn.wc_variation_form = function() {
		var $form                  = this,
			$single_variation      = $form.find( '.single_variation' ),
			$product               = $form.closest( '.product' ),
			$product_id            = parseInt( $form.data( 'product_id' ), 10 ),
			$product_variations    = $form.data( 'product_variations' ),
			$use_ajax              = $product_variations === false,
			$xhr                   = false,
			$reset_variations      = $form.find( '.reset_variations' ),
			template               = wp.template( 'variation-template' ),
			unavailable_template   = wp.template( 'unavailable-variation-template' ),
			selection_ux           = $form.data( 'selection_ux' ) || 'locking',
			$single_variation_wrap = $form.find( '.single_variation_wrap' ),
			$attribute_selects     = $form.find( '.variations select' );

		// Always visible since 2.5.0
		$single_variation_wrap.show();

		// Unbind any existing events
		$form.unbind( 'check_variations update_variation_values found_variation' );
		$reset_variations.unbind( 'click' );
		$attribute_selects.unbind( 'change focusin' );

		// Bind new events to form
		$form

		// On clicking the reset variation button
		.on( 'click', '.reset_variations', function( event ) {
			event.preventDefault();
			$attribute_selects.val( '' ).change();
			$form.trigger( 'reset_data' );
		} )

		// When the variation is hidden
		.on( 'hide_variation', function( event ) {
			event.preventDefault();
			$form.find( '.single_add_to_cart_button' ).removeClass( 'wc-variation-is-unavailable' ).addClass( 'disabled wc-variation-selection-needed' );
			$form.find( '.woocommerce-variation-add-to-cart' ).removeClass( 'woocommerce-variation-add-to-cart-enabled' ).addClass( 'woocommerce-variation-add-to-cart-disabled' );
		} )

		// When the variation is revealed
		.on( 'show_variation', function( event, variation, purchasable ) {
			event.preventDefault();
			if ( purchasable ) {
				$form.find( '.single_add_to_cart_button' ).removeClass( 'disabled wc-variation-selection-needed wc-variation-is-unavailable' );
				$form.find( '.woocommerce-variation-add-to-cart' ).removeClass( 'woocommerce-variation-add-to-cart-disabled' ).addClass( 'woocommerce-variation-add-to-cart-enabled' );
			} else {
				$form.find( '.single_add_to_cart_button' ).removeClass( 'wc-variation-selection-needed' ).addClass( 'disabled wc-variation-is-unavailable' );
				$form.find( '.woocommerce-variation-add-to-cart' ).removeClass( 'woocommerce-variation-add-to-cart-enabled' ).addClass( 'woocommerce-variation-add-to-cart-disabled' );
			}
		} )

		.on( 'click', '.single_add_to_cart_button', function( event ) {
			var $this = $( this );

			if ( $this.is('.disabled') ) {
				event.preventDefault();

				if ( $this.is('.wc-variation-is-unavailable') ) {
					window.alert( wc_add_to_cart_variation_params.i18n_unavailable_text );
				} else if ( $this.is('.wc-variation-selection-needed') ) {
					window.alert( wc_add_to_cart_variation_params.i18n_make_a_selection_text );
				}
			}
		} )

		// Reload product variations data - allows third-party scripts to modify the available variations data
		.on( 'reload_product_variations', function() {
			$product_variations = $form.data( 'product_variations' );
			$use_ajax           = $product_variations === false;

			$( this ).wc_variation_form().trigger( 'check_variations' );
		} )

		// Reset product data
		.on( 'reset_data', function() {
			$product.find( '.product_meta' ).find( '.sku' ).wc_reset_content();
			$('.product_weight').wc_reset_content();
			$('.product_dimensions').wc_reset_content();
			$form.trigger( 'reset_image' );
			$single_variation.slideUp( 200 ).trigger( 'hide_variation' );
		} )

		// Reset product image
		.on( 'reset_image', function() {
			$form.wc_variations_image_update( false );
		} )

		// On changing an attribute
		.on( 'change', '.variations select', function() {
			$form.find( 'input[name="variation_id"], input.variation_id' ).val( '' ).change();
			$form.find( '.wc-no-matching-variations' ).remove();

			if ( $use_ajax ) {
				if ( $xhr ) {
					$xhr.abort();
				}

				var all_attributes_chosen  = true;
				var some_attributes_chosen = false;
				var data                   = {};

				$attribute_selects.each( function() {
					var attribute_name = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );
					var value          = $( this ).val() || '';

					if ( value.length === 0 ) {
						all_attributes_chosen = false;
					} else {
						some_attributes_chosen = true;
					}

					data[ attribute_name ] = value;
				});

				if ( all_attributes_chosen ) {
					// Get a matchihng variation via ajax
					data.product_id  = $product_id;
					data.custom_data = $form.data( 'custom_data' );

					$form.block( {
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					} );

					$xhr = $.ajax( {
						url: wc_add_to_cart_variation_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_variation' ),
						type: 'POST',
						data: data,
						success: function( variation ) {
							if ( variation ) {
								$form.trigger( 'found_variation', [ variation ] );
							} else {
								$form.trigger( 'reset_data' );
								$form.find( '.single_variation' ).after( '<p class="wc-no-matching-variations woocommerce-info">' + wc_add_to_cart_variation_params.i18n_no_matching_variations_text + '</p>' );
								$form.find( '.wc-no-matching-variations' ).slideDown( 200 );
							}
						},
						complete: function() {
							$form.unblock();
						}
					} );
				} else {
					$form.trigger( 'reset_data' );
				}
				if ( some_attributes_chosen ) {
					if ( $reset_variations.css( 'visibility' ) === 'hidden' ) {
						$reset_variations.css( 'visibility', 'visible' ).hide().fadeIn();
					}
				} else {
					$reset_variations.css( 'visibility', 'hidden' );
				}
			} else {
				$form.trigger( 'woocommerce_variation_select_change' );
				$form.trigger( 'check_variations' );
				$( this ).blur();
			}

			// added to get around variation image flicker issue
			$( '.product.has-default-attributes > .images' ).fadeTo( 200, 1 );

			// Custom event for when variation selection has been changed
			$form.trigger( 'woocommerce_variation_has_changed' );
		} )

		// Upon gaining focus
		.on( 'focusin touchstart', '.variations select', function() {
			if ( 'ontouchstart' in window || navigator.maxTouchPoints ) {
				$( this ).find( 'option:selected' ).attr( 'selected', 'selected' );

				if ( ! $use_ajax ) {
					$form.trigger( 'woocommerce_variation_select_focusin' );
				}
			}
		} )

		// Show single variation details (price, stock, image)
		.on( 'found_variation', function( event, variation ) {
			var $sku        = $product.find( '.product_meta' ).find( '.sku' ),
				$weight     = $product.find( '.product_weight' ),
				$dimensions = $product.find( '.product_dimensions' ),
				$qty        = $single_variation_wrap.find( '.quantity' ),
				purchasable = true;

			// Display SKU
			if ( variation.sku ) {
				$sku.wc_set_content( variation.sku );
			} else {
				$sku.wc_reset_content();
			}

			// Display weight
			if ( variation.weight_html ) {
				$weight.wc_set_content( variation.weight_html );
			} else {
				$weight.wc_reset_content();
			}

			// Display dimensions
			if ( variation.dimensions_html ) {
				$dimensions.wc_set_content( variation.dimensions_html );
			} else {
				$dimensions.wc_reset_content();
			}

			// Show images
			$form.wc_variations_image_update( variation );

			// Output correct templates
			var $template_html = '';

			if ( ! variation.variation_is_visible ) {
				$template_html = unavailable_template();
				// w3 total cache inline minification adds CDATA tags around our HTML (sigh)
				$template_html = $template_html.replace( '/*<![CDATA[*/', '' );
				$template_html = $template_html.replace( '/*]]>*/', '' );
				$single_variation.html( $template_html );
				$form.find( 'input[name="variation_id"], input.variation_id' ).val( '' ).change();
			} else {
				$template_html = template( {
					variation:    variation
				} );
				// w3 total cache inline minification adds CDATA tags around our HTML (sigh)
				$template_html = $template_html.replace( '/*<![CDATA[*/', '' );
				$template_html = $template_html.replace( '/*]]>*/', '' );
				$single_variation.html( $template_html );
				$form.find( 'input[name="variation_id"], input.variation_id' ).val( variation.id ).change();
			}

			// Hide or show qty input
			if ( variation.sold_individually ) {
				$qty.find( 'input.qty' ).val( '1' ).attr( 'min', '1' ).attr( 'max', '' );
				$qty.hide();
			} else {
				$qty.find( 'input.qty' ).attr( 'min', variation.min_qty ).attr( 'max', variation.max_qty );
				$qty.show();
			}

			// Enable or disable the add to cart button
			if ( ! variation.is_purchasable || ! variation.is_in_stock || ! variation.variation_is_visible ) {
				purchasable = false;
			}

			// Reveal
			if ( $.trim( $single_variation.text() ) ) {
				$single_variation.slideDown( 200 ).trigger( 'show_variation', [ variation, purchasable ] );
			} else {
				$single_variation.show().trigger( 'show_variation', [ variation, purchasable ] );
			}
		})

		// Check variations
		.on( 'check_variations', function( event ) {
			if ( $use_ajax ) {
				return;
			}

			var all_attributes_chosen  = true,
				some_attributes_chosen = false,
				current_settings       = {},
				$form                  = $( this );

			$form.trigger( 'update_variation_values' );

			$attribute_selects.each( function() {
				var attribute_name = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );
				var value          = $( this ).val() || '';

				if ( value.length === 0 ) {
					all_attributes_chosen = false;
				} else {
					some_attributes_chosen = true;
				}

				current_settings[ attribute_name ] = value;
			});

			// Find the variations that match with the attributes chosen so far
			var matching_variations = wc_variation_form_matcher.find_matching_variations( $product_variations, current_settings );

			if ( all_attributes_chosen ) {

				var variation = matching_variations.length > 0 ? matching_variations[0] : false;

				if ( variation ) {
					$form.trigger( 'found_variation', [ variation ] );
				} else if ( 'locking' === selection_ux ) {
					// Nothing found - reset fields
					$attribute_selects.val( '' );
					$form.trigger( 'reset_data' );
					window.alert( wc_add_to_cart_variation_params.i18n_no_matching_variations_text );
				}

			} else {

				$form.trigger( 'reset_data' );
				$single_variation.slideUp( 200 ).trigger( 'hide_variation' );
			}

			if ( some_attributes_chosen ) {
				if ( $reset_variations.css( 'visibility' ) === 'hidden' ) {
					$reset_variations.css( 'visibility', 'visible' ).hide().fadeIn();
				}
			} else {
				$reset_variations.css( 'visibility', 'hidden' );
			}
		} )

		// Disable option fields that are unavaiable for current set of attributes
		.on( 'update_variation_values', function( event ) {
			if ( $use_ajax ) {
				return;
			}

			// Collect current settings.
			var current_settings = {};

			$attribute_selects.each( function( setts_index, setts_el ) {
				var attribute_name = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' ),
					value          = $( this ).val() || '';
				current_settings[ attribute_name ] = value;
			} );

			// Loop through selects and disable/enable options based on selections
			$attribute_selects.each( function( index, el ) {

				var current_attr_select     = $( el ),
					current_attr_name       = current_attr_select.data( 'attribute_name' ) || current_attr_select.attr( 'name' ),
					ux                      = 'locking' !== selection_ux ? 'non-locking' : 'locking',
					show_option_none        = $( el ).data( 'show_option_none' ),
					option_gt_filter        = ':gt(0)',
					attached_options_count  = 0,
					new_attr_select         = $( '<select/>' ),
					selected_attr_val       = current_attr_select.val() || '',
					selected_attr_val_valid = true;

				// Reference options set
				if ( ! current_attr_select.data( 'attribute_options' ) ) {
					var ref_attr_select = current_attr_select.clone();
					ref_attr_select.find( 'option' ).removeClass( 'attached enabled' ).removeAttr( 'disabled' ).removeAttr( 'selected' );
					current_attr_select.data( 'attribute_options', ref_attr_select.html() );
				}

				new_attr_select.html( current_attr_select.data( 'attribute_options' ) );

				// The attribute of this select field should not be taken into account when calculating its matching variations:
				// The constraints of this attribute are shaped by the values of the other attributes.
				var settings = $.extend( true, {}, current_settings );

				// Selections UX is 'non-locking': The constraints of this attribute are shaped by the values of all preceding attributes.
				if ( 'non-locking' === ux ) {
					$attribute_selects.each( function( inner_index, inner_el ) {
						if ( inner_index >= index ) {
							var inner_attr_select = $( inner_el ),
								inner_attr_name   = inner_attr_select.data( 'attribute_name' ) || inner_attr_select.attr( 'name' );

							settings[ inner_attr_name ] = '';
						}
					} );
				// Selections UX is 'locking': The constraints of this attribute are shaped by the values of all other attributes.
				} else {
					settings[ current_attr_name ] = '';
				}

				// As a consequence, the globally 'matching_variations' might be fewer than the matches "seen" by this attribute.
				var variations = wc_variation_form_matcher.find_matching_variations( $product_variations, settings );

				// Loop through variations.
				for ( var num in variations ) {

					if ( typeof( variations[ num ] ) !== 'undefined' ) {

						var attributes = variations[ num ].attributes;

						for ( var attr_name in attributes ) {
							if ( attributes.hasOwnProperty( attr_name ) ) {
								var attr_val = attributes[ attr_name ];

								if ( attr_name === current_attr_name ) {

									var variation_active = '';

									if ( variations[ num ].variation_is_active ) {
										variation_active = 'enabled';
									}

									if ( attr_val ) {

										// Decode entities.
										attr_val = $( '<div/>' ).html( attr_val ).text();

										// Add slashes.
										attr_val = attr_val.replace( /'/g, '\\\'' );
										attr_val = attr_val.replace( /"/g, '\\\"' );

										// Attach.
										new_attr_select.find( 'option[value="' + attr_val + '"]' ).addClass( 'attached ' + variation_active );

									} else {
										// Attach all apart from placeholder.
										new_attr_select.find( 'option:gt(0)' ).addClass( 'attached ' + variation_active );
									}
								}
							}
						}
					}
				}

				// Count available options.
				attached_options_count = new_attr_select.find( 'option.attached' ).length;

				// Check if current selection is in attached options.
				if ( selected_attr_val && ( attached_options_count === 0 || new_attr_select.find( 'option.attached[value="' + selected_attr_val + '"]' ).length === 0 ) ) {
					selected_attr_val_valid = false;
				}

				// Detach the placeholder if:
				// - Valid options exist.
				// - The current selection is non-empty.
				// - The current selection is valid.
				// - Placeholders are not set to be permanently visible.
				if ( attached_options_count > 0 && selected_attr_val && selected_attr_val_valid && ( 'no' === show_option_none ) ) {
					new_attr_select.find( 'option:first' ).remove();
					option_gt_filter = '';
				}

				// Detach unattached.
				new_attr_select.find( 'option' + option_gt_filter + ':not(.attached)' ).remove();

				// Grey out disabled.
				new_attr_select.find( 'option' + option_gt_filter + ':not(.enabled)' ).attr( 'disabled', 'disabled' );

				// Choose selected.
				if ( selected_attr_val ) {
					// If the previously selected value is no longer available, fall back to the placeholder (it's going to be there).
					if ( selected_attr_val_valid ) {
						new_attr_select.find( 'option[value="' + selected_attr_val + '"]' ).attr( 'selected', 'selected' );
					} else {
						new_attr_select.find( 'option:eq(0)' ).attr( 'selected', 'selected' );
						if ( 'non-locking' === ux ) {
							current_settings[ current_attr_name ] = '';
						}
					}
				}

				// Copy to DOM.
				current_attr_select.html( new_attr_select.html() );

			} );

			// Custom event for when variations have been updated
			$form.trigger( 'woocommerce_update_variation_values' );
		} );

		$form.trigger( 'wc_variation_form' );

		return $form;
	};

	/**
	 * Matches inline variation objects to chosen attributes
	 * @type {Object}
	 */
	var wc_variation_form_matcher = {
		find_matching_variations: function( product_variations, settings ) {
			var matching = [];
			for ( var i = 0; i < product_variations.length; i++ ) {
				var variation    = product_variations[i];

				if ( wc_variation_form_matcher.variations_match( variation.attributes, settings ) ) {
					matching.push( variation );
				}
			}
			return matching;
		},
		variations_match: function( attrs1, attrs2 ) {
			var match = true;
			for ( var attr_name in attrs1 ) {
				if ( attrs1.hasOwnProperty( attr_name ) ) {
					var val1 = attrs1[ attr_name ];
					var val2 = attrs2[ attr_name ];
					if ( val1 !== undefined && val2 !== undefined && val1.length !== 0 && val2.length !== 0 && val1 !== val2 ) {
						match = false;
					}
				}
			}
			return match;
		}
	};

	/**
	 * Stores the default text for an element so it can be reset later
	 */
	$.fn.wc_set_content = function( content ) {
		if ( undefined === this.attr( 'data-o_content' ) ) {
			this.attr( 'data-o_content', this.text() );
		}
		this.text( content );
	};

	/**
	 * Stores the default text for an element so it can be reset later
	 */
	$.fn.wc_reset_content = function() {
		if ( undefined !== this.attr( 'data-o_content' ) ) {
			this.text( this.attr( 'data-o_content' ) );
		}
	};

	/**
	 * Stores a default attribute for an element so it can be reset later
	 */
	$.fn.wc_set_variation_attr = function( attr, value ) {
		if ( undefined === this.attr( 'data-o_' + attr ) ) {
			this.attr( 'data-o_' + attr, ( ! this.attr( attr ) ) ? '' : this.attr( attr ) );
		}
		if ( false === value ) {
			this.removeAttr( attr );
		} else {
			this.attr( attr, value );
		}
	};

	/**
	 * Reset a default attribute for an element so it can be reset later
	 */
	$.fn.wc_reset_variation_attr = function( attr ) {
		if ( undefined !== this.attr( 'data-o_' + attr ) ) {
			this.attr( attr, this.attr( 'data-o_' + attr ) );
		}
	};

	/**
	 * Sets product images for the chosen variation
	 */
	$.fn.wc_variations_image_update = function( variation ) {
		var $form             = this,
			$product          = $form.closest('.product'),
			$gallery_img      = $product.find( '.flex-control-nav li:eq(0) img' ),
			$product_img_wrap = $product.find( '.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image:eq(0)' ),
			$product_img      = $product.find( '.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image:eq(0) .wp-post-image' );

		if ( variation && variation.image && variation.image.src.length > 1 ) {
			$product_img.wc_set_variation_attr( 'src', variation.image.src );
			$product_img.wc_set_variation_attr( 'height', variation.image.src_h );
			$product_img.wc_set_variation_attr( 'width', variation.image.src_w );
			$product_img.wc_set_variation_attr( 'srcset', variation.image.srcset );
			$product_img.wc_set_variation_attr( 'sizes', variation.image.sizes );
			$product_img.wc_set_variation_attr( 'title', variation.image.title );
			$product_img.wc_set_variation_attr( 'alt', variation.image.alt );
			$product_img.wc_set_variation_attr( 'data-large-image', variation.image.full_src );
			$product_img.wc_set_variation_attr( 'data-large-image-width', variation.image.full_src_w );
			$product_img.wc_set_variation_attr( 'data-large-image-height', variation.image.full_src_h );
			$product_img_wrap.wc_set_variation_attr( 'data-thumb', variation.image.src );
		} else {
			$product_img_wrap.wc_reset_variation_attr( 'data-thumb' );
			$product_img.wc_reset_variation_attr( 'large-image' );
			$product_img.wc_reset_variation_attr( 'src' );
			$product_img.wc_reset_variation_attr( 'width' );
			$product_img.wc_reset_variation_attr( 'height' );
			$product_img.wc_reset_variation_attr( 'srcset' );
			$product_img.wc_reset_variation_attr( 'sizes' );
			$product_img.wc_reset_variation_attr( 'title' );
			$product_img.wc_reset_variation_attr( 'alt' );
			$gallery_img.wc_reset_variation_attr( 'src' );
			$product_img.wc_reset_variation_attr( 'data-large-image' );
			$product_img.wc_reset_variation_attr( 'data-large-image-width' );
			$product_img.wc_reset_variation_attr( 'data-large-image-height' );

			window.setTimeout( function() {
				$( window ).trigger( 'resize' );
			}, 10 );
		}
		$('body').trigger( 'woocommerce_init_gallery' );
	};

	$( function() {
		if ( typeof wc_add_to_cart_variation_params !== 'undefined' ) {
			$( '.variations_form' ).each( function() {
				$( this ).wc_variation_form().trigger( 'check_variations' );
			});
		}
	});

})( jQuery, window, document );
