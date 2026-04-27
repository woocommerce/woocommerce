;(function ( $, document ) {

	var SYNC_INPUT_MARKER = 'data-wc-bis-variation-attr';

	/**
	 * Back in stock form manager.
	 *
	 * The BIS signup is rendered as a sibling of `.variations_form` (see
	 * ProductPageIntegration hooking `woocommerce_after_add_to_cart_form`), not
	 * nested inside it — HTML does not allow nested `<form>` elements, and this
	 * keeps submit-on-enter, screen-reader announcements, and the form role
	 * working natively.
	 *
	 * We synchronise the currently selected variation id and its attribute
	 * selections into hidden inputs on the BIS form whenever the user picks a
	 * variation, so that submitting the BIS form produces the same payload the
	 * legacy synthetic-submit path used to build at submit time.
	 *
	 * @param {jQuery} $variationsForm The `.variations_form` element.
	 */
	var BISFormManager = function( $variationsForm ) {

		// Properties.
		var self              = this;
		self.$variationsForm  = $variationsForm;
		self.product_id       = self.$variationsForm.data( 'product_id' );
		self.$form            = $( 'form.wc_bis_form[data-bis-product-id="' + self.product_id + '"]' );

		if ( ! self.$form.length ) {
			return;
		}

		self.$formProductInput = self.$form.find( 'input[name="wc_bis_product_id"]' );

		// Variation Events.
		self.$variationsForm.off( '.wc-bis-form' );
		self.$variationsForm.on( 'found_variation.wc-bis-form', { bisForm: self }, self.onFoundVariation );
		self.$variationsForm.on( 'show_variation.wc-bis-form', { bisForm: self }, self.onShowVariation );
		self.$variationsForm.on( 'reset_data.wc-bis-form', { bisForm: self }, self.onResetData );
	};

	/**
	 * Handle found variation — sync variation id and attribute values into the
	 * BIS form as hidden inputs so a native form submit carries them.
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
		form.syncVariationAttributes();
	};

	/**
	 * Handle show variation — toggle form visibility based on stock state.
	 *
	 * @param {Event} event The event object.
	 * @param {Object} variation The variation object.
	 */
	BISFormManager.prototype.onShowVariation = function( event, variation ) {
		var form    = event.data.bisForm;
		var visible = ! variation.is_in_stock || ! variation.is_purchasable;

		if ( ! variation.variation_is_active || ! variation.variation_is_visible ) {
			visible = false;
		}

		form.setVisible( visible );
	};

	/**
	 * Handle reset data — variation cleared, hide form and revert to the parent
	 * product id.
	 *
	 * @param {Event} event The event object.
	 */
	BISFormManager.prototype.onResetData = function( event ) {
		var form = event.data.bisForm;
		form.$formProductInput.val( form.product_id ).trigger( 'change' );
		form.clearVariationAttributes();
		form.setVisible( false );
	};

	/**
	 * Sync variation attribute select values into hidden inputs on the BIS
	 * form. Existing managed inputs are replaced so "any" attribute changes
	 * propagate on every variation change.
	 */
	BISFormManager.prototype.syncVariationAttributes = function() {
		var form      = this;
		var $selects  = form.$variationsForm.find( '.variations select' );

		form.clearVariationAttributes();

		$selects.each( function() {
			var $select = $( this );
			var name    = $select.attr( 'name' );
			if ( ! name ) {
				return;
			}

			form.$form.append(
				$( '<input/>', {
					type: 'hidden',
					name: name,
					value: $select.val() || ''
				} ).attr( SYNC_INPUT_MARKER, '' )
			);
		} );
	};

	/**
	 * Remove hidden inputs previously synced from the variations form so we
	 * don't leak stale attribute values when the user picks a different
	 * variation or clears the selection.
	 */
	BISFormManager.prototype.clearVariationAttributes = function() {
		this.$form.find( '[' + SYNC_INPUT_MARKER + ']' ).remove();
	};

	/**
	 * Toggle form visibility and expose an assistive-technology announcement
	 * when the form transitions into view.
	 *
	 * @param {boolean} visible Whether the form should be visible.
	 */
	BISFormManager.prototype.setVisible = function( visible ) {
		var wasHidden = this.$form.hasClass( 'hidden' );
		this.$form.toggleClass( 'hidden', ! visible );

		if ( visible && wasHidden ) {
			var $status       = this.$form.find( '.wc_bis_form__status' );
			var announcement  = this.$form.data( 'available-text' ) || '';
			if ( $status.length && announcement ) {
				// Re-set the text so the aria-live region re-announces even if
				// the same string is written twice in a row.
				$status.text( '' );
				window.setTimeout( function() {
					$status.text( announcement );
				}, 50 );
			}
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
