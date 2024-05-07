/**
 * External dependencies
 */
import { DoneActorEvent } from 'xstate5';
import { TaskListType } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type { MainContentMachineContext } from '../../../main-content/xstate';

export const assignCompleteSurvey = {
	congratsScreen: ( { context }: { context: MainContentMachineContext } ) => {
		apiFetch( {
			path: '/wc-admin/launch-your-store/update-survey-status',
			data: {
				status: 'yes',
			},
			method: 'POST',
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
