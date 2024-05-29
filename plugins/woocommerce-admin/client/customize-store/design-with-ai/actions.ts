/**
 * External dependencies
 */
import { assign, spawn } from 'xstate';
import { getQuery, updateQueryString } from '@woocommerce/navigation';
import { dispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	ColorPaletteResponse,
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents,
	LookAndToneCompletionResponse,
	Header,
	Footer,
	HomepageTemplate,
} from './types';
import { aiWizardClosedBeforeCompletionEvent } from './events';
import {
	businessInfoDescriptionCompleteEvent,
	lookAndFeelCompleteEvent,
	toneOfVoiceCompleteEvent,
} from './pages';
import { trackEvent } from '../tracking';

const assignStartLoadingTime = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	startLoadingTime: () => performance.now(),
} );

const assignBusinessInfoDescription = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	businessInfoDescription: ( _context, event: unknown ) => {
		return {
			descriptionText: ( event as businessInfoDescriptionCompleteEvent )
				.payload,
		};
	},
} );

const assignLookAndFeel = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	lookAndFeel: ( context, event: unknown ) => {
		return {
			...context.lookAndFeel,
			choice: ( event as lookAndFeelCompleteEvent ).payload,
		};
	},
} );

const assignToneOfVoice = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	toneOfVoice: ( context, event: unknown ) => {
		return {
			...context.toneOfVoice,
			choice: ( event as toneOfVoiceCompleteEvent ).payload,
		};
	},
} );

const assignLookAndTone = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	lookAndFeel: ( _context, event: unknown ) => {
		return {
			choice: ( event as { data: LookAndToneCompletionResponse } ).data
				.look,
			aiRecommended: ( event as { data: LookAndToneCompletionResponse } )
				.data.look,
		};
	},
	toneOfVoice: ( _context, event: unknown ) => {
		return {
			choice: ( event as { data: LookAndToneCompletionResponse } ).data
				.tone,
			aiRecommended: ( event as { data: LookAndToneCompletionResponse } )
				.data.tone,
		};
	},
} );

const assignDefaultColorPalette = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	aiSuggestions: ( context, event: unknown ) => {
		return {
			...context.aiSuggestions,
			defaultColorPalette: (
				event as {
					data: {
						response: ColorPaletteResponse;
					};
				}
			 ).data.response,
		};
	},
} );

const assignFontPairing = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	aiSuggestions: ( context ) => {
		let fontPairing = context.aiSuggestions.fontPairing;
		const choice = context.lookAndFeel.choice;

		switch ( true ) {
			case choice === 'Contemporary':
				fontPairing = 'Inter + Inter';
				break;
			case choice === 'Classic':
				fontPairing = 'Bodoni Moda + Overpass';
				break;
			case choice === 'Bold':
				fontPairing = 'Rubik + Inter';
				break;
		}

		return {
			...context.aiSuggestions,
			fontPairing,
		};
	},
} );

const assignHeader = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	aiSuggestions: ( context, event: unknown ) => {
		return {
			...context.aiSuggestions,
			header: (
				event as {
					data: {
						response: Header;
					};
				}
			 ).data.response.slug,
		};
	},
} );

const assignFooter = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	aiSuggestions: ( context, event: unknown ) => {
		return {
			...context.aiSuggestions,
			footer: (
				event as {
					data: {
						response: Footer;
					};
				}
			 ).data.response.slug,
		};
	},
} );

const assignHomepageTemplate = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	aiSuggestions: ( context, event: unknown ) => {
		return {
			...context.aiSuggestions,
			homepageTemplate: (
				event as {
					data: {
						response: HomepageTemplate;
					};
				}
			 ).data.response.homepage_template,
		};
	},
} );

const updateWooAiStoreDescriptionOption = ( descriptionText: string ) => {
	return dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		woo_ai_describe_store_description: descriptionText,
	} );
};

const spawnSaveDescriptionToOption = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents,
	designWithAiStateMachineEvents
>( {
	spawnSaveDescriptionToOptionRef: (
		context: designWithAiStateMachineContext
	) =>
		spawn(
			() =>
				updateWooAiStoreDescriptionOption(
					context.businessInfoDescription.descriptionText
				),
			'update-woo-ai-business-description-option'
		),
} );

const assignAPICallLoaderError = assign<
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents
>( {
	apiCallLoader: () => {
		trackEvent( 'customize_your_store_ai_wizard_error' );

		return {
			hasErrors: true,
		};
	},
} );

const logAIAPIRequestError = () => {
	// log AI API request error
	// eslint-disable-next-line no-console
	console.log( 'API Request error' );
};

const updateQueryStep = (
	_context: unknown,
	_evt: unknown,
	{ action }: { action: unknown }
) => {
	const { path } = getQuery() as { path: string };
	const step = ( action as { step: string } ).step;
	const pathFragments = path.split( '/' ); // [0] '', [1] 'customize-store', [2] cys step slug [3] design-with-ai step slug
	if (
		pathFragments[ 1 ] === 'customize-store' &&
		pathFragments[ 2 ] === 'design-with-ai'
	) {
		if ( pathFragments[ 3 ] !== step ) {
			// this state machine is only concerned with [2], so we ignore changes to [3]
			// [1] is handled by router at root of wc-admin
			updateQueryString(
				{},
				`/customize-store/design-with-ai/${ step }`
			);
		}
	}
};

const recordTracksStepViewed = (
	_context: unknown,
	_event: unknown,
	{ action }: { action: unknown }
) => {
	const { step } = action as { step: string };
	trackEvent( 'customize_your_store_ai_wizard_step_view', {
		step,
	} );
};

const recordTracksStepClosed = (
	_context: unknown,
	event: aiWizardClosedBeforeCompletionEvent
) => {
	const { step } = event.payload;
	trackEvent( `customize_your_store_ai_wizard_step_close`, {
		step: step.replaceAll( '-', '_' ),
	} );
};

const recordTracksStepCompleted = (
	_context: unknown,
	_event: unknown,
	{ action }: { action: unknown }
) => {
	const { step } = action as { step: string };
	trackEvent( 'customize_your_store_ai_wizard_step_complete', {
		step,
	} );
};

const redirectToAssemblerHub = async () => {
	// This is a workaround to update the "activeThemeHasMods" in the parent's machine
	// state context. We should find a better way to do this using xstate actions,
	// since state machines should rely only on their context.
	// Will be fixed on: https://github.com/woocommerce/woocommerce/issues/44349
	// This is needed because the iframe loads the entire Customize Store app.
	// This means that the iframe instance will have different state machines
	// than the parent window.
	// Check https://github.com/woocommerce/woocommerce/pull/44206 for more details.
	window.parent.__wcCustomizeStore.activeThemeHasMods = true;
};

export const actions = {
	assignStartLoadingTime,
	assignBusinessInfoDescription,
	assignLookAndFeel,
	assignToneOfVoice,
	assignLookAndTone,
	assignDefaultColorPalette,
	assignFontPairing,
	assignHeader,
	assignFooter,
	assignHomepageTemplate,
	assignAPICallLoaderError,
	logAIAPIRequestError,
	updateQueryStep,
	recordTracksStepViewed,
	recordTracksStepClosed,
	recordTracksStepCompleted,
	spawnSaveDescriptionToOption,
	redirectToAssemblerHub,
};
