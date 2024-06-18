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

const fetchIsComingSoonShown = async (): Promise< boolean > => {
	const isComingSoonPage = ( await apiFetch( {
		path:
			'/wc-admin/launch-your-store/is-coming-soon-page-shown?t=' +
			Date.now(),
		method: 'GET',
	} ) ) as { is_coming_soon_shown: boolean };

	return isComingSoonPage.is_coming_soon_shown;
};

export const fetchCongratsData = fromPromise( async () => {
	const [ isComingSoonShown, surveyCompleted, tasklists, activePlugins ] =
		await Promise.all( [
			fetchIsComingSoonShown(),
			fetchSurveyCompletedOption(),
			resolveSelect( ONBOARDING_STORE_NAME ).getTaskListsByIds( [
				'setup',
				'extended',
			] ),
			resolveSelect( PLUGINS_STORE_NAME ).getActivePlugins(),
		] );
	return {
		isComingSoonShown,
		surveyCompleted: surveyCompleted as string | null,
		tasklists,
		activePlugins,
	};
} );
