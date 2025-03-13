/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { onboardingStore, optionsStore } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { FlowType, aiStatusResponse } from '../types';
import { isIframe } from '~/customize-store/utils';

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
	const task = await resolveSelect( onboardingStore ).getTask(
		'customize-store'
	);

	return {
		customizeStoreTaskCompleted: task?.isComplete,
	};
};

export const fetchIntroData = async () => {
	const currentTemplatePromise = resolveSelect(
		coreStore
	).getDefaultTemplateId( { slug: 'home' } );

	const maybePreviousTemplatePromise = resolveSelect(
		optionsStore
	).getOption( 'woocommerce_admin_customize_store_completed_theme_id' );

	const getTaskPromise =
		resolveSelect( onboardingStore ).getTask( 'customize-store' );

	const themeDataPromise = fetchThemeCards();

	const [ currentTemplateId, maybePreviousTemplate, task, themeData ] =
		await Promise.all( [
			currentTemplatePromise,
			maybePreviousTemplatePromise,
			getTaskPromise,
			themeDataPromise,
		] );

	let currentThemeIsAiGenerated = false;
	if (
		maybePreviousTemplate &&
		currentTemplateId === maybePreviousTemplate
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

const fetchIsPTKPatternsAPIAvailable = async () => {
	try {
		await apiFetch( {
			path: '/wc/private/patterns',
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
			PTK_PATTERNS_API_AVAILABLE: ( async () => {
				const isPTKPatternsAPIAvailable =
					await fetchIsPTKPatternsAPIAvailable();
				window.__wcCustomizeStore = {
					...window.__wcCustomizeStore,
					isPTKPatternsAPIAvailable,
				};
			} )(),
		};

		// Since the _featureFlags values are promises, we need to wait for
		// all of them to resolve before returning.
		await Promise.all( Object.values( _featureFlags ) );
	}

	return FlowType.noAI;
};
