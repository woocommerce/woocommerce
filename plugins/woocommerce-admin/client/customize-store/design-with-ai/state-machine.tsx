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
	FontPairing,
	Header,
	Footer,
	ColorPaletteResponse,
} from './types';
import {
	BusinessInfoDescription,
	LookAndFeel,
	ToneOfVoice,
	ApiCallLoader,
} from './pages';
import { actions } from './actions';
import { services } from './services';
import {
	defaultColorPalette,
	fontPairings,
	defaultHeader,
	defaultFooter,
} from './prompts';

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
				defaultColorPalette: {} as ColorPaletteResponse,
				fontPairing: '' as FontPairing[ 'pair_name' ],
				header: '' as Header[ 'slug' ],
				footer: '' as Footer[ 'slug' ],
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
								actions: [
									'assignBusinessInfoDescription',
									'spawnSaveDescriptionToOption',
								],
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
								initial: 'pending',
								states: {
									pending: {
										invoke: {
											src: 'queryAiEndpoint',
											data: ( context ) => {
												return {
													...defaultColorPalette,
													prompt: defaultColorPalette.prompt(
														context
															.businessInfoDescription
															.descriptionText,
														context.lookAndFeel
															.choice,
														context.toneOfVoice
															.choice
													),
												};
											},
											onDone: {
												actions: [
													'assignDefaultColorPalette',
												],
												target: 'success',
											},
										},
									},
									success: { type: 'final' },
								},
							},
							chooseFontPairing: {
								initial: 'pending',
								states: {
									pending: {
										invoke: {
											src: 'queryAiEndpoint',
											data: ( context ) => {
												return {
													...fontPairings,
													prompt: fontPairings.prompt(
														context
															.businessInfoDescription
															.descriptionText,
														context.lookAndFeel
															.choice,
														context.toneOfVoice
															.choice
													),
												};
											},
											onDone: {
												actions: [
													'assignFontPairing',
												],
												target: 'success',
											},
										},
									},
									success: { type: 'final' },
								},
							},
							chooseHeader: {
								initial: 'pending',
								states: {
									pending: {
										invoke: {
											src: 'queryAiEndpoint',
											data: ( context ) => {
												return {
													...defaultHeader,
													prompt: defaultHeader.prompt(
														context
															.businessInfoDescription
															.descriptionText,
														context.lookAndFeel
															.choice,
														context.toneOfVoice
															.choice
													),
												};
											},
											onDone: {
												actions: [ 'assignHeader' ],
												target: 'success',
											},
										},
									},
									success: { type: 'final' },
								},
							},
							chooseFooter: {
								initial: 'pending',
								states: {
									pending: {
										invoke: {
											src: 'queryAiEndpoint',
											data: ( context ) => {
												return {
													...defaultFooter,
													prompt: defaultFooter.prompt(
														context
															.businessInfoDescription
															.descriptionText,
														context.lookAndFeel
															.choice,
														context.toneOfVoice
															.choice
													),
												};
											},
											onDone: {
												actions: [ 'assignFooter' ],
												target: 'success',
											},
										},
									},
									success: { type: 'final' },
								},
							},
							updateStorePatterns: {
								initial: 'pending',
								states: {
									pending: {
										invoke: {
											src: 'updateStorePatterns',
											onDone: {
												target: 'success',
											},
											onError: {
												// TODO: handle error
												target: 'success',
											},
										},
									},
									success: { type: 'final' },
								},
							},
						},
						onDone: 'postApiCallLoader',
					},
					postApiCallLoader: {
						type: 'parallel',
						states: {
							assembleSite: {
								initial: 'pending',
								states: {
									pending: {
										invoke: {
											src: 'assembleSite',
											onDone: {
												target: 'done',
											},
											onError: {
												target: 'failed',
											},
										},
									},
									done: {
										type: 'final',
									},
									failed: {
										type: 'final', // If there's an error we should not block the user from proceeding. They'll just not see the AI suggestions, but that's better than being stuck
									},
								},
							},
							saveAiResponse: {
								initial: 'pending',
								states: {
									pending: {
										invoke: {
											src: 'saveAiResponseToOption',
											onDone: {
												target: 'done',
											},
											onError: {
												target: 'failed',
											},
										},
									},
									done: {
										type: 'final',
									},
									failed: {
										type: 'final',
									},
								},
							},
						},
						onDone: {
							actions: [
								sendParent( () => ( {
									type: 'THEME_SUGGESTED',
								} ) ),
							],
						},
					},
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
