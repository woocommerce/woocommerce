/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TYPES from './action-types';

/**
 * Initialize the state
 *
 * @param {Object} queue initial queue
 */
export function setCesSurveyQueue( queue ) {
	return {
		type: TYPES.SET_CES_SURVEY_QUEUE,
		queue,
	};
}

/**
 * Add a new CES track to the state.
 *
 * @param {string} action        action name for the survey
 * @param {string} label         label for the snackback
 * @param {string} pageNow       value of window.pagenow
 * @param {string} adminPage     value of window.adminpage
 * @param {string} onsubmitLabel label for the snackback onsubmit
 * @param {Object} props         object for optional props
 */
export function addCesSurvey(
	action,
	label,
	pageNow = window.pagenow,
	adminPage = window.adminpage,
	onsubmitLabel = undefined,
	props = {}
) {
	return {
		type: TYPES.ADD_CES_SURVEY,
		action,
		label,
		pageNow,
		adminPage,
		onsubmit_label: onsubmitLabel,
		props,
	};
}

/**
 * Add a new CES survey track for the pages in Analytics menu
 */
export function addCesSurveyForAnalytics() {
	return addCesSurvey(
		'analytics_filtered',
		__( 'How easy was it to filter your store analytics?', 'woocommerce' ),
		'woocommerce_page_wc-admin',
		'woocommerce_page_wc-admin'
	);
}

/**
 * Add a new CES survey track on searching customers.
 */
export function addCesSurveyForCustomerSearch() {
	return addCesSurvey(
		'ces_search',
		__( 'How easy was it to use search?', 'woocommerce' ),
		'woocommerce_page_wc-admin',
		'woocommerce_page_wc-admin',
		undefined,
		{
			search_area: 'customer',
		}
	);
}
