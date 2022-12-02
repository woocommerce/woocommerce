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
 * @param {Object} args                All arguments.
 * @param {string} args.action         action name for the survey
 * @param {string} args.title          title for the snackback
 * @param {string} args.firstQuestion  first question for modal survey
 * @param {string} args.secondQuestion second question for modal survey
 * @param {string} args.pageNow        value of window.pagenow
 * @param {string} args.adminPage      value of window.adminpage
 * @param {string} args.onsubmitLabel  label for the snackback onsubmit
 * @param {Object} args.props          object for optional props
 */
export function addCesSurvey( {
	action,
	title,
	firstQuestion,
	secondQuestion,
	pageNow = window.pagenow,
	adminPage = window.adminpage,
	onsubmitLabel = undefined,
	props = {},
} ) {
	return {
		type: TYPES.ADD_CES_SURVEY,
		action,
		title,
		firstQuestion,
		secondQuestion,
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
	return addCesSurvey( {
		action: 'analytics_filtered',
		title: __(
			'How easy was it to filter your store analytics?',
			'woocommerce'
		),
		firstQuestion: __(
			'The filters in the analytics screen are easy to use.',
			'woocommerce'
		),
		secondQuestion: __(
			`The filters' functionality meets my needs.`,
			'woocommerce'
		),
		pageNow: 'woocommerce_page_wc-admin',
		adminPage: 'woocommerce_page_wc-admin',
	} );
}

/**
 * Add a new CES survey track on searching customers.
 */
export function addCesSurveyForCustomerSearch() {
	return addCesSurvey( {
		action: 'ces_search',
		title: __( 'How easy was it to use search?', 'woocommerce' ),
		firstQuestion: __(
			'The search feature in WooCommerce is easy to use.',
			'woocommerce'
		),
		secondQuestion: __(
			`The search's functionality meets my needs.`,
			'woocommerce'
		),
		pageNow: 'woocommerce_page_wc-admin',
		adminPage: 'woocommerce_page_wc-admin',
		onsubmit_label: undefined,
		props: {
			search_area: 'customer',
		},
	} );
}
