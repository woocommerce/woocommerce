jQuery(function( $ ) {

	/**
	 * Coupon actions
	 */
	var wc_beta_tester_version_information = {

		/**
		 * Initialize variations actions
		 */
		init: function() {
			$( '#wp-admin-bar-show-version-info' )
				.on( 'click', this.showModal );
		},

		/**
		 * Show/hide fields by coupon type options
		 */
		showModal: function( event ) {
			event.preventDefault();

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
