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
	aiStatusResponse,
	customizeStoreStateMachineContext,
	FlowType,
	RecommendThemesAPIResponse,
} from '../types';
import { events } from './';
import { isIframe } from '~/customize-store/utils';

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

export const assignActiveTheme = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents
>( {
	intro: ( context, event ) => {
		const activeTheme = (
			event as DoneInvokeEvent< {
				activeTheme: string;
			} >
		 ).data.activeTheme;
		return { ...context.intro, activeTheme };
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

export const assignAiStatus = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents // this is actually the wrong type for the event but I still don't know how to type this properly
>( {
	flowType: ( _context, _event ) => {
		const indicator = ( _event as DoneInvokeEvent< aiStatusResponse > ).data
			.status.indicator;
		const status = indicator !== 'critical' && indicator !== 'major';
		// @ts-expect-error temp workaround;
		window.cys_aiOnline = status;

		recordEvent( 'customize_your_store_ai_status', {
			online: status ? 'yes' : 'no',
		} );

		return status ? FlowType.AIOnline : FlowType.AIOffline;
	},
} );

export const assignAiOffline = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents // this is actually the wrong type for the event but I still don't know how to type this properly
>( {
	flowType: () => {
		// @ts-expect-error temp workaround;
		window.cys_aiOnline = false;
		recordEvent( 'customize_your_store_ai_status', {
			online: 'no',
		} );

		return FlowType.AIOffline;
	},
} );

export const assignNoAI = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents // this is actually the wrong type for the event but I still don't know how to type this properly
>( {
	flowType: FlowType.noAI,
} );

export const assignNoAIFlowError = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents
>( {
	intro: ( context ) => {
		return { ...context.intro, hasErrors: true };
	},
} );

export const assignIsFontLibraryAvailable = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents
>( {
	isFontLibraryAvailable: ( context, event: unknown ) => {
		return (
			event as {
				payload: boolean;
			}
		 ).payload;
	},
} );

export const assignActiveThemeHasMods = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents
>( {
	activeThemeHasMods: ( context, event: unknown ) => {
		return (
			event as DoneInvokeEvent< {
				activeThemeHasMods: boolean;
			} >
		 ).data.activeThemeHasMods;
	},
} );

export const assignFlags = assign<
	customizeStoreStateMachineContext,
	customizeStoreStateMachineEvents
>( {
	activeThemeHasMods: () => {
		if ( ! isIframe( window ) ) {
			return window.__wcCustomizeStore.activeThemeHasMods;
		}

		return window.parent.__wcCustomizeStore.activeThemeHasMods;
	},
	isFontLibraryAvailable: () => {
		if ( ! isIframe( window ) ) {
			return window.__wcCustomizeStore.isFontLibraryAvailable;
		}
		const isFontLibraryAvailable =
			window.parent.__wcCustomizeStore.isFontLibraryAvailable || false;
		return isFontLibraryAvailable;
	},
} );
