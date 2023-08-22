/**
 * External dependencies
 */
import { assign, DoneInvokeEvent } from 'xstate';

/**
 * Internal dependencies
 */
import { customizeStoreStateMachineEvents } from '..';

/**
 * Internal dependencies
 */
import { customizeStoreStateMachineContext } from '../types';
import { ThemeCard } from './theme-cards';

export const assignThemeCards = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents // this is actually the wrong type for the event but I still don't know how to type this properly
>( {
	intro: ( context, event ) => {
		const themeCards = ( event as DoneInvokeEvent< ThemeCard[] > ).data; // type coercion workaround for now
		return { ...context.intro, themeCards };
	},
} );
