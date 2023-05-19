/**
 * External dependencies
 */
import { render, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WriteItForMeButtonContainer } from './product-description';

import './index.scss';

const buttonRoot = document.getElementById(
	'woocommerce-ai-app-product-gpt-button'
);

const renderRoot = () => {
	if ( ! buttonRoot ) {
		return;
	}

	if ( createRoot ) {
		createRoot( buttonRoot ).render( <WriteItForMeButtonContainer /> );
	} else {
		render( <WriteItForMeButtonContainer />, buttonRoot );
	}
};

renderRoot();
