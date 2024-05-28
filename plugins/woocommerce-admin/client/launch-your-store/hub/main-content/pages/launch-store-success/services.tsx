/**
 * External dependencies
 */
import { ONBOARDING_STORE_NAME, PLUGINS_STORE_NAME } from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import { fromPromise } from 'xstate5';
import apiFetch from '@wordpress/api-fetch';

export const fetchCongratsData = fromPromise( async () => {
	const [ surveyCompleted, tasklists, activePlugins ] = await Promise.all( [
		await apiFetch( {
			path: `/wc-admin/launch-your-store/survey-completed`,
		} ),

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
