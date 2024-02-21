/* global userLocale */
/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { LOCALE } from '../../../utils/admin-settings';
import Notice from '../notice/notice';

declare global {
	interface Window {
		wc: {
			marketplace: {
				promotions: Promotion[];
			};
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

const Promotions: React.FC = () => {
	const urlParams = new URLSearchParams( window.location.search );
	const currentPage = urlParams.get( 'page' );

	// Check if the current page is not 'wc-admin'
	if ( currentPage !== 'wc-admin' ) {
		return null;
	}
	const promotions = window.wc?.marketplace?.promotions ?? [];
	const currentDate = new Date().toISOString();
	const currentPath = decodeURIComponent( urlParams.get( 'path' ) || '' );
	const currentTab = urlParams.get( 'tab' );

	return (
		<>
			{ promotions.map( ( promotion, index ) => {
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
