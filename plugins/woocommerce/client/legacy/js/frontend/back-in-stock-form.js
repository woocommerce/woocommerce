;(function ( $, document ) {

	/**
	 * Back in stock form manager.
	 */
	var BISFormManager = {

		/**
		 * Initialize the form manager.
		 */
		init: function() {
			$( document ).off( 'click.wc-bis-form' ).on( 'click.wc-bis-form', '.wc_bis_send_form', this.handleSubmit.bind( this ) );
		},

		/**
		 * Handle the form submit event.
		 *
		 * @param {jQuery.Event} event - The event object.
		 */
		handleSubmit: function( event ) {
			event.preventDefault();
			event.stopPropagation();

			var $form = $( event.target ).closest( '#wc_bis_product_form' );
			if ( ! $form.length ) {
				return;
			}

			var data = this.getFormData( $form );
			if ( Object.keys( data ).length > 0 ) {
				this.submitFormData( data );
			}
		},

		/**
		 * Submit form data by creating a hidden form and submitting it.
		 *
		 * @param {Object} data - The form data to submit.
		 */
		submitFormData: function( data ) {
			// Remove any existing hidden form
			var $existingForm = $( '#wc_bis_hidden_submit_form' );
			if ( $existingForm.length ) {
				$existingForm.remove();
			}

			// Create hidden form
			var $hiddenForm = $( '<form/>' );
			$hiddenForm.attr({
				'id': 'wc_bis_hidden_submit_form',
				'method': 'POST',
				'style': 'display: none;'
			});

			// Add form data as hidden inputs
			for ( var key in data ) {
				if ( data.hasOwnProperty( key ) ) {
					if ( key === 'variation_attributes' && Array.isArray( data[ key ] ) ) {
						// Handle variation attributes array
						for ( var i = 0; i < data[ key ].length; i++ ) {
							this.addHiddenInput( data[ key ][ i ].name, data[ key ][ i ].value, $hiddenForm );
						}
					} else {
						console.log( 'Adding hidden input for key: ' + key );
						this.addHiddenInput( key, data[ key ], $hiddenForm );
					}
				}
			}

			this.addHiddenInput( 'action', 'wc_bis_register', $hiddenForm );

			// Append to body and submit
			$( 'body' ).append( $hiddenForm );
			$hiddenForm.submit();
		},

		/**
		 * Add a hidden input to the form.
		 *
		 * @param {string} name - The input name.
		 * @param {string|number|boolean} value - The input value.
		 * @param {jQuery} $form - The form to append to.
		 */
		addHiddenInput: function( name, value, $form ) {
			// Validate and sanitize based on field type
			var sanitizedName = this.validateFieldName( name );
			var sanitizedValue = this.validateFieldValue( name, value );

			// Only add if both name and value are valid
			if ( sanitizedName && sanitizedValue !== false ) {
				var $input = $( '<input/>' );
				$input.attr({
					'type': 'hidden',
					'name': sanitizedName,
					'value': sanitizedValue
				});
				$form.append( $input );
			}
		},

		/**
		 * Validate field name.
		 *
		 * @param {string} name - The field name.
		 * @returns {string|false} The validated name or false if invalid.
		 */
		validateFieldName: function( name ) {
			if ( typeof name !== 'string' ) {
				return false;
			}

			// Allow only alphanumeric, underscore, hyphen, and square brackets
			var cleanName = name.replace( /[^a-zA-Z0-9_\-\[\]]/g, '' );

			// Must not be empty and max 50 chars
			return cleanName.length > 0 ? cleanName : false;
		},

		/**
		 * Validate field value based on field type.
		 *
		 * @param {string} name - The field name.
		 * @param {*} value - The field value.
		 * @returns {string|false} The validated value or false if invalid.
		 */
		validateFieldValue: function( name, value ) {
			switch ( name ) {
				case 'wc_bis_product_id':
				case 'wc_bis_variation_id':
					var intValue = parseInt( value, 10 );
					return intValue > 0 ? String( intValue ) : false;

				case 'wc_bis_email':
					var emailValue = String( value ).trim();
					var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					return emailRegex.test( emailValue ) && emailValue.length <= 100 ? emailValue : false;

				case 'wc_bis_opt_in':
					return value === true ? 'true' : 'false';

				case 'action':
					var actionValue = String( value );
					return actionValue === 'wc_bis_register' ? actionValue : false;

				default:
					// For variation attributes (attribute_pa_color, etc.)
					if ( name.indexOf( 'attribute_' ) === 0 ) {
						var attrValue = String( value ).trim();
						var cleanAttr = attrValue.replace( /[^a-zA-Z0-9\s\-_]/g, '' );
						return cleanAttr.length > 0 ? cleanAttr : false;
					}

					// Fallback for unknown fields - reject
					return false;
			}
		},

		/**
		 * Get the form data.
		 *
		 * @param {jQuery} $form - The form.
		 * @returns {Object} The form data.
		 */
		getFormData: function( $form ) {
			var data = {};
			var $emailInput = $form.find( '#wc_bis_email' );
			var $optInCheckbox = $form.find( '#wc_bis_opt_in' );

			if ( $emailInput.length ) {
				data.wc_bis_email = $emailInput.val();
			}

			if ( $optInCheckbox.length ) {
				data.wc_bis_opt_in = $optInCheckbox.is( ':checked' );
			}

			// Parse product ID
			var productId = this.parseProductId( $form );
			if ( productId ) {
				data.wc_bis_product_id = productId;
			}

			// Parse variation ID and attributes
			var variationData = this.parseVariationData( $form );
			if ( variationData.variationId ) {
				data.wc_bis_variation_id = variationData.variationId;
			}
			if ( data.wc_bis_variation_id && variationData.attributes && variationData.attributes.length > 0 ) {
				data.variation_attributes = variationData.attributes;
			}
			console.log( data );
			return data;
		},

		/**
		 * Parse product id from the form data attribute.
		 *
		 * @param {jQuery} $container - The container of the form.
		 * @returns {number} The parsed product id.
		 */
		parseProductId: function( $container ) {
			var productId = false;

			if ( $container.length ) {
				productId = parseInt( $container.data( 'bis-product-id' ), 10 );
			}

			if ( ! productId || 0 === productId ) {
				console.warn( 'BIS Warning: Could not parse product id.' );
				return false;
			}

			return productId;
		},


		/**
		 * Parse variation data from the form.
		 *
		 * @param {jQuery} $container - The container of the form.
		 * @returns {Object} The parsed variation data.
		 */
		parseVariationData: function( $container ) {
			var result = {
				variationId: false,
				attributes: []
			};

			var $variationsForm = $container.closest( 'form.variations_form' );

			if ( $variationsForm.length ) {
				// Parse variation ID
				var $variationAddToCartForm = $variationsForm.find( '.woocommerce-variation-add-to-cart' ).first();
				var variationId = parseInt( $variationAddToCartForm.find( 'input[name="variation_id"]' ).val(), 10 );

				if ( variationId && variationId > 0 ) {
					result.variationId = variationId;
				}

				// Parse variation attributes
				var $attributeFields = $variationsForm.find( '.variations select' );
				$attributeFields.each( function() {
					var $field = $( this );
					var name = $field.prop( 'name' );
					var value = $field.val();

					if ( name && value ) {
						result.attributes.push({
							name: name,
							value: value
						});
					}
				});
			}

			return result;
		}
	};

	// Initialize the form manager on DOM ready.
	$( function() {
		BISFormManager.init();
	});

})( jQuery, document );