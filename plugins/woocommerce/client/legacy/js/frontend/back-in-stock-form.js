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
		self.$form             = $( '.wc_bis_form[data-bis-product-id="' + self.product_id + '"]' );
		self.$formProductInput = self.$form.find( 'input[name="wc_bis_product_id"]' );

		// Events.
		self.$variationsForm.off( '.wc-bis-form' );
		self.$variationsForm.on( 'found_variation.wc-bis-form', { bisForm: self }, self.onFoundVariation );
		self.$variationsForm.on( 'show_variation.wc-bis-form', { bisForm: self }, self.onShowVariation );
		self.$variationsForm.on( 'reset_data.wc-bis-form', { bisForm: self }, self.onAnnounceReset );
	};

	/**
	 * Handle found variation.
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
	 */
	BISFormManager.prototype.onShowVariation = function( event, variation ) {
		var form = event.data.bisForm;
		if ( variation.is_in_stock && variation.is_purchasable ) {
			form.$form.addClass( 'hidden' );
			return;
		}

		if ( ! variation.variation_is_active || ! variation.variation_is_visible ) {
			form.$form.addClass( 'hidden' );
			return;
		}

		form.$form.removeClass( 'hidden' );
	};

	/**
	 * Handle announce reset.
	 */
	BISFormManager.prototype.onAnnounceReset = function( event ) {
		var form = event.data.bisForm;
		form.$formProductInput.val( form.product_id ).trigger( 'change' );
		form.$form.addClass( 'hidden' );
	};

	// Initialize the form manager on DOM ready.
	$( function() {
		$( '.variations_form' ).each( function() {
			new BISFormManager( $( this ) );
		});
	});

})( jQuery, document );