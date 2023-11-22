/**
 * External dependencies
 */
import { assign, DoneInvokeEvent } from 'xstate';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { customizeStoreStateMachineEvents } from '..';
import {
	customizeStoreStateMachineContext,
	RecommendThemesAPIResponse,
} from '../types';
import { events } from './';
import { OpenAiStatus } from './types';

export const assignThemeData = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents // this is actually the wrong type for the event but I still don't know how to type this properly
>( {
	intro: ( context, event ) => {
		const apiResponse = (
			event as DoneInvokeEvent< {
				themeData: RecommendThemesAPIResponse;
			} >
		 ).data.themeData;

		// type coercion workaround for now
		return {
			...context.intro,
			themeData: apiResponse,
		};
	},
} );

export const recordTracksDesignWithAIClicked = () => {
	recordEvent( 'customize_your_store_intro_design_with_ai_click' );
};

export const recordTracksThemeSelected = (
	_context: customizeStoreStateMachineContext,
	event: Extract<
		events,
		{ type: 'SELECTED_ACTIVE_THEME' | 'SELECTED_NEW_THEME' }
	>
) => {
	recordEvent( 'customize_your_store_intro_theme_select', {
		theme: event.payload.theme,
		is_active: event.type === 'SELECTED_ACTIVE_THEME' ? 'yes' : 'no',
	} );
};

export const recordTracksBrowseAllThemesClicked = () => {
	recordEvent( 'customize_your_store_intro_browse_all_themes_click' );
};

export const assignActiveThemeHasMods = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents // this is actually the wrong type for the event but I still don't know how to type this properly
>( {
	intro: ( context, event ) => {
		const activeThemeHasMods = (
			event as DoneInvokeEvent< { activeThemeHasMods: boolean } >
		 ).data.activeThemeHasMods;
		// type coercion workaround for now
		return { ...context.intro, activeThemeHasMods };
	},
} );

export const assignCustomizeStoreCompleted = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents
>( {
	intro: ( context, event ) => {
		const customizeStoreTaskCompleted = (
			event as DoneInvokeEvent< {
				customizeStoreTaskCompleted: boolean;
			} >
		 ).data.customizeStoreTaskCompleted;
		return { ...context.intro, customizeStoreTaskCompleted };
	},
} );

export const assignFetchIntroDataError = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents // this is actually the wrong type for the event but I still don't know how to type this properly
>( {
	intro: ( context ) => {
		return { ...context.intro, hasErrors: true };
	},
} );

export const assignCurrentThemeIsAiGenerated = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents
>( {
	intro: ( context, event ) => {
		const currentThemeIsAiGenerated = (
			event as DoneInvokeEvent< {
				currentThemeIsAiGenerated: boolean;
			} >
		 ).data.currentThemeIsAiGenerated;
		return { ...context.intro, currentThemeIsAiGenerated };
	},
} );

export const assignAIAvailability = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents // this is actually the wrong type for the event but I still don't know how to type this properly
>( {
	isAiAvailable: ( _context, _event ) => {
		const status = ( _event as DoneInvokeEvent< OpenAiStatus > ).data.status
			.indicator;
		return status !== 'critical' && status !== 'major';
	},
} );

export const assignAiUnavailable = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents // this is actually the wrong type for the event but I still don't know how to type this properly
>( {
	isAiAvailable: false,
} );
