/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { dispatch, resolveSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ALLOW_TRACKING_OPTION_NAME } from './constants';

const CUSTOMER_EFFORT_SCORE_EXIT_PAGE_KEY = 'customer-effort-score-exit-page';

let allowTracking = false;
resolveSelect( OPTIONS_STORE_NAME )
	.getOption( ALLOW_TRACKING_OPTION_NAME )
	.then( ( trackingOption ) => {
		allowTracking = trackingOption === 'yes';
	} );

export const getExitPageData = () => {
	if ( ! window.localStorage ) {
		return [];
	}

	const items = window.localStorage.getItem(
		CUSTOMER_EFFORT_SCORE_EXIT_PAGE_KEY
	);
	const parsedJSONItems = items ? JSON.parse( items ) : [];
	const arrayItems = Array.isArray( parsedJSONItems ) ? parsedJSONItems : [];

	return arrayItems;
};

export const addExitPage = ( pageName: string ) => {
	if ( ! window.localStorage ) {
		return;
	}

	let items = getExitPageData();

	items = items.filter( ( item ) => item !== pageName );
	items.push( pageName );
	items = items.slice( -10 ); // Upper limit.

	window.localStorage.setItem(
		CUSTOMER_EFFORT_SCORE_EXIT_PAGE_KEY,
		JSON.stringify( items )
	);
};

export const removeExitPage = ( pageName: string ) => {
	if ( ! window.localStorage ) {
		return;
	}

	let items = getExitPageData();

	items = items.filter( ( item ) => item !== pageName );
	items = items.slice( -10 ); // Upper limit.

	window.localStorage.setItem(
		CUSTOMER_EFFORT_SCORE_EXIT_PAGE_KEY,
		JSON.stringify( items )
	);
};

const eventListeners: Record< string, ( event: any ) => void > = {};

export const addCustomerEffortScoreExitPageListener = (
	pageId: string,
	hasUnsavedChanges: () => boolean
) => {
	eventListeners[ pageId ] = ( event ) => {
		console.log( event );
		if ( hasUnsavedChanges() && allowTracking ) {
			addExitPage( pageId );
		}
	};
	window.addEventListener( 'unload', eventListeners[ pageId ] );
};

export const removeCustomerEffortScoreExitPageListener = ( pageId: string ) => {
	if ( eventListeners[ pageId ] ) {
		window.removeEventListener( 'unload', eventListeners[ pageId ], {
			capture: true,
		} );
	}
};

function getExitPageCESCopy( pageId: string ): string {
	switch ( pageId ) {
		case 'edit-product':
		case 'new-product':
		case 'edit-product-mvp':
		case 'new-product-mvp':
			return __(
				'We noticed you started editing a product, then left. How was it? Your feedback will help create a better experience for thousands of merchants like you.',
				'woocommerce'
			);
		default:
			return '';
	}
}

export function triggerExitPageCesSurvey() {
	const exitPageItems: string[] = getExitPageData();
	if ( exitPageItems && exitPageItems.length > 0 ) {
		const copy = getExitPageCESCopy( exitPageItems[ 0 ] );
		if ( copy && copy.length > 0 ) {
			dispatch( 'wc/customer-effort-score' ).addCesSurvey(
				'exit_' + exitPageItems[ 0 ].replaceAll( '-', '_' ),
				copy,
				window.pagenow,
				window.adminpage
			);
		}
		removeExitPage( exitPageItems[ 0 ] );
	}
}
