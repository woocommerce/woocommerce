/*global wc_users_params */
jQuery( function ( $ ) {

	/**
	 * Users country and state fields
	 */
	var wc_users_fields = {
		states: null,
		init: function() {
			if ( typeof wc_users_params.countries !== 'undefined' ) {
				/* State/Country select boxes */
				this.states = $.parseJSON( wc_users_params.countries.replace( /&quot;/g, '"' ) );
			}

			$( '.js_field-country' ).selectWoo().change( this.change_country );
			$( '.js_field-country' ).trigger( 'change', [ true ] );
			$( document.body ).on( 'change', 'select.js_field-state', this.change_state );

			$( document.body ).on( 'click', 'button.js_copy-billing', this.copy_billing );
		},

		change_country: function( e, stickValue ) {
			// Check for stickValue before using it
			if ( typeof stickValue === 'undefined' ) {
				stickValue = false;
			}

			// Prevent if we don't have the metabox data
			if ( wc_users_fields.states === null ) {
				return;
			}

			var $this = $( this ),
				country = $this.val(),
				$state = $this.parents( '.form-table' ).find( ':input.js_field-state' ),
				$parent = $state.parent(),
				input_name = $state.attr( 'name' ),
				input_id = $state.attr( 'id' ),
				value = $this.data( 'woocommerce.stickState-' + country ) ? $this.data( 'woocommerce.stickState-' + country ) : $state.val();

			if ( stickValue ){
				$this.data( 'woocommerce.stickState-' + country, value );
			}

			// Remove the previous DOM element
			$parent.show().find( '.select2-container' ).remove();

			if ( ! $.isEmptyObject( wc_users_fields.states[ country ] ) ) {
				var $states_select = $( '<select name="' + input_name + '" id="' + input_id + '" class="js_field-state" style="width: 25em;"></select>' ),
					state = wc_users_fields.states[ country ];

				$states_select.append( $( '<option value="">' + wc_users_params.i18n_select_state_text + '</option>' ) );

				$.each( state, function( index ) {
					$states_select.append( $( '<option value="' + index + '">' + state[ index ] + '</option>' ) );
				} );

				$states_select.val( value );

				$state.replaceWith( $states_select );

				$states_select.show().selectWoo().hide().change();
			} else {
				$state.replaceWith( '<input type="text" class="js_field-state" name="' + input_name + '" id="' + input_id + '" value="' + value + '" />' );
			}

			// This event has a typo - deprecated in 2.5.0
			$( document.body ).trigger( 'contry-change.woocommerce', [country, $( this ).closest( 'div' )] );
			$( document.body ).trigger( 'country-change.woocommerce', [country, $( this ).closest( 'div' )] );
		},

		change_state: function() {
			// Here we will find if state value on a select has changed and stick it to the country data
			var $this = $( this ),
				state    = $this.val(),
				$country = $this.parents( '.form-table' ).find( ':input.js_field-country' ),
				country  = $country.val();

			$country.data( 'woocommerce.stickState-' + country, state );
		},

		copy_billing: function( event ) {
			event.preventDefault();

			$( '#fieldset-billing' ).find( 'input, select' ).each( function( i, el ) {
				// The address keys match up, except for the prefix
				var shipName = el.name.replace( /^billing_/, 'shipping_' );
				// Swap prefix, then check if there are any elements
				var shipEl = $( '[name="' + shipName + '"]' );
				// No corresponding shipping field, skip this item
				if ( ! shipEl.length ) {
					return;
				}
				// Found a matching shipping element, update the value
				shipEl.val( el.value ).trigger( 'change' );
			} );
		}
	};

	wc_users_fields.init();

});
