/**
 * External dependencies
 */
import { AnyInterpreter, Sender, StateValue, EventObject } from 'xstate';

export type CustomizeStoreComponent = ( props: {
	parentMachine: AnyInterpreter;
	sendEvent: Sender< EventObject >;
	context: customizeStoreStateMachineContext;
	currentState: StateValue;
} ) => React.ReactElement | null;

export type CustomizeStoreComponentMeta = {
	component: CustomizeStoreComponent;
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
		activeTheme: string;
		customizeStoreTaskCompleted: boolean;
	};
	isFontLibraryAvailable: boolean | null;
	isPTKPatternsAPIAvailable: boolean | null;
	activeThemeHasMods: boolean | undefined;
};
