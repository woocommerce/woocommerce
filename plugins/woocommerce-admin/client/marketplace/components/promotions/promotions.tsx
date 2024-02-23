/**
 * Internal dependencies
 */
import { LOCALE } from '../../../utils/admin-settings';
import Notice from '../notice/notice';

declare global {
	interface Window {
		wcMarketplace?: {
			promotions?: Promotion[];
		};
	}
}

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

const Promotions: () => null | JSX.Element = () => {
	const urlParams = new URLSearchParams( window.location.search );
	const currentPage = urlParams.get( 'page' );

	// Check if the current page is not 'wc-admin'
	if ( currentPage !== 'wc-admin' ) {
		return null;
	}
	const promotions = window?.wcMarketplace?.promotions ?? [];
	const currentDateUTC = Date.now();
	const currentPath = decodeURIComponent( urlParams.get( 'path' ) || '' );
	const currentTab = urlParams.get( 'tab' );

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
						const normalizedPath = page.path.startsWith( '/' )
							? page.path
							: `/${ page.path }`;
						const normalizedCurrentPath = currentPath.startsWith(
							'/'
						)
							? currentPath
							: `/${ currentPath }`;

						return page.page === currentPage &&
							normalizedPath === normalizedCurrentPath &&
							page.tab
							? page.tab === currentTab
							: ! currentTab;
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
