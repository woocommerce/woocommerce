/**
 * External dependencies
 */
import { DoneActorEvent } from 'xstate5';
import { dispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME, TaskListType } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import type { MainContentMachineContext } from '../../../main-content/xstate';

export const assignCompleteSurvey = {
	congratsScreen: ( { context }: { context: MainContentMachineContext } ) => {
		dispatch( OPTIONS_STORE_NAME ).updateOptions( {
			woocommerce_admin_launch_your_store_survey_completed: 'yes',
		} );

		return {
			...context.congratsScreen,
			hasCompleteSurvey: true,
		};
	},
};

export const assignCongratsData = {
	congratsScreen: ( {
		context,
		event,
	}: {
		context: MainContentMachineContext;
		event: DoneActorEvent< {
			surveyCompleted: string | null;
			tasklists: TaskListType[];
			activePlugins: string[];
		} >;
	} ) => {
		return {
			...context.congratsScreen,
			hasLoadedCongratsData: true,
			hasCompleteSurvey: event.output.surveyCompleted === 'yes',
			allTasklists: event.output.tasklists,
			activePlugins: event.output.activePlugins,
		};
	},
};
