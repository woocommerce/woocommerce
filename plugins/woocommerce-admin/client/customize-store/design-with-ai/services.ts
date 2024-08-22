/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';
import apiFetch from '@wordpress/api-fetch';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { Sender, assign, createMachine, actions } from 'xstate';
import { dispatch, resolveSelect } from '@wordpress/data';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
// @ts-ignore No types for this exist yet.
import { mergeBaseAndUserConfigs } from '@wordpress/edit-site/build-module/components/global-styles/global-styles-provider';
/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from './types';
import { FONT_PAIRINGS } from '../assembler-hub/sidebar/global-styles/font-pairing-variations/constants';
import { COLOR_PALETTES } from '../assembler-hub/sidebar/global-styles/color-palette-variations/constants';
import { HOMEPAGE_TEMPLATES } from '../data/homepageTemplates';
import { updateTemplate } from '../data/actions';
import { installAndActivateTheme as setTheme } from '../data/service';
import { THEME_SLUG } from '../data/constants';
import { trackEvent } from '../tracking';
import { installPatterns } from '../design-without-ai/services';

const { escalate } = actions;

const browserPopstateHandler =
	() => ( sendBack: Sender< { type: 'EXTERNAL_URL_UPDATE' } > ) => {
		const popstateHandler = () => {
			sendBack( { type: 'EXTERNAL_URL_UPDATE' } );
		};
		window.addEventListener( 'popstate', popstateHandler );
		return () => {
			window.removeEventListener( 'popstate', popstateHandler );
		};
	};

export const getCompletion = async < ValidResponseObject >( {
	queryId,
	prompt,
	version,
	responseValidation,
	retryCount,
	abortSignal = AbortSignal.timeout( 10000 ),
}: {
	queryId: string;
	prompt: string;
	version: string;
	responseValidation: ( arg0: string ) => ValidResponseObject;
	retryCount: number;
	abortSignal?: AbortSignal;
} ) => {
	const { token } = await requestJetpackToken();
	let data: {
		completion: string;
	};
	let parsedCompletionJson;
	try {
		const url = new URL(
			'https://public-api.wordpress.com/wpcom/v2/text-completion'
		);

		url.searchParams.append( 'feature', 'woo_cys' );

		data = await apiFetch( {
			url: url.toString(),
			method: 'POST',
			data: {
				token,
				prompt,
				_fields: 'completion',
			},
			signal: abortSignal,
		} );
	} catch ( error ) {
		trackEvent( 'customize_your_store_ai_completion_api_error', {
			query_id: queryId,
			version,
			retry_count: retryCount,
			error_type: 'api_request_error',
		} );
		throw error;
	}

	try {
		parsedCompletionJson = JSON.parse( data.completion );
	} catch {
		trackEvent( 'customize_your_store_ai_completion_response_error', {
			query_id: queryId,
			version,
			retry_count: retryCount,
			error_type: 'json_parse_error',
			response: data.completion,
		} );
		throw new Error(
			`Error validating Jetpack AI text completions response for ${ queryId }`
		);
	}

	try {
		const validatedResponse = responseValidation( parsedCompletionJson );
		trackEvent( 'customize_your_store_ai_completion_success', {
			query_id: queryId,
			version,
			retry_count: retryCount,
		} );
		return validatedResponse;
	} catch ( error ) {
		trackEvent( 'customize_your_store_ai_completion_response_error', {
			query_id: queryId,
			version,
			retry_count: retryCount,
			error_type: 'valid_json_invalid_values',
			response: data.completion,
		} );
		throw error;
	}
};

export const queryAiEndpoint = createMachine(
	{
		id: 'query-ai-endpoint',
		predictableActionArguments: true,
		initial: 'init',
		context: {
			// these values are all overwritten by incoming parameters
			prompt: '',
			queryId: '',
			version: '',
			responseValidation: () => true,
			retryCount: 0,
			validatedResponse: {} as unknown,
		},
		states: {
			init: {
				always: 'querying',
				entry: [ 'setRetryCount' ],
			},
			querying: {
				invoke: {
					src: 'getCompletion',
					onDone: {
						target: 'success',
						actions: [ 'handleAiResponse' ],
					},
					onError: {
						target: 'error',
					},
				},
			},
			error: {
				always: [
					{
						cond: ( context ) => context.retryCount >= 3,
						target: 'querying',
						actions: [
							// Throw an error to be caught by the parent machine.
							escalate( () => ( {
								data: 'Max retries exceeded',
							} ) ),
						],
					},
					{
						target: 'querying',
						actions: assign( {
							retryCount: ( context ) => context.retryCount + 1,
						} ),
					},
				],
			},
			success: {
				type: 'final',
				data: ( context ) => {
					return {
						result: 'success',
						response: context.validatedResponse,
					};
				},
			},
		},
	},
	{
		actions: {
			handleAiResponse: assign( {
				validatedResponse: ( _context, event: unknown ) =>
					( event as { data: unknown } ).data,
			} ),
			setRetryCount: assign( {
				retryCount: 0,
			} ),
		},
		services: {
			getCompletion,
		},
	}
);

const resetPatternsAndProducts = () => async () => {
	await dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		woocommerce_blocks_allow_ai_connection: 'yes',
	} );

	const response = await apiFetch< {
		is_ai_generated: boolean;
	} >( {
		path: '/wc-admin/ai/store-info',
		method: 'GET',
	} );

	if ( response.is_ai_generated ) {
		return;
	}

	return Promise.all( [
		apiFetch( {
			path: '/wc-admin/ai/patterns',
			method: 'DELETE',
		} ),
		apiFetch( {
			path: '/wc-admin/ai/products',
			method: 'DELETE',
		} ),
	] );
};

export const updateStorePatterns = async (
	context: designWithAiStateMachineContext
) => {
	try {
		// TODO: Probably move this to a more appropriate place with a check. We should set this when the user granted permissions during the onboarding phase.
		await dispatch( OPTIONS_STORE_NAME ).updateOptions( {
			woocommerce_blocks_allow_ai_connection: 'yes',
		} );

		const { images } = await apiFetch< {
			ai_content_generated: boolean;
			images: { images: Array< unknown >; search_term: string };
		} >( {
			path: '/wc-admin/ai/images',
			method: 'POST',
			data: {
				business_description:
					context.businessInfoDescription.descriptionText,
			},
		} );

		const { is_ai_generated } = await apiFetch< {
			is_ai_generated: boolean;
		} >( {
			path: '/wc-admin/ai/store-info',
			method: 'GET',
		} );

		if ( ! images ) {
			if ( ! is_ai_generated ) {
				throw new Error(
					'AI content not generated: images not available'
				);
			}

			await resetPatternsAndProducts()();
			return;
		}

		const [ response ] = await Promise.all< {
			ai_content_generated: boolean;
			product_content: Array< {
				title: string;
				description: string;
				image: {
					src: string;
					alt: string;
				};
			} >;
			additional_errors?: unknown[];
		} >( [
			apiFetch( {
				path: '/wc-admin/ai/products',
				method: 'POST',
				data: {
					business_description:
						context.businessInfoDescription.descriptionText,
					images,
				},
			} ),
			apiFetch( {
				path: '/wc-admin/ai/patterns',
				method: 'POST',
				data: {
					business_description:
						context.businessInfoDescription.descriptionText,
					images,
				},
			} ),
		] );

		const productContents = response.product_content.map(
			( product, index ) => {
				return apiFetch( {
					path: '/wc-admin/ai/product',
					method: 'POST',
					data: {
						products_information: product,
						last_product:
							index === response.product_content.length - 1,
					},
				} );
			}
		);

		await Promise.all( [
			...productContents,
			apiFetch( {
				path: '/wc-admin/ai/business-description',
				method: 'POST',
				data: {
					business_description:
						context.businessInfoDescription.descriptionText,
				},
			} ),
			apiFetch( {
				path: '/wc-admin/ai/store-title',
				method: 'POST',
				data: {
					business_description:
						context.businessInfoDescription.descriptionText,
				},
			} ),
		] );

		if ( ! response.ai_content_generated ) {
			throw new Error(
				'AI content not generated: ' + response.additional_errors
					? JSON.stringify( response.additional_errors )
					: ''
			);
		}
	} catch ( error ) {
		trackEvent( 'customize_your_store_update_store_pattern_api_error', {
			error: error instanceof Error ? error.message : 'unknown',
		} );
		throw error;
	}
};

// Update the current global styles of theme
const updateGlobalStyles = async ( {
	colorPaletteName = COLOR_PALETTES[ 0 ].title,
	fontPairingName = FONT_PAIRINGS[ 0 ].title,
}: {
	colorPaletteName: string;
	fontPairingName: string;
} ) => {
	const colorPalette = COLOR_PALETTES.find(
		( palette ) => palette.title === colorPaletteName
	);
	const fontPairing = FONT_PAIRINGS.find(
		( pairing ) => pairing.title === fontPairingName
	);

	// @ts-ignore No types for this exist yet.
	const { invalidateResolutionForStoreSelector } = dispatch( coreStore );
	invalidateResolutionForStoreSelector(
		'__experimentalGetCurrentGlobalStylesId'
	);

	const globalStylesId = await resolveSelect(
		coreStore
		// @ts-ignore No types for this exist yet.
	).__experimentalGetCurrentGlobalStylesId();

	// @ts-ignore No types for this exist yet.
	const { saveEntityRecord } = dispatch( coreStore );

	await saveEntityRecord(
		'root',
		'globalStyles',
		{
			id: globalStylesId,
			styles: mergeBaseAndUserConfigs(
				colorPalette?.styles || {},
				fontPairing?.styles || {}
			),
			settings: mergeBaseAndUserConfigs(
				colorPalette?.settings || {},
				fontPairing?.settings || {}
			),
		},
		{
			throwOnError: true,
		}
	);
};

export const assembleSite = async (
	context: designWithAiStateMachineContext
) => {
	try {
		await updateGlobalStyles( {
			colorPaletteName: context.aiSuggestions.defaultColorPalette.default,
			fontPairingName: context.aiSuggestions.fontPairing,
		} );
		trackEvent( 'customize_your_store_ai_update_global_styles_success' );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( error );
		trackEvent(
			'customize_your_store_ai_update_global_styles_response_error',
			{
				error: error instanceof Error ? error.message : 'unknown',
			}
		);
		throw error;
	}

	try {
		await updateTemplate( {
			// TODO: Get from context
			homepageTemplateId: 'template1' as keyof typeof HOMEPAGE_TEMPLATES,
		} );
		trackEvent( 'customize_your_store_ai_update_template_success' );
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( error );
		trackEvent( 'customize_your_store_ai_update_template_response_error', {
			error: error instanceof Error ? error.message : 'unknown',
		} );
		throw error;
	}
};

const installAndActivateTheme = async () => {
	try {
		await setTheme( THEME_SLUG );
	} catch ( error ) {
		trackEvent(
			'customize_your_store_ai_install_and_activate_theme_error',
			{
				theme: THEME_SLUG,
				error: error instanceof Error ? error.message : 'unknown',
			}
		);
		throw error;
	}
};

const saveAiResponseToOption = ( context: designWithAiStateMachineContext ) => {
	return dispatch( OPTIONS_STORE_NAME ).updateOptions( {
		woocommerce_customize_store_ai_suggestions: {
			...context.aiSuggestions,
			lookAndFeel: context.lookAndFeel.choice,
		},
	} );
};

export const services = {
	browserPopstateHandler,
	queryAiEndpoint,
	assembleSite,
	updateStorePatterns,
	saveAiResponseToOption,
	installAndActivateTheme,
	resetPatternsAndProducts,
	installPatterns,
};
