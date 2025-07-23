;(function ( $, document ) {

	/**
	 * Back in stock form manager.
	 *
	 * @param jQuery $form The form element.
	 */
	var BISFormManager = function( $variationsForm ) {

		// Properties.
		var self               = this;
		self.$variationsForm   = $variationsForm;
		self.product_id        = self.$variationsForm.data( 'product_id' );
		self.$formContainer    = $( '.wc_bis_form[data-bis-product-id="' + self.product_id + '"]' );
		self.$form             = self.$formContainer.find( 'form' );
		self.$formProductInput = self.$formContainer.find( 'input[name="wc_bis_product_id"]' );

		// Variation Events.
		self.$variationsForm.off( '.wc-bis-form' );
		self.$variationsForm.on( 'found_variation.wc-bis-form', { bisForm: self }, self.onFoundVariation );
		self.$variationsForm.on( 'show_variation.wc-bis-form', { bisForm: self }, self.onShowVariation );
		self.$variationsForm.on( 'reset_data.wc-bis-form', { bisForm: self }, self.onAnnounceReset );

		// Form Events.
		self.$form.off( '.wc-bis-form' );
		self.$form.on( 'submit.wc-bis-form', { bisForm: self }, self.onSendForm );
	};

	/**
	 * Handle found variation.
	 *
	 * @param {Event} event The event object.
	 * @param {Object} variation The variation object.
	 */
	BISFormManager.prototype.onFoundVariation = function( event, variation ) {
		var form = event.data.bisForm;
		if ( variation.is_in_stock && variation.is_purchasable ) {
			return;
		}

		if ( ! variation.variation_is_active || ! variation.variation_is_visible ) {
			return;
		}

		form.$formProductInput.val( variation.variation_id ).trigger( 'change' );
	};

	/**
	 * Handle show variation.
	 *
	 * @param {Event} event The event object.
	 * @param {Object} variation The variation object.
	 */
	BISFormManager.prototype.onShowVariation = function( event, variation ) {
		var form = event.data.bisForm;
		if ( variation.is_in_stock && variation.is_purchasable ) {
			form.$formContainer.addClass( 'hidden' );
			return;
		}

		if ( ! variation.variation_is_active || ! variation.variation_is_visible ) {
			form.$formContainer.addClass( 'hidden' );
			return;
		}

		form.$formContainer.removeClass( 'hidden' );
	};

	/**
	 * Handle announce reset.
	 *
	 * @param {Event} event The event object.
	 */
	BISFormManager.prototype.onAnnounceReset = function( event ) {
		var form = event.data.bisForm;
		form.$formProductInput.val( form.product_id ).trigger( 'change' );
		form.$formContainer.addClass( 'hidden' );
	};

	/**
	 * Handle send form.
	 *
	 * @param {Event} event The event object.
	 */
	BISFormManager.prototype.onSendForm = function( event ) {

		var form = event.data.bisForm;
		if ( ! form.$variationsForm.length ) {
			return;
		}

		var $attributes = form.$variationsForm.find( '.variations select' );
		if ( $attributes.length ) {

			// Build dynamic hidden form fields.
			$attributes.each( function( index, el ) {

				var $attribute_field = $( el )
				var $input = $( '<input/>' );
				$input.val( $attribute_field.val() );
				$input.prop( 'name', $attribute_field.attr( 'name' ) );
				$input.prop( 'type', 'hidden' );
				if ( ! form.$form.find( 'input[name="' + $input.prop( 'name' ) + '"]' ).length ) {
					form.$form.append( $input );
				}
			} );
		}
	};

	/**
	 * Extend jQuery.
	 */
	$.fn.extend( {
		wc_back_in_stock_form: function() {
			return this.each( function() {
				new BISFormManager( $( this ) );
			} );
		}
	} );

	// Initialize the form manager on DOM ready.
	$( function() {
		$( '.variations_form' ).wc_back_in_stock_form();
	});

})( jQuery, document );
