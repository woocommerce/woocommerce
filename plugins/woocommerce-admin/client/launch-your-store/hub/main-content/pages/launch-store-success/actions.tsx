/**
 * External dependencies
 */
import { DoneActorEvent } from 'xstate5';
import { dispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import type { MainContentMachineContext } from '../../../main-content/xstate';

export const assignHasCompleteSurvey = {
	congratsScreen: ( {
		context,
		event,
	}: {
		context: MainContentMachineContext;
		event: DoneActorEvent;
	} ) => {
		return {
			...context.congratsScreen,
			hasLoadedCompleteOption: true,
			hasCompleteSurvey: event.output === 'yes',
		};
	},
};

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
