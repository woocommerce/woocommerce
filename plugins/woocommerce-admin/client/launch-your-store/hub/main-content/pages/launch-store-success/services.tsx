/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import { fromPromise } from 'xstate5';

export const fetchSurveyCompletedOption = fromPromise( () =>
	resolveSelect( OPTIONS_STORE_NAME ).getOption(
		'woocommerce_admin_launch_your_store_survey_completed'
	)
);
