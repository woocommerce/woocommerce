/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
import apiFetch from '@wordpress/api-fetch';

// placeholder xstate async service that returns a set of theme cards
export const fetchThemeCards = async () => {
	const themes = await apiFetch( {
		path: '/wc-admin/onboarding/themes/recommended',
		method: 'GET',
	} );

	return themes;
};

export const fetchIntroData = async () => {
	const currentTemplate = await resolveSelect(
		coreStore
		// @ts-expect-error No types for this exist yet.
	).__experimentalGetTemplateForLink( '/' );

	const styleRevs = await resolveSelect(
		coreStore
		// @ts-expect-error No types for this exist yet.
	).getCurrentThemeGlobalStylesRevisions();

	const hasModifiedPages = (
		await resolveSelect( coreStore )
			// @ts-expect-error No types for this exist yet.
			.getEntityRecords( 'postType', 'page', {
				per_page: 100,
				_fields: [ 'id', '_links.version-history' ],
				orderby: 'menu_order',
				order: 'asc',
			} )
	 )?.some( ( page: { _links: { [ key: string ]: string[] } } ) => {
		return page._links?.[ 'version-history' ]?.length > 1;
	} );

	const { getTask } = resolveSelect( ONBOARDING_STORE_NAME );

	const activeThemeHasMods =
		!! currentTemplate?.modified ||
		styleRevs?.length > 0 ||
		hasModifiedPages;
	const customizeStoreTaskCompleted = ( await getTask( 'customize-store' ) )
		?.isComplete;
	const themeData = await fetchThemeCards();

	return {
		activeThemeHasMods,
		customizeStoreTaskCompleted,
		themeData,
	};
};
