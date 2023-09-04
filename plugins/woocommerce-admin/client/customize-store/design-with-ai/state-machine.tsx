/**
 * External dependencies
 */
import { createMachine } from 'xstate';

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
						always: {
							target: '#lookAndFeel',
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
				// TODO: handle this event when the 'x' is clicked at any point
				// probably bail (to where?) and log the tracks for which step it is in plus
				// whatever details might be helpful to know why they bailed
			},
		},
	},
	{
		actions,
	}
);
