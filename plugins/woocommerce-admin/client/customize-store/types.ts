/**
 * External dependencies
 */
import { Sender } from 'xstate';

/**
 * Internal dependencies
 */
import { customizeStoreStateMachineEvents } from '.';
import { ThemeCard } from './intro/theme-cards';

export type CustomizeStoreComponent = ( props: {
	sendEvent: Sender< customizeStoreStateMachineEvents >;
	context: customizeStoreStateMachineContext;
} ) => React.ReactElement | null;

export type CustomizeStoreComponentMeta = {
	component: CustomizeStoreComponent;
};

export type customizeStoreStateMachineContext = {
	themeConfiguration: Record< string, unknown >; // placeholder for theme configuration until we know what it looks like
	intro: {
		themeCards: ThemeCard[];
		activeTheme: string;
	};
};
