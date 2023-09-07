/**
 * External dependencies
 */
import { createMachine, sendParent } from 'xstate';

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
export const designWithAiStateMachineDefinition = createMachine(
	{
		id: 'designWithAi',
		predictableActionArguments: true,
		preserveActionOrder: true,
		schema: {
			context: {} as designWithAiStateMachineContext,
			events: {} as designWithAiStateMachineEvents,
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
		initial: 'businessInfoDescription',
		states: {
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
					},
					postApiCallLoader: {},
				},
			},
		},
		on: {
			AI_WIZARD_CLOSED_BEFORE_COMPLETION: {
				actions: sendParent( ( _context, event ) => event ),
			},
		},
	},
	{
		actions,
		services,
	}
);
