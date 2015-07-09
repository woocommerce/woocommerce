/*!
 * Variations Plugin
 */
;(function ( $, window, document, undefined ) {

	$.fn.wc_variation_form = function() {
		var $form               = this;
		var $product            = $form.closest('.product');
		var $product_id         = parseInt( $form.data( 'product_id' ) );
		var $product_variations = $form.data( 'product_variations' );
		var $use_ajax           = $product_variations === false;
		var $xhr                = false;
		var $reset_variations   = $form.find( '.reset_variations' );

		// Unbind any existing events
		$form.unbind( 'check_variations update_variation_values found_variation' );
		$form.find( '.reset_variations' ).unbind( 'click' );
		$form.find( '.variations select' ).unbind( 'change focusin' );

		// Bind new events to form
		$form

		// On clicking the reset variation button
		.on( 'click', '.reset_variations', function( event ) {
			$form.find( '.variations select' ).val( '' ).change();
			$form.triggerHandler( 'reset_data' );
			return false;
		} )

		// Reset product data
		.on( 'reset_data', function( event ) {
			var to_reset = {
				'.sku': 'o_sku',
				'.product_weight': 'o_weight',
				'.product_dimensions': 'o_dimensions'
			};
			$.each( to_reset, function( selector, data_attribute ) {
				var $el = $product.find( selector );
				if ( $el.attr( 'data-' + data_attribute ) ) {
					$el.text( $el.attr( 'data-' + data_attribute ) );
				}
			});
			$form.find( '.woocommerce-variation-description' ).remove();
			$form.triggerHandler( 'reset_image' );
			$form.find( '.single_variation_wrap' ).slideUp( 200 ).triggerHandler( 'hide_variation' );
		} )

		// Reset product image
		.on( 'reset_image', function( event ) {
			var $product_img = $product.find( 'div.images img:eq(0)' ),
				$product_link = $product.find( 'div.images a.zoom:eq(0)' ),
				o_src = $product_img.attr( 'data-o_src' ),
				o_title = $product_img.attr( 'data-o_title' ),
				o_alt = $product_img.attr( 'data-o_title' ),
				o_href = $product_link.attr( 'data-o_href' );

			if ( o_src !== undefined ) {
				$product_img.attr( 'src', o_src );
			}
			if ( o_href !== undefined ) {
				$product_link.attr( 'href', o_href );
			}
			if ( o_title !== undefined ) {
				$product_img.attr( 'title', o_title );
				$product_link.attr( 'title', o_title );
			}
			if ( o_alt !== undefined ) {
				$product_img.attr( 'alt', o_alt );
			}
		} )

		// On changing an attribute
		.on( 'change', '.variations select', function( event ) {
			$form.find( 'input[name="variation_id"], input.variation_id' ).val( '' ).change();
			$form.find( '.wc-no-matching-variations' ).remove();

			if ( $use_ajax ) {
				if ( $xhr ) {
					$xhr.abort();
				}

				var all_attributes_chosen  = true;
				var some_attributes_chosen = false;
				var data                   = {};

				$form.find( '.variations select' ).each( function() {
					var attribute_name = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );

					if ( $( this ).val().length === 0 ) {
						all_attributes_chosen = false;
					} else {
						some_attributes_chosen = true;
					}

					data[ attribute_name ] = $( this ).val();
				});

				if ( all_attributes_chosen ) {
					// Get a matchihng variation via ajax
					data.product_id = $product_id;

					$xhr = $.ajax( {
						url: wc_cart_fragments_params.wc_ajax_url + 'get_variation/',
						type: 'POST',
						data: data,
						success: function( variation ) {
							if ( variation ) {
								$form.find( 'input[name="variation_id"], input.variation_id' )
									.val( variation.variation_id )
									.change();
								$form.triggerHandler( 'found_variation', [ variation ] );
							} else {
								$form.triggerHandler( 'reset_data' );
								$form.find( '.single_variation_wrap' ).after( '<p class="wc-no-matching-variations woocommerce-info">' + wc_add_to_cart_variation_params.i18n_no_matching_variations_text + '</p>' );
								$form.find( '.wc-no-matching-variations' ).slideDown( 200 );
							}
						}
					} );
				} else {
					$form.triggerHandler( 'reset_data' );
				}
				if ( some_attributes_chosen ) {
					if ( $reset_variations.css( 'visibility' ) === 'hidden' ) {
						$reset_variations.css( 'visibility', 'visible' ).hide().fadeIn();
					}
				} else {
					$reset_variations.css( 'visibility', 'hidden' );
				}
			} else {
				$form.triggerHandler( 'woocommerce_variation_select_change' )
				$form.triggerHandler( 'check_variations', [ '', false ] );
				$( this ).blur();
			}

			// Custom event for when variation selection has been changed
			$form.triggerHandler( 'woocommerce_variation_has_changed' );
		} )

		// Upon gaining focus
		.on( 'focusin touchstart', '.variations select', function( event ) {
			if ( ! $use_ajax ) {
				$form.triggerHandler( 'woocommerce_variation_select_focusin' )
				$form.triggerHandler( 'check_variations', [ $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' ), true ] );
			}
		} )

		// Show single variation details (price, stock, image)
		.on( 'found_variation', function( event, variation ) {
			var $product_img = $product.find( 'div.images img:eq(0)' ),
				$product_link = $product.find( 'div.images a.zoom:eq(0)' ),
				o_src = $product_img.attr( 'data-o_src' ),
				o_title = $product_img.attr( 'data-o_title' ),
				o_alt = $product_img.attr( 'data-o_alt' ),
				o_href = $product_link.attr( 'data-o_href' ),
				variation_image = variation.image_src,
				variation_link  = variation.image_link,
				variation_caption = variation.image_caption,
				variation_title = variation.image_title,
				variation_alt = variation.image_alt;

			$form.find( '.variations_button' ).show();
			$form.find( '.single_variation' ).html( variation.price_html + variation.availability_html );

			if ( o_src === undefined ) {
				o_src = ( ! $product_img.attr( 'src' ) ) ? '' : $product_img.attr( 'src' );
				$product_img.attr( 'data-o_src', o_src );
			}

			if ( o_href === undefined ) {
				o_href = ( ! $product_link.attr( 'href' ) ) ? '' : $product_link.attr( 'href' );
				$product_link.attr( 'data-o_href', o_href );
			}

			if ( o_title === undefined ) {
				o_title = ( ! $product_img.attr( 'title' ) ) ? '' : $product_img.attr( 'title' );
				$product_img.attr( 'data-o_title', o_title );
			}

			if ( o_alt === undefined ) {
				o_alt = ( ! $product_img.attr( 'alt' ) ) ? '' : $product_img.attr( 'alt' );
				$product_img.attr( 'data-o_alt', o_alt );
			}

			if ( variation_image && variation_image.length > 1 ) {
				$product_img
					.attr( 'src', variation_image )
					.attr( 'alt', variation_title )
					.attr( 'title', variation_title );
				$product_link
					.attr( 'href', variation_link )
					.attr( 'title', variation_caption );
			} else {
				$product_img
					.attr( 'src', o_src )
					.attr( 'alt', o_alt )
					.attr( 'title', o_title );
				$product_link
					.attr( 'href', o_href )
					.attr( 'title', o_title );
			}

			var $single_variation_wrap = $form.find( '.single_variation_wrap' ),
				$sku = $product.find( '.product_meta' ).find( '.sku' ),
				$weight = $product.find( '.product_weight' ),
				$dimensions = $product.find( '.product_dimensions' );

			if ( ! $sku.attr( 'data-o_sku' ) ) {
				$sku.attr( 'data-o_sku', $sku.text() );
			}

			if ( ! $weight.attr( 'data-o_weight' ) ) {
				$weight.attr( 'data-o_weight', $weight.text() );
			}

			if ( ! $dimensions.attr( 'data-o_dimensions' ) ) {
				$dimensions.attr( 'data-o_dimensions', $dimensions.text() );
			}

			if ( variation.sku ) {
				$sku.text( variation.sku );
			} else {
				$sku.text( $sku.attr( 'data-o_sku' ) );
			}

			if ( variation.weight ) {
				$weight.text( variation.weight );
			} else {
				$weight.text( $weight.attr( 'data-o_weight' ) );
			}

			if ( variation.dimensions ) {
				$dimensions.text( variation.dimensions );
			} else {
				$dimensions.text( $dimensions.attr( 'data-o_dimensions' ) );
			}

			$single_variation_wrap.find( '.quantity' ).show();

			if ( ! variation.is_purchasable || ! variation.is_in_stock || ! variation.variation_is_visible ) {
				$form.find( '.variations_button' ).hide();
			}

			if ( ! variation.variation_is_visible ) {
				$form.find( '.single_variation' ).html( '<p>' + wc_add_to_cart_variation_params.i18n_unavailable_text + '</p>' );
			}

			if ( variation.min_qty !== '' ) {
				$single_variation_wrap.find( '.quantity input.qty' ).attr( 'min', variation.min_qty ).val( variation.min_qty );
			} else {
				$single_variation_wrap.find( '.quantity input.qty' ).removeAttr( 'min' );
			}

			if ( variation.max_qty !== '' ) {
				$single_variation_wrap.find( '.quantity input.qty' ).attr( 'max', variation.max_qty );
			} else {
				$single_variation_wrap.find( '.quantity input.qty' ).removeAttr( 'max' );
			}

			if ( variation.is_sold_individually === 'yes' ) {
				$single_variation_wrap.find( '.quantity input.qty' ).val( '1' );
				$single_variation_wrap.find( '.quantity' ).hide();
			}

			// display variation description
			$form.find( '.woocommerce-variation-description' ).remove();

			if ( variation.variation_description ) {
				$form.find( '.single_variation_wrap' ).prepend( '<div class="woocommerce-variation-description">' + variation.variation_description + '</div>' );
			}

			$single_variation_wrap.slideDown( 200 ).triggerHandler( 'show_variation', [ variation ] );
		})

		// Check variations
		.on( 'check_variations', function( event, exclude, focus ) {
			if ( $use_ajax ) {
				return;
			}

			var all_attributes_chosen = true,
				some_attributes_chosen = false,
				showing_variation = false,
				current_settings = {},
				$form = $( this ),
				$reset_variations = $form.find( '.reset_variations' );

			$form.find( '.variations select' ).each( function() {
				var attribute_name = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );

				if ( $( this ).val().length === 0 ) {
					all_attributes_chosen = false;
				} else {
					some_attributes_chosen = true;
				}

				if ( exclude && attribute_name === exclude ) {
					all_attributes_chosen = false;
					current_settings[ attribute_name ] = '';
				} else {
					// Add to settings array
					current_settings[ attribute_name ] = $( this ).val();
				}
			});

			var matching_variations = wc_variation_form_matcher.find_matching_variations( $product_variations, current_settings );

			if ( all_attributes_chosen ) {

				var variation = matching_variations.shift();

				if ( variation ) {
					$form.find( 'input[name="variation_id"], input.variation_id' )
						.val( variation.variation_id )
						.change();
					$form.triggerHandler( 'found_variation', [ variation ] );
				} else {
					// Nothing found - reset fields
					$form.find( '.variations select' ).val( '' );

					if ( ! focus ) {
						$form.triggerHandler( 'reset_data' );
					}

					alert( wc_add_to_cart_variation_params.i18n_no_matching_variations_text );
				}

			} else {

				$form.triggerHandler( 'update_variation_values', [ matching_variations ] );

				if ( ! focus ) {
					$form.triggerHandler( 'reset_data' );
				}

				if ( ! exclude ) {
					$form.find( '.single_variation_wrap' ).slideUp( 200 ).triggerHandler( 'hide_variation' );
				}
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
		.on( 'update_variation_values', function( event, variations ) {
			if ( $use_ajax ) {
				return;
			}
			// Loop through selects and disable/enable options based on selections
			$form.find( '.variations select' ).each( function( index, el ) {

				current_attr_select = $( el );

				// Reset options
				if ( ! current_attr_select.data( 'attribute_options' ) ) {
					current_attr_select.data( 'attribute_options', current_attr_select.find( 'option:gt(0)' ).get() );
				}

				current_attr_select.find( 'option:gt(0)' ).remove();
				current_attr_select.append( current_attr_select.data( 'attribute_options' ) );
				current_attr_select.find( 'option:gt(0)' ).removeClass( 'attached' );
				current_attr_select.find( 'option:gt(0)' ).removeClass( 'enabled' );
				current_attr_select.find( 'option:gt(0)' ).removeAttr( 'disabled' );

				// Get name from data-attribute_name, or from input name if it doesn't exist
				if ( typeof( current_attr_select.data( 'attribute_name' ) ) != 'undefined' ) {
					current_attr_name = current_attr_select.data( 'attribute_name' );
				} else {
					current_attr_name = current_attr_select.attr( 'name' );
				}

				// Loop through variations
				for ( var num in variations ) {

					if ( typeof( variations[ num ] ) != 'undefined' ) {

						var attributes = variations[ num ].attributes;

						for ( var attr_name in attributes ) {
							if ( attributes.hasOwnProperty( attr_name ) ) {
								var attr_val = attributes[ attr_name ];

								if ( attr_name == current_attr_name ) {

									if ( variations[ num ].variation_is_active )
										variation_active = 'enabled';
									else
										variation_active = '';

									if ( attr_val ) {

										// Decode entities
										attr_val = $( '<div/>' ).html( attr_val ).text();

										// Add slashes
										attr_val = attr_val.replace( /'/g, "\\'" );
										attr_val = attr_val.replace( /"/g, "\\\"" );

										// Compare the meerkat
										current_attr_select.find( 'option[value="' + attr_val + '"]' ).addClass( 'attached ' + variation_active );

									} else {

										current_attr_select.find( 'option:gt(0)' ).addClass( 'attached ' + variation_active );

									}
								}
							}
						}
					}
				}

				// Detach unattached
				current_attr_select.find( 'option:gt(0):not(.attached)' ).remove();

				// Grey out disabled
				current_attr_select.find( 'option:gt(0):not(.enabled)' ).attr( 'disabled', 'disabled' );

			});

			// Custom event for when variations have been updated
			$form.triggerHandler( 'woocommerce_update_variation_values' );
		});

		$form.triggerHandler( 'wc_variation_form' );

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
				var variation_id = variation.variation_id;

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

	$( function() {
		if ( typeof wc_add_to_cart_variation_params !== 'undefined' ) {
			$( '.variations_form' ).wc_variation_form().find('.variations select:eq(0)').change();
		}
	});

})( jQuery, window, document );