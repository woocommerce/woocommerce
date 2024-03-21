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
import { aiStatusResponse } from '../types';
import { isIframe } from '~/customize-store/utils';

export const fetchAiStatus = () => async (): Promise< aiStatusResponse > => {
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

export const fetchActiveThemeHasMods = async () => {
	const currentTemplatePromise =
		// @ts-expect-error No types for this exist yet.
		resolveSelect( coreStore ).__experimentalGetTemplateForLink( '/' );

	const styleRevsPromise =
		// @ts-expect-error No types for this exist yet.
		resolveSelect( coreStore ).getCurrentThemeGlobalStylesRevisions();

	// @ts-expect-error No types for this exist yet.
	const hasModifiedPagesPromise = resolveSelect( coreStore ).getEntityRecords(
		'postType',
		'page',
		{
			per_page: 100,
			_fields: [ 'id', '_links.version-history' ],
			orderby: 'menu_order',
			order: 'asc',
		}
	);

	const [ currentTemplate, styleRevs, rawPages ] = await Promise.all( [
		currentTemplatePromise,
		styleRevsPromise,
		hasModifiedPagesPromise,
	] );

	const hasModifiedPages = rawPages?.some(
		( page: { _links: { [ key: string ]: string[] } } ) => {
			return page._links?.[ 'version-history' ]?.length > 1;
		}
	);

	const activeThemeHasMods =
		!! currentTemplate?.modified ||
		styleRevs?.length > 0 ||
		hasModifiedPages;

	return { activeThemeHasMods };
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
};
