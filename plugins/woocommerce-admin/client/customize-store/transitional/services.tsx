/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

export const fetchSurveyCompletedOption = async () =>
	await apiFetch( {
		path: `/wc-admin/launch-your-store/survey-completed`,
	} );
