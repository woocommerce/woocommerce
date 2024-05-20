/**
 * External dependencies
 */
import { createMachine } from 'xstate';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */

import { ApiCallLoader, AssembleHubLoader } from './pages/ApiCallLoader';

import { FlowType } from '../types';
import { DesignWithoutAIStateMachineContext } from './types';
import { services } from './services';
import { actions } from './actions';
import { isFontLibraryAvailable } from './guards';

export const hasStepInUrl = (
	_ctx: unknown,
	_evt: unknown,
	{ cond }: { cond: unknown }
) => {
	const { path = '' } = getQuery() as { path: string };
	const pathFragments = path.split( '/' );
	return (
		pathFragments[ 2 ] === // [0] '', [1] 'customize-store', [2] design step slug
		( cond as { step: string | undefined } ).step
	);
};

export const hasFontInstallInUrl = () => {
	const { path = '' } = getQuery() as { path: string };
	const pathFragments = path.split( '/' );
	return (
		pathFragments[ 2 ] === 'design' &&
		pathFragments[ 3 ] === 'install-fonts'
	);
};

const installFontFamiliesState = {
	initial: 'checkFontLibrary',
	states: {
		checkFontLibrary: {
			always: [
				{
					cond: {
						type: 'isFontLibraryAvailable',
					},
					target: 'pending',
				},
				{ target: 'success' },
			],
		},
		pending: {
			invoke: {
				src: 'installFontFamilies',
				onDone: {
					target: 'success',
				},
				onError: {
					actions: 'redirectToIntroWithError',
				},
			},
		},
		success: {
			type: 'final',
		},
	},
};

export type DesignWithoutAIStateMachineEvents =
	| { type: 'EXTERNAL_URL_UPDATE' }
	| { type: 'INSTALL_FONTS' }
	| { type: 'NO_AI_FLOW_ERROR'; payload: { hasError: boolean } };

export const designWithNoAiStateMachineDefinition = createMachine(
	{
		id: 'designWithoutAI',
		predictableActionArguments: true,
		preserveActionOrder: true,
		schema: {
			context: {} as DesignWithoutAIStateMachineContext,
			events: {} as DesignWithoutAIStateMachineEvents,
		},
		invoke: {
			src: 'browserPopstateHandler',
		},
		on: {
			EXTERNAL_URL_UPDATE: {
				target: 'navigate',
			},
			INSTALL_FONTS: {
				target: 'installFontFamilies',
			},
		},
		context: {
			startLoadingTime: null,
			flowType: FlowType.noAI,
			apiCallLoader: {
				hasErrors: false,
			},
			isFontLibraryAvailable: false,
		},
		initial: 'navigate',
		states: {
			navigate: {
				always: [
					{
						cond: {
							type: 'hasFontInstallInUrl',
							step: 'design',
						},
						target: 'installFontFamilies',
					},
					{
						cond: {
							type: 'hasStepInUrl',
							step: 'design',
						},
						target: 'preAssembleSite',
					},
				],
			},
			installFontFamilies: {
				meta: {
					component: ApiCallLoader,
				},
				initial: 'enableTracking',
				states: {
					enableTracking: {
						invoke: {
							src: 'enableTracking',
							onDone: {
								target: 'checkFontLibrary',
							},
						},
					},
					checkFontLibrary:
						installFontFamiliesState.states.checkFontLibrary,
					pending: installFontFamiliesState.states.pending,
					success: {
						type: 'final',
					},
				},
				onDone: {
					target: '#designWithoutAI.showAssembleHub',
				},
			},
			preAssembleSite: {
				initial: 'preApiCallLoader',
				id: 'preAssembleSite',
				states: {
					preApiCallLoader: {
						meta: {
							// @todo: Move the current component in a common folder or create a new one dedicated to this flow.
							component: ApiCallLoader,
						},
						type: 'parallel',
						states: {
							installAndActivateTheme: {
								initial: 'pending',
								states: {
									pending: {
										invoke: {
											src: 'installAndActivateTheme',
											onDone: {
												target: 'success',
											},
											onError: {
												actions:
													'redirectToIntroWithError',
											},
										},
									},
									success: { type: 'final' },
								},
							},
							createProducts: {
								initial: 'pending',
								states: {
									pending: {
										invoke: {
											src: 'createProducts',
											onDone: {
												target: 'success',
											},
											onError: {
												actions:
													'redirectToIntroWithError',
											},
										},
									},
									success: {
										type: 'final',
									},
								},
							},
							installFontFamilies: {
								initial: installFontFamiliesState.initial,
								states: {
									checkFontLibrary:
										installFontFamiliesState.states
											.checkFontLibrary,
									pending:
										installFontFamiliesState.states.pending,
									success: {
										type: 'final',
									},
								},
							},
						},
						onDone: {
							target: 'assembleSite',
						},
					},
					assembleSite: {
						initial: 'pending',
						states: {
							pending: {
								invoke: {
									src: 'assembleSite',
									onDone: {
										target: 'success',
									},
									onError: {
										actions: 'redirectToIntroWithError',
									},
								},
							},
							success: {
								type: 'final',
							},
						},
						onDone: {
							target: '#designWithoutAI.showAssembleHub',
						},
					},
				},
			},
			showAssembleHub: {
				id: 'showAssembleHub',
				meta: {
					component: AssembleHubLoader,
				},
				entry: [ 'redirectToAssemblerHub' ],
			},
		},
	},
	{
		actions,
		services,
		guards: {
			hasStepInUrl,
			isFontLibraryAvailable,
			hasFontInstallInUrl,
		},
	}
);
