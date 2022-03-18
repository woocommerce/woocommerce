/**
 * Handles the version picker form.
 *
 * @package WooCommerceBetaTester\JS
 */

jQuery(function( $ ) {

	/**
	 * Version picker
	 */
	var wc_beta_tester_version_picker = {

		/**
		 * Initialize Version Information click
		 */
		init: function() {
			instance = this;
			instance.new_version = undefined;

			$( '#wcbt-modal-version-switch-confirm' )
				.on( 'click', this.showConfirmVersionSwitchModal );
			$( 'input[type=radio][name=wcbt_switch_to_version]' ).change( function() {
				if ( $( this ).is( ':checked' ) ) {
					instance.new_version = $( this ).val();
				}
			} ).trigger( 'change' );
		},

		/**
		 * Handler for showing/hiding version switch modal
		 */
		showConfirmVersionSwitchModal: function( event ) {
			event.preventDefault();

			if ( ! instance.new_version ) {
				alert( wc_beta_tester_version_picker_params.i18n_pick_version );
			} else {
				$( this ).WCBackboneModal({
					template: 'wcbt-version-switch-confirm',
					variable: {
						new_version: instance.new_version,
					},
				});

				$( '#wcbt-submit-version-switch' )
					.on( 'click', instance.submitSwitchVersionForm );
			}
		},

		/**
		 * Submit form to switch version of WooCommerce.
		 */
		submitSwitchVersionForm: function( event ) {
			event.preventDefault();

			$( 'form[name=wcbt-select-version]' ).get( 0 ).submit();
		},
	};

	wc_beta_tester_version_picker.init();

});
