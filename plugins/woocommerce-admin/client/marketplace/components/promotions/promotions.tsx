/* global userLocale, WC_marketplace_promotions */
/**
 * External dependencies
 */
import { useState, useEffect } from 'react';

/**
 * Internal dependencies
 */
import { LOCALE } from '../../../utils/admin-settings';
import Notice from '../notice/notice';

declare const WC_marketplace_promotions: {
	data: Promotion[];
};

type Promotion = {
	date_from_gmt: string;
	date_to_gmt: string;
	format: string;
	pages: Page[];
	position: string;
	content: { [ locale: string ]: string };
	icon?: string;
	is_dismissible?: boolean;
	menu_item_id?: string;
	style?: string;
};

type Page = {
	page: string;
	path: string;
	tab?: string;
};

const Promotions: React.FC = () => {
	const promotionsEndpoint =
		'https://woo.com/wp-json/wccom-extensions/3.0/promotions';
	const initialPromotions =
		typeof WC_marketplace_promotions !== 'undefined' &&
		WC_marketplace_promotions.data.length > 0
			? WC_marketplace_promotions.data
			: null;

	const [ fetchedPromotions, setFetchedPromotions ] = useState<
		Promotion[] | null
	>( initialPromotions );

	// Fallback to fetching promotions if initialPromotions is null, which should not happen.
	useEffect( () => {
		// Only fetch promotions if initialPromotions is null (indicating WC_marketplace_promotions was not set or empty)
		if ( initialPromotions === null ) {
			const fetchPromotions = async () => {
				try {
					const response = await fetch( promotionsEndpoint );
					if ( ! response.ok ) {
						throw new Error( 'Network response was not ok' );
					}
					const data = await response.json();
					setFetchedPromotions( data ); // Assuming the data is an array of promotions
				} catch ( error ) {
					console.error( 'Failed to fetch promotions:', error );
				}
			};

			fetchPromotions();
		}
	}, [ initialPromotions ] );

	// Determine the source of promotions to use, preferring locally available data
	const promotionsToUse = fetchedPromotions || initialPromotions;

	if ( ! promotionsToUse || promotionsToUse.length === 0 ) {
		return null;
	}

	const currentDate = new Date().toISOString();
	const urlParams = new URLSearchParams( window.location.search );
	const currentPage = urlParams.get( 'page' );
	const currentPath = decodeURIComponent( urlParams.get( 'path' ) || '' );
	const currentTab = urlParams.get( 'tab' );

	return (
		<>
			{ promotionsToUse.map( ( promotion, index ) => {
				// Skip this promotion if pages are not defined
				if ( ! promotion.pages ) {
					return null;
				}

				// Check if the current page, path & tab match the promotion's pages
				const matchesPagePath = promotion.pages.some( ( page ) => {
					const normalizedPagePath = page.path.startsWith( '/' )
						? page.path
						: `/${ page.path }`;
					const normalizedCurrentPath = currentPath.startsWith( '/' )
						? currentPath
						: `/${ currentPath }`;
					return (
						page.page === currentPage &&
						normalizedPagePath === normalizedCurrentPath &&
						( page.tab ? page.tab === currentTab : true )
					);
				} );

				if ( ! matchesPagePath ) {
					return null;
				}

				const startDate = new Date( promotion.date_from_gmt );
				const endDate = new Date( promotion.date_to_gmt );
				const now = new Date( currentDate );

				// Promotion is not active
				if ( now < startDate || now > endDate ) {
					return null;
				}

				// Promotion is a notice
				if ( promotion.format === 'notice' ) {
					return (
						<Notice
							key={ index }
							id={
								promotion.menu_item_id ?? `promotion-${ index }`
							}
							description={
								promotion.content[ LOCALE.userLocale ] ||
								promotion.content.en_US
							}
							variant={
								promotion.style ? promotion.style : 'info'
							}
							icon={ promotion.icon }
							isDismissible={ promotion.is_dismissible || false }
						/>
					);
				}
				return null;
			} ) }
		</>
	);
};

export default Promotions;
