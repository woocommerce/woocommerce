/**
 * External dependencies
 */
import {
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
} from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import { fromPromise } from 'xstate5';

export const fetchCongratsData = fromPromise( async () => {
	const [ surveyCompleted, tasklists, activePlugins ] = await Promise.all( [
		resolveSelect( OPTIONS_STORE_NAME ).getOption(
			'woocommerce_admin_launch_your_store_survey_completed'
		),
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
