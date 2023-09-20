/**
 * External dependencies
 */
import { createMachine, sendParent } from 'xstate';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	designWithAiStateMachineContext,
	designWithAiStateMachineEvents,
	ColorPalette,
	FontPairing,
} from './types';
import {
	BusinessInfoDescription,
	LookAndFeel,
	ToneOfVoice,
	ApiCallLoader,
} from './pages';
import { actions } from './actions';
import { services } from './services';
import { defaultColorPalette, fontPairings } from './prompts';

export const hasStepInUrl = (
	_ctx: unknown,
	_evt: unknown,
	{ cond }: { cond: unknown }
) => {
	const { path = '' } = getQuery() as { path: string };
	const pathFragments = path.split( '/' );
	return (
		pathFragments[ 3 ] === // [0] '', [1] 'customize-store', [2] cys step slug [3] design-with-ai step slug
		( cond as { step: string | undefined } ).step
	);
};

export const designWithAiStateMachineDefinition = createMachine(
	{
		id: 'designWithAi',
		predictableActionArguments: true,
		preserveActionOrder: true,
		schema: {
			context: {} as designWithAiStateMachineContext,
			events: {} as designWithAiStateMachineEvents,
		},
		invoke: {
			src: 'browserPopstateHandler',
		},
		on: {
			EXTERNAL_URL_UPDATE: {
				target: 'navigate',
			},
			AI_WIZARD_CLOSED_BEFORE_COMPLETION: {
				actions: [
					sendParent( ( _context, event ) => event ),
					'recordTracksStepClosed',
				],
			},
		},
		context: {
			businessInfoDescription: {
				descriptionText: '',
			},
			lookAndFeel: {
				choice: '',
			},
			toneOfVoice: {
				choice: '',
			},
			aiSuggestions: {
				defaultColorPalette: {} as ColorPalette,
				fontPairing: '' as FontPairing[ 'pair_name' ],
			},
		},
		initial: 'navigate',
		states: {
			navigate: {
				always: [
					{
						target: 'businessInfoDescription',
						cond: {
							type: 'hasStepInUrl',
							step: 'business-info-description',
						},
					},
					{
						target: 'lookAndFeel',
						cond: {
							type: 'hasStepInUrl',
							step: 'look-and-feel',
						},
					},
					{
						target: 'toneOfVoice',
						cond: {
							type: 'hasStepInUrl',
							step: 'tone-of-voice',
						},
					},
					{
						target: 'apiCallLoader',
						cond: {
							type: 'hasStepInUrl',
							step: 'api-call-loader',
						},
					},
					{
						target: 'businessInfoDescription',
					},
				],
			},
			businessInfoDescription: {
				id: 'businessInfoDescription',
				initial: 'preBusinessInfoDescription',
				states: {
					preBusinessInfoDescription: {
						// if we need to prefetch options, other settings previously populated from core profiler, do it here
						always: {
							target: 'businessInfoDescription',
						},
					},
					businessInfoDescription: {
						meta: {
							component: BusinessInfoDescription,
						},
						entry: [
							{
								type: 'recordTracksStepViewed',
								step: 'business_info_description',
							},
						],
						on: {
							BUSINESS_INFO_DESCRIPTION_COMPLETE: {
								actions: [ 'assignBusinessInfoDescription' ],
								target: 'postBusinessInfoDescription',
							},
						},
					},
					postBusinessInfoDescription: {
						invoke: {
							src: 'getLookAndTone',
							onError: {
								actions: [
									{
										type: 'recordTracksStepCompleted',
										step: 'business_info_description',
									},
									'logAIAPIRequestError',
								],
								target: '#lookAndFeel',
							},
							onDone: {
								actions: [
									{
										type: 'recordTracksStepCompleted',
										step: 'business_info_description',
									},
									'assignLookAndTone',
								],
								target: '#lookAndFeel',
							},
						},
					},
				},
			},
			lookAndFeel: {
				id: 'lookAndFeel',
				initial: 'preLookAndFeel',
				states: {
					preLookAndFeel: {
						always: {
							target: 'lookAndFeel',
						},
					},
					lookAndFeel: {
						meta: {
							component: LookAndFeel,
						},
						entry: [
							{
								type: 'updateQueryStep',
								step: 'look-and-feel',
							},
							{
								type: 'recordTracksStepViewed',
								step: 'look_and_feel',
							},
						],
						on: {
							LOOK_AND_FEEL_COMPLETE: {
								actions: [
									{
										type: 'recordTracksStepCompleted',
										step: 'look_and_feel',
									},
									'assignLookAndFeel',
								],
								target: 'postLookAndFeel',
							},
						},
					},
					postLookAndFeel: {
						always: {
							target: '#toneOfVoice',
						},
					},
				},
			},
			toneOfVoice: {
				id: 'toneOfVoice',
				initial: 'preToneOfVoice',
				states: {
					preToneOfVoice: {
						always: {
							target: 'toneOfVoice',
						},
					},
					toneOfVoice: {
						meta: {
							component: ToneOfVoice,
						},
						entry: [
							{
								type: 'updateQueryStep',
								step: 'tone-of-voice',
							},
							{
								type: 'recordTracksStepViewed',
								step: 'tone_of_voice',
							},
						],
						on: {
							TONE_OF_VOICE_COMPLETE: {
								actions: [
									'assignToneOfVoice',
									{
										type: 'recordTracksStepCompleted',
										step: 'tone_of_voice',
									},
								],
								target: 'postToneOfVoice',
							},
						},
					},
					postToneOfVoice: {
						always: {
							target: '#apiCallLoader',
						},
					},
				},
			},
			apiCallLoader: {
				id: 'apiCallLoader',
				initial: 'preApiCallLoader',
				states: {
					preApiCallLoader: {
						always: {
							target: 'apiCallLoader',
						},
					},
					apiCallLoader: {
						meta: {
							component: ApiCallLoader,
						},
						entry: [
							{
								type: 'updateQueryStep',
								step: 'api-call-loader',
							},
						],
						type: 'parallel',
						states: {
							chooseColorPairing: {
								invoke: {
									src: 'queryAiEndpoint',
									data: ( context ) => {
										return {
											...defaultColorPalette,
											prompt: defaultColorPalette.prompt(
												context.businessInfoDescription
													.descriptionText,
												context.lookAndFeel.choice,
												context.toneOfVoice.choice
											),
										};
									},
									onDone: {
										actions: [
											'assignDefaultColorPalette',
										],
									},
								},
							},
							chooseFontPairing: {
								invoke: {
									src: 'queryAiEndpoint',
									data: ( context ) => {
										return {
											...fontPairings,
											prompt: fontPairings.prompt(
												context.businessInfoDescription
													.descriptionText,
												context.lookAndFeel.choice,
												context.toneOfVoice.choice
											),
										};
									},
									onDone: {
										actions: [ 'assignFontPairing' ],
									},
								},
							},
						},
					},
					postApiCallLoader: {},
				},
			},
		},
	},
	{
		actions,
		services,
		guards: {
			hasStepInUrl,
		},
	}
);
