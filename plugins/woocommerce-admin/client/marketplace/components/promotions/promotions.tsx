/**
 * Internal dependencies
 */
import { LOCALE } from '../../../utils/admin-settings';
import Notice from '../notice/notice';
import { Page, Promotion } from './types';
import PromoCard from '../promo-card/promo-card';

declare global {
	interface Window {
		wcMarketplace?: {
			promotions?: Promotion[];
		};
	}
}

const Promotions: ( {
	format,
}: {
	format?: string;
} ) => null | JSX.Element = ( { format = 'notice' } ) => {
	const urlParams = new URLSearchParams( window.location.search );
	const currentPage = urlParams.get( 'page' );

	const promotions = ( window?.wcMarketplace?.promotions ?? [] ).filter(
		( x ) => x.format === format
	);
	const currentDateUTC = Date.now();
	const currentPath = decodeURIComponent( urlParams.get( 'path' ) || '' );
	const currentTab = urlParams.get( 'tab' );
	const pathname = window.location.pathname + window.location.search;

	return (
		<>
			{ promotions.map( ( promotion: Promotion, index: number ) => {
				// Skip this promotion if pages are not defined
				if ( ! promotion.pages ) {
					return null;
				}

				// Check if the current page, path & tab match the promotion's pages
				const matchesPagePath = promotion.pages.some(
					( page: Page ) => {
						if ( page.pathname ) {
							return page.pathname === pathname;
						}

						if ( ! page.path ) {
							return false;
						}

						const normalize = ( path: string ) =>
							path.startsWith( '/' ) ? path : `/${ path }`;
						const normalizedPath = normalize( page.path );
						const normalizedCurrentPath = normalize( currentPath );

						return (
							page.page === currentPage &&
							normalizedPath === normalizedCurrentPath &&
							( page.tab ? currentTab : ! currentTab )
						);
					}
				);

				if ( ! matchesPagePath ) {
					return null;
				}

				const startDate = new Date( promotion.date_from_gmt ).getTime();
				const endDate = new Date( promotion.date_to_gmt ).getTime();

				// Promotion is not active
				if ( currentDateUTC < startDate || currentDateUTC > endDate ) {
					return null;
				}

				// Promotion is a promo
				if ( promotion.format === 'promo-card' ) {
					return <PromoCard key={ index } promotion={ promotion } />;
				}

				// Promotion is a notice
				if ( promotion.format === 'notice' ) {
					if ( ! promotion?.content ) {
						return null;
					}

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
							icon={ promotion?.icon || '' }
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
