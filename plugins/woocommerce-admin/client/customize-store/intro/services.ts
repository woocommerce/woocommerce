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

export const fetchIntroData = async () => {
	const currentTemplatePromise =
		// @ts-expect-error No types for this exist yet.
		resolveSelect( coreStore ).__experimentalGetTemplateForLink( '/' );

	const maybePreviousTemplatePromise = resolveSelect(
		OPTIONS_STORE_NAME
	).getOption( 'woocommerce_admin_customize_store_completed_theme_id' );

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

	const getTaskPromise = resolveSelect( ONBOARDING_STORE_NAME ).getTask(
		'customize-store'
	);

	const themeDataPromise = fetchThemeCards();

	const [
		currentTemplate,
		maybePreviousTemplate,
		styleRevs,
		rawPages,
		task,
		themeData,
	] = await Promise.all( [
		currentTemplatePromise,
		maybePreviousTemplatePromise,
		styleRevsPromise,
		hasModifiedPagesPromise,
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

	const hasModifiedPages = rawPages?.some(
		( page: { _links: { [ key: string ]: string[] } } ) => {
			return page._links?.[ 'version-history' ]?.length > 1;
		}
	);

	const activeThemeHasMods =
		!! currentTemplate?.modified ||
		styleRevs?.length > 0 ||
		hasModifiedPages;

	const customizeStoreTaskCompleted = task?.isComplete;

	return {
		activeThemeHasMods,
		customizeStoreTaskCompleted,
		themeData,
		currentThemeIsAiGenerated,
	};
};
