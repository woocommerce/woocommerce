/**
 * Woo Fraud Protection - Blackbox Initialization
 *
 * Configures the Blackbox JS SDK with the site's API key and blog ID.
 * Loaded on checkout, pay-for-order, and add-payment-method pages.
 */
( function () {
	'use strict';

	var config = window.wcBlackboxConfig;
	if ( ! config ) {
		return;
	}

	if ( ! window.Blackbox || ! window.Blackbox.configure ) {
		return;
	}

	window.Blackbox.configure( {
		apiKey: config.apiKey,
		blogId: config.blogId,
	} );
} )();
