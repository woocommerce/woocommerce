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

/**
 * Gets the list of exited pages from Localstorage.
 */
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

/**
 * Adds the page to the exit page list in Localstorage.
 *
 * @param {string} pageId of page exited early.
 */
export const addExitPage = ( pageId: string ) => {
	if ( ! window.localStorage ) {
		return;
	}

	let items = getExitPageData();

	if ( ! items.find( ( pageExitedId ) => pageExitedId === pageId ) ) {
		items.push( pageId );
	}
	items = items.slice( -10 ); // Upper limit.

	window.localStorage.setItem(
		CUSTOMER_EFFORT_SCORE_EXIT_PAGE_KEY,
		JSON.stringify( items )
	);
};

/**
 * Removes the passed in page id from the list in Localstorage.
 *
 * @param {string} pageId of page to be removed.
 */
export const removeExitPage = ( pageId: string ) => {
	if ( ! window.localStorage ) {
		return;
	}

	let items = getExitPageData();

	items = items.filter( ( pageExitedId ) => pageExitedId !== pageId );
	items = items.slice( -10 ); // Upper limit.

	window.localStorage.setItem(
		CUSTOMER_EFFORT_SCORE_EXIT_PAGE_KEY,
		JSON.stringify( items )
	);
};

const eventListeners: Record< string, ( event: BeforeUnloadEvent ) => void > =
	{};

/**
 * Adds unload event listener to add pageId to exit page list incase there were unsaved changes.
 *
 * @param {string}   pageId the page id of the page being exited early.
 * @param {Function} hasUnsavedChanges callback to check if the page had unsaved changes.
 */
export const addCustomerEffortScoreExitPageListener = (
	pageId: string,
	hasUnsavedChanges: () => boolean
) => {
	eventListeners[ pageId ] = ( event ) => {
		if ( hasUnsavedChanges() && allowTracking ) {
			addExitPage( pageId );
		}
	};
	window.addEventListener( 'unload', eventListeners[ pageId ] );
};

/**
 * Removes the unload exit page listener.
 *
 * @param {string} pageId the page id to remove the listener from.
 */
export const removeCustomerEffortScoreExitPageListener = ( pageId: string ) => {
	if ( eventListeners[ pageId ] ) {
		window.removeEventListener( 'unload', eventListeners[ pageId ], {
			capture: true,
		} );
	}
};

/**
 * Returns the exit page copy of the passed in pageId.
 *
 * @param {string} pageId page id.
 */
function getExitPageCESCopy( pageId: string ): {
	action: string;
	title: string;
	firstQuestion: string;
	secondQuestion: string;
	noticeLabel?: string;
	description?: string;
} | null {
	switch ( pageId ) {
		case 'product_edit_view':
		case 'editing_new_product':
			return {
				action:
					pageId === 'editing_new_product' ? 'new_product' : pageId,
				noticeLabel: __(
					'How is your experience with editing products?',
					'woocommerce'
				),
				title: __(
					"How's your experience with editing products?",
					'woocommerce'
				),
				description: __(
					'We noticed you started editing a product, then left. How was it? Your feedback will help create a better experience for thousands of merchants like you.',
					'woocommerce'
				),
				firstQuestion: __(
					'The product editing screen is easy to use',
					'woocommerce'
				),
				secondQuestion: __(
					"The product editing screen's functionality meets my needs",
					'woocommerce'
				),
			};
		case 'product_add_view':
		case 'new_product':
			return {
				action: pageId,
				noticeLabel: __(
					'How is your experience with creating products?',
					'woocommerce'
				),
				title: __(
					'How is your experience with creating products?',
					'woocommerce'
				),
				description: __(
					'We noticed you started creating a product, then left. How was it? Your feedback will help create a better experience for thousands of merchants like you.',
					'woocommerce'
				),
				firstQuestion: __(
					'The product creation screen is easy to use',
					'woocommerce'
				),
				secondQuestion: __(
					"The product creation screen's functionality meets my needs",
					'woocommerce'
				),
			};
		default:
			return null;
	}
}

/**
 * Checks the exit page list and triggers a CES survey for the first item in the list.
 */
export function triggerExitPageCesSurvey() {
	const exitPageItems: string[] = getExitPageData();
	if ( exitPageItems && exitPageItems.length > 0 ) {
		const copy = getExitPageCESCopy( exitPageItems[ 0 ] );
		if ( copy && copy.title.length > 0 ) {
			dispatch( 'wc/customer-effort-score' ).addCesSurvey( {
				...copy,
				pageNow: window.pagenow,
				adminPage: window.adminpage,
				props: {
					ces_location: 'outside',
				},
			} );
		}
		removeExitPage( exitPageItems[ 0 ] );
	}
}
