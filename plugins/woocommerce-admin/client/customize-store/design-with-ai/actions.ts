/**
 * External dependencies
 */
import { assign, spawn } from 'xstate';
import {
	getQuery,
	updateQueryString,
	getNewPath,
} from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
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
import { attachIframeListeners, onIframeLoad } from '../utils';

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
		recordEvent( 'customize_your_store_ai_wizard_error' );

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
	recordEvent( 'customize_your_store_ai_wizard_step_view', {
		step,
	} );
};

const recordTracksStepClosed = (
	_context: unknown,
	event: aiWizardClosedBeforeCompletionEvent
) => {
	const { step } = event.payload;
	recordEvent( `customize_your_store_ai_wizard_step_close`, {
		step: step.replaceAll( '-', '_' ),
	} );
};

const recordTracksStepCompleted = (
	_context: unknown,
	_event: unknown,
	{ action }: { action: unknown }
) => {
	const { step } = action as { step: string };
	recordEvent( 'customize_your_store_ai_wizard_step_complete', {
		step,
	} );
};

const redirectToAssemblerHub = async (
	context: designWithAiStateMachineContext
) => {
	const assemblerUrl = getNewPath( {}, '/customize-store/assembler-hub', {} );
	const iframe = document.createElement( 'iframe' );
	iframe.classList.add( 'cys-fullscreen-iframe' );
	iframe.src = assemblerUrl;

	const showIframe = () => {
		if ( iframe.style.opacity === '1' ) {
			// iframe is already visible
			return;
		}

		const loader = document.getElementsByClassName(
			'woocommerce-onboarding-loader'
		);
		if ( loader[ 0 ] ) {
			( loader[ 0 ] as HTMLElement ).style.display = 'none';
		}

		iframe.style.opacity = '1';

		if ( context.startLoadingTime ) {
			const endLoadingTime = performance.now();
			const timeToLoad = endLoadingTime - context.startLoadingTime;
			recordEvent( 'customize_your_store_ai_wizard_loading_time', {
				time_in_s: ( timeToLoad / 1000 ).toFixed( 2 ),
			} );
		}
	};

	iframe.onload = () => {
		// Hide loading UI
		attachIframeListeners( iframe );
		onIframeLoad( showIframe );

		// Ceiling wait time set to 60 seconds
		setTimeout( showIframe, 60 * 1000 );
		window.history?.pushState( {}, '', assemblerUrl );
	};

	document.body.appendChild( iframe );

	// Listen for back button click
	window.addEventListener(
		'popstate',
		() => {
			const apiLoaderUrl = getNewPath(
				{},
				'/customize-store/design-with-ai/api-call-loader',
				{}
			);

			// Only catch the back button click when the user is on the main assember hub page
			// and trying to go back to the api loader page
			if ( 'admin.php' + window.location.search === apiLoaderUrl ) {
				iframe.contentWindow?.postMessage(
					{
						type: 'assemberBackButtonClicked',
					},
					'*'
				);
				// When the user clicks the back button, push state changes to the previous step
				// Set it back to the assember hub
				window.history?.pushState( {}, '', assemblerUrl );
			}
		},
		false
	);
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
