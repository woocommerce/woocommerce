/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import {
	TaskType,
	TaskListType,
	OnboardingState,
	ExtensionList,
	ProfileItems,
	GetJetpackAuthUrlResponse,
	CoreProfilerCompletedSteps,
} from './types';
import { WPDataSelectors } from '../types';
import { Plugin } from '../plugins/types';

export const getFreeExtensions = (
	state: OnboardingState
): ExtensionList[] => {
	return state.freeExtensions || [];
};

export const getProfileItems = (
	state: OnboardingState
): ProfileItems | Record< string, never > => {
	return state.profileItems || {};
};

export const getProfileProgress = (
	state: OnboardingState
): Partial< CoreProfilerCompletedSteps > => {
	return state.profileProgress || {};
};

export const getTaskLists = createSelector(
	( state: OnboardingState ): TaskListType[] => {
		return Object.values( state.taskLists );
	},
	( state: OnboardingState ) => [ state.taskLists ]
);

export const getTaskListsByIds = createSelector(
	( state: OnboardingState, ids: string[] ): TaskListType[] => {
		return ids.map( ( id ) => state.taskLists[ id ] );
	},
	( state: OnboardingState, ids: string[] ) =>
		ids.map( ( id ) => state.taskLists[ id ] )
);

export const getTaskList = (
	state: OnboardingState,
	selector: string
): TaskListType | undefined => {
	return state.taskLists[ selector ];
};

export const getTask = (
	state: OnboardingState,
	selector: string
): TaskType | undefined => {
	return Object.keys( state.taskLists ).reduce(
		( value: TaskType | undefined, listId: string ) => {
			return (
				value ||
				state.taskLists[ listId ].tasks.find(
					( task ) => task.id === selector
				)
			);
		},
		undefined
	);
};

export const getPaymentGatewaySuggestions = (
	state: OnboardingState
): Plugin[] => {
	return state.paymentMethods || [];
};

export const getOnboardingError = (
	state: OnboardingState,
	selector: string
): unknown | false => {
	return state.errors[ selector ] || false;
};

export const isOnboardingRequesting = (
	state: OnboardingState,
	selector: string
): boolean => {
	return state.requesting[ selector ] || false;
};

export const getEmailPrefill = ( state: OnboardingState ): string => {
	return state.emailPrefill || '';
};

export const getProductTypes = ( state: OnboardingState ) => {
	return state.productTypes || {};
};

export const getJetpackAuthUrl = (
	state: OnboardingState,
	query: {
		redirectUrl: string;
		from?: string;
	}
): GetJetpackAuthUrlResponse => {
	return state.jetpackAuthUrls[ query.redirectUrl ] || '';
};

export const getCoreProfilerCompletedSteps = createSelector(
	( state: OnboardingState ) => {
		return state.profileProgress || {};
	},
	( state: OnboardingState ) => [ state.profileProgress ]
);

export const getMostRecentCoreProfilerStep = createSelector(
	( state: OnboardingState ) => {
		const completedSteps = state.profileProgress || {};

		return (
			Object.entries(
				completedSteps as Record<
					string,
					CoreProfilerCompletedSteps[ keyof CoreProfilerCompletedSteps ]
				>
			).sort( ( a, b ) => {
				const dateA = new Date( a[ 1 ].completed_at );
				const dateB = new Date( b[ 1 ].completed_at );
				return dateB.getTime() - dateA.getTime();
			} )[ 0 ]?.[ 0 ] || null
		);
	},
	( state: OnboardingState ) => [ state.profileProgress ]
);

export type OnboardingSelectors = {
	getProfileItems: () => ReturnType< typeof getProfileItems >;
	getProfileProgress: () => ReturnType< typeof getProfileProgress >;
	getPaymentGatewaySuggestions: () => ReturnType<
		typeof getPaymentGatewaySuggestions
	>;
	getOnboardingError: () => ReturnType< typeof getOnboardingError >;
	isOnboardingRequesting: () => ReturnType< typeof isOnboardingRequesting >;
	getTaskListsByIds: (
		ids: string[]
	) => ReturnType< typeof getTaskListsByIds >;
	getTaskLists: () => ReturnType< typeof getTaskLists >;
	getTaskList: ( id: string ) => ReturnType< typeof getTaskList >;
	getTask: ( id: string ) => ReturnType< typeof getTask >;
	getFreeExtensions: () => ReturnType< typeof getFreeExtensions >;
	getCoreProfilerCompletedSteps: () => ReturnType<
		typeof getCoreProfilerCompletedSteps
	>;
	getMostRecentCoreProfilerStep: () => ReturnType<
		typeof getMostRecentCoreProfilerStep
	>;
} & WPDataSelectors;
