/**
 * External dependencies
 */
import { ONBOARDING_STORE_NAME, PLUGINS_STORE_NAME } from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import { fromPromise } from 'xstate5';
import apiFetch from '@wordpress/api-fetch';

type SurveyCompletedResponse = string | null;
let cachedSurveyCompleted: SurveyCompletedResponse = null;
const fetchSurveyCompletedOption =
	async (): Promise< SurveyCompletedResponse > => {
		if ( cachedSurveyCompleted !== null ) {
			return cachedSurveyCompleted;
		}
		const response = await apiFetch( {
			path: `/wc-admin/launch-your-store/survey-completed`,
		} );
		cachedSurveyCompleted = response as SurveyCompletedResponse;
		return cachedSurveyCompleted;
	};

export const fetchCongratsData = fromPromise( async () => {
	const [ surveyCompleted, tasklists, activePlugins ] = await Promise.all( [
		fetchSurveyCompletedOption(),
		resolveSelect( ONBOARDING_STORE_NAME ).getTaskListsByIds( [
			'setup',
			'extended',
		] ),
		resolveSelect( PLUGINS_STORE_NAME ).getActivePlugins(),
	] );
	return {
		surveyCompleted: surveyCompleted as string | null,
		tasklists,
		activePlugins,
	};
} );
