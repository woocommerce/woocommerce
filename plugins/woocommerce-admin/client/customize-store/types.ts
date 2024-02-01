/**
 * External dependencies
 */
import { AnyInterpreter, Sender, StateValue } from 'xstate';

/**
 * Internal dependencies
 */
import { customizeStoreStateMachineEvents } from '.';
import { ThemeCard } from './intro/types';

export type CustomizeStoreComponent = ( props: {
	parentMachine: AnyInterpreter;
	sendEvent: Sender< customizeStoreStateMachineEvents >;
	context: customizeStoreStateMachineContext;
	currentState: StateValue;
} ) => React.ReactElement | null;

export type CustomizeStoreComponentMeta = {
	component: CustomizeStoreComponent;
};

export type RecommendThemesAPIResponse = {
	themes: ThemeCard[];
	_links: {
		browse_all?: {
			href: string;
		};
	};
};

export type aiStatusResponse = {
	status: {
		indicator: 'major' | 'critical' | 'ok';
	};
};

export enum FlowType {
	// Flow when the AI is online.
	AIOnline = 'AIOnline',
	// Flow when the AI is offline because the AI endpoints are down.
	AIOffline = 'AIOffline',
	// Flow when the AI isn't available in the site. E.g. the site is not on a paid plan.
	noAI = 'noAI',
}

export type customizeStoreStateMachineContext = {
	themeConfiguration: Record< string, unknown >; // placeholder for theme configuration until we know what it looks like
	intro: {
		hasErrors: boolean;
		themeData: RecommendThemesAPIResponse;
		activeTheme: string;
		activeThemeHasMods: boolean;
		customizeStoreTaskCompleted: boolean;
		currentThemeIsAiGenerated: boolean;
	};
	transitionalScreen: {
		hasCompleteSurvey: boolean;
	};
	flowType: FlowType;
	isFontLibraryAvailable: boolean | null;
};
