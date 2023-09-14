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
} from './types';
import {
	BusinessInfoDescription,
	LookAndFeel,
	ToneOfVoice,
	ApiCallLoader,
} from './pages';
import { actions } from './actions';
import { services } from './services';

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
				actions: sendParent( ( _context, event ) => event ),
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
								actions: [ 'logAIAPIRequestError' ],
								target: '#lookAndFeel',
							},
							onDone: {
								actions: [ 'assignLookAndTone' ],
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
						],
						on: {
							LOOK_AND_FEEL_COMPLETE: {
								actions: [ 'assignLookAndFeel' ],
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
						],
						on: {
							TONE_OF_VOICE_COMPLETE: {
								actions: [ 'assignToneOfVoice' ],
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
