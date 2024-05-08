// @ts-expect-error -- No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { store as coreStore } from '@wordpress/core-data';
/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { FlowType, aiStatusResponse } from '../types';
import { isIframe } from '~/customize-store/utils';
import { isWooExpress } from '~/utils/is-woo-express';
import { trackEvent } from '../tracking';

export const fetchAiStatus = async (): Promise< aiStatusResponse > => {
	const response = await fetch(
		'https://status.openai.com/api/v2/status.json'
	);
	const data = await response.json();
	return data;
};

export const fetchThemeCards = async () => {
	const themes = await apiFetch( {
		path: '/wc-admin/onboarding/themes/recommended',
		method: 'GET',
	} );

	return themes;
};

export const fetchCustomizeStoreCompleted = async () => {
	const task = await resolveSelect( ONBOARDING_STORE_NAME ).getTask(
		'customize-store'
	);

	return {
		customizeStoreTaskCompleted: task?.isComplete,
	};
};

export const fetchIntroData = async () => {
	const currentTemplatePromise =
		// @ts-expect-error No types for this exist yet.
		resolveSelect( coreStore ).__experimentalGetTemplateForLink( '/' );

	const maybePreviousTemplatePromise = resolveSelect(
		OPTIONS_STORE_NAME
	).getOption( 'woocommerce_admin_customize_store_completed_theme_id' );

	const getTaskPromise = resolveSelect( ONBOARDING_STORE_NAME ).getTask(
		'customize-store'
	);

	const themeDataPromise = fetchThemeCards();

	const [ currentTemplate, maybePreviousTemplate, task, themeData ] =
		await Promise.all( [
			currentTemplatePromise,
			maybePreviousTemplatePromise,
			getTaskPromise,
			themeDataPromise,
		] );

	let currentThemeIsAiGenerated = false;
	if (
		maybePreviousTemplate &&
		currentTemplate?.id === maybePreviousTemplate
	) {
		currentThemeIsAiGenerated = true;
	}

	const customizeStoreTaskCompleted = task?.isComplete;

	interface Theme {
		stylesheet?: string;
	}

	const theme = ( await resolveSelect( 'core' ).getCurrentTheme() ) as Theme;

	return {
		customizeStoreTaskCompleted,
		themeData,
		activeTheme: theme.stylesheet || '',
		currentThemeIsAiGenerated,
	};
};

const fetchIsFontLibraryAvailable = async () => {
	try {
		await apiFetch( {
			path: '/wp/v2/font-collections?_fields=slug',
			method: 'GET',
		} );

		return true;
	} catch ( err ) {
		return false;
	}
};

export const setFlags = async () => {
	if ( ! isIframe( window ) ) {
		// To improve the readability of the code, we want to use a dictionary
		// where the key is the feature flag name and the value is the
		// function to retrieve flag value.

		// eslint-disable-next-line @typescript-eslint/no-unused-vars
		const _featureFlags = {
			FONT_LIBRARY_AVAILABLE: ( async () => {
				const isFontLibraryAvailable =
					await fetchIsFontLibraryAvailable();
				window.__wcCustomizeStore = {
					...window.__wcCustomizeStore,
					isFontLibraryAvailable,
				};
			} )(),
		};

		// Since the _featureFlags values are promises, we need to wait for
		// all of them to resolve before returning.
		await Promise.all( Object.values( _featureFlags ) );
	}

	// Set FlowType flag. We want to set the flag only in the parent window.
	if ( isWooExpress() && ! isIframe( window ) ) {
		try {
			const { status } = await fetchAiStatus();

			const isAiOnline =
				status.indicator !== 'critical' && status.indicator !== 'major';

			// @ts-expect-error temp workaround;
			window.cys_aiOnline = status;
			trackEvent( 'customize_your_store_ai_status', {
				online: isAiOnline ? 'yes' : 'no',
			} );

			return isAiOnline ? FlowType.AIOnline : FlowType.AIOffline;
		} catch ( e ) {
			// @ts-expect-error temp workaround;
			window.cys_aiOnline = false;
			trackEvent( 'customize_your_store_ai_status', {
				online: 'no',
			} );
			return FlowType.AIOffline;
		}
	}

	return FlowType.noAI;
};
