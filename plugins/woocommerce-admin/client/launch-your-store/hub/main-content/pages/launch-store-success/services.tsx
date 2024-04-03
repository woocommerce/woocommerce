/**
 * External dependencies
 */
import { ONBOARDING_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import { fromPromise } from 'xstate5';

export const fetchSurveyCompletedOption = fromPromise( async () => {
	const result = await resolveSelect( OPTIONS_STORE_NAME ).getOption(
		'woocommerce_admin_launch_your_store_survey_completed'
	);
	return result as string | null;
} );

export const getAllTasklists = fromPromise( () =>
	resolveSelect( ONBOARDING_STORE_NAME ).getTaskListsByIds( [
		'setup',
		'extended',
	] )
);
