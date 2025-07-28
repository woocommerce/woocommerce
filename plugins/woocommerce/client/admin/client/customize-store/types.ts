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

export type customizeStoreStateMachineContext = {
	themeConfiguration: Record< string, unknown >; // placeholder for theme configuration until we know what it looks like
	intro: {
		hasErrors: boolean;
		errorStatus: number | undefined;
		themeData: RecommendThemesAPIResponse;
		activeTheme: string;
		customizeStoreTaskCompleted: boolean;
	};
	isFontLibraryAvailable: boolean | null;
	isPTKPatternsAPIAvailable: boolean | null;
	activeThemeHasMods: boolean | undefined;
};
