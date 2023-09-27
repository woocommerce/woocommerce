/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';

// placeholder xstate async service that returns a set of theme cards
export const fetchThemeCards = async () => {
	return [
		{
			slug: 'twentytwentyone',
			name: 'Twenty Twenty One',
			description: 'The default theme for WordPress.',
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/pub/twentytwentyone/screenshot.png',
			styleVariations: [],
			isActive: true,
		},
		{
			slug: 'twentytwenty',
			name: 'Twenty Twenty',
			description: 'The previous default theme for WordPress.',
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/pub/twentytwenty/screenshot.png',
			styleVariations: [],
		},
		{
			slug: 'tsubaki',
			name: 'Tsubaki',
			description:
				'Tsubaki puts the spotlight on your products and your customers. This theme leverages WooCommerce to provide you with intuitive product navigation and the patterns you need to master digital merchandising.',
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/premium/tsubaki/screenshot.png',
			styleVariations: [],
		},
		{
			slug: 'winkel',
			name: 'Winkel',
			description:
				'Winkel is a minimal, product-focused theme featuring Payments block. Its clean, cool look combined with a simple layout makes it perfect for showcasing fashion items – clothes, shoes, and accessories.',
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/pub/winkel/screenshot.png',
			styleVariations: [
				{
					title: 'Default',
					primary: '#ffffff',
					secondary: '#676767',
				},
				{
					title: 'Charcoal',
					primary: '#1f2527',
					secondary: '#9fd3e8',
				},
				{
					title: 'Rainforest',
					primary: '#eef4f7',
					secondary: '#35845d',
				},
				{
					title: 'Ruby Wine',
					primary: '#ffffff',
					secondary: '#c8133e',
				},
			],
		},
	];
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
		currentTemplate?.modified !== null ||
		styleRevs?.length > 0 ||
		hasModifiedPages.length > 0;
	const customizeStoreTaskCompleted = ( await getTask( 'customize-store' ) )
		?.isComplete;
	const themeCards = await fetchThemeCards();

	return {
		activeThemeHasMods,
		customizeStoreTaskCompleted,
		themeCards,
	};
};
