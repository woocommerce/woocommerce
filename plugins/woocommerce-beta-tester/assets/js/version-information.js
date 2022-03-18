/**
 * Handles version information modal.
 *
 * @package WooCommerceBetaTester\JS
 */

jQuery(function( $ ) {

	/**
	 * Version information
	 */
	var wc_beta_tester_version_information = {

		/**
		 * Initialize Version Information click
		 */
		init: function() {
			$( '#wp-admin-bar-show-version-info' )
				.on( 'click', this.showModal );
		},

		/**
		 * Handler for showing/hiding version information modal
		 */
		showModal: function( event ) {
			event.preventDefault();

			// Prevent multiple modals.
			if ( 0 < $( '.wc-backbone-modal-beta-tester-version-info' ).length ) {
				return;
			}

			$( this ).WCBackboneModal({
				template: 'wc-beta-tester-version-info',
				variable: {
					version: wc_beta_tester_version_info_params.version,
					description: wc_beta_tester_version_info_params.description,
				},
			});
		}
	};

	wc_beta_tester_version_information.init();
});
