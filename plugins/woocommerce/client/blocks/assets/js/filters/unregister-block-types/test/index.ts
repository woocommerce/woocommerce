/**
 * External dependencies
 */
import { getBlockTypes, unregisterBlockType } from '@wordpress/blocks';
import { getSetting } from '@woocommerce/settings';

jest.mock( '@woocommerce/settings', () => ( {
	STORE_PAGES: { shop: { id: 123 } },
	getSetting: jest.fn(),
} ) );

jest.mock( '@wordpress/blocks', () => ( {
	getBlockTypes: jest.fn(),
	unregisterBlockType: jest.fn(),
} ) );

jest.mock( '@wordpress/dom-ready', () => ( {
	__esModule: true,
	default: jest.fn( ( callback ) => callback() ),
} ) );

const loadFilter = (
	adminPage: string | undefined,
	blockTypes: string[],
	pageNow?: string
) => {
	const wordpressWindow = window as Window & {
		adminpage?: string;
		pagenow?: string;
	};
	wordpressWindow.adminpage = adminPage;
	wordpressWindow.pagenow = pageNow;
	( getBlockTypes as jest.Mock ).mockReturnValue(
		blockTypes.map( ( name ) => ( { name } ) )
	);

	jest.isolateModules( () => {
		jest.requireActual( '../index' );
	} );
};

describe( 'unregister block types', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( getSetting as jest.Mock ).mockReturnValue( true );
	} );

	it.each( [ true, false ] )(
		'preserves catalog template blocks only on the Shop page under block themes (%s)',
		( isBlockTheme ) => {
			const originalUrl = window.location.href;
			window.history.replaceState(
				null,
				'',
				'/wp-admin/post.php?post=123&action=edit'
			);
			( getSetting as jest.Mock ).mockReturnValue( isBlockTheme );

			try {
				loadFilter( 'post-php', [
					'woocommerce/breadcrumbs',
					'woocommerce/catalog-sorting',
					'woocommerce/product-results-count',
					'woocommerce/product-reviews',
				] );

				expect( unregisterBlockType ).toHaveBeenCalledTimes(
					isBlockTheme ? 1 : 4
				);
				expect( unregisterBlockType ).toHaveBeenCalledWith(
					'woocommerce/product-reviews'
				);
			} finally {
				window.history.replaceState( null, '', originalUrl );
			}
		}
	);

	it.each( [ 'post-php', 'post-new-php' ] )(
		'unregisters only post-editor block types in the deny list in %s',
		( adminPage ) => {
			loadFilter( adminPage, [
				'woocommerce/breadcrumbs',
				'woocommerce/catalog-sorting',
				'woocommerce/product-results-count',
				'woocommerce/product-reviews',
				'woocommerce/product-search',
				'myplugin/client-only',
			] );

			expect( unregisterBlockType ).toHaveBeenCalledTimes( 4 );
			expect( unregisterBlockType ).toHaveBeenCalledWith(
				'woocommerce/breadcrumbs'
			);
			expect( unregisterBlockType ).toHaveBeenCalledWith(
				'woocommerce/catalog-sorting'
			);
			expect( unregisterBlockType ).toHaveBeenCalledWith(
				'woocommerce/product-results-count'
			);
			expect( unregisterBlockType ).toHaveBeenCalledWith(
				'woocommerce/product-reviews'
			);
			expect( unregisterBlockType ).not.toHaveBeenCalledWith(
				'woocommerce/product-search'
			);
			expect( unregisterBlockType ).not.toHaveBeenCalledWith(
				'myplugin/client-only'
			);
		}
	);

	it.each( [
		[ 'widgets.php', 'widgets-php', undefined ],
		[ 'the Customizer', undefined, 'customize' ],
	] )(
		'unregisters WooCommerce blocks outside the widget-editor allow list in %s',
		( _context, adminPage, pageNow ) => {
			loadFilter(
				adminPage,
				[
					'woocommerce/product-search',
					'woocommerce/product-filters',
					'woocommerce/cart',
					'woocommerce/checkout',
					'woocommerce/order-confirmation-status',
					'woocommerce/new-widget-compatible-block',
					'myplugin/client-only',
				],
				pageNow
			);

			expect( unregisterBlockType ).toHaveBeenCalledTimes( 4 );
			expect( unregisterBlockType ).toHaveBeenCalledWith(
				'woocommerce/cart'
			);
			expect( unregisterBlockType ).toHaveBeenCalledWith(
				'woocommerce/checkout'
			);
			expect( unregisterBlockType ).toHaveBeenCalledWith(
				'woocommerce/order-confirmation-status'
			);
			expect( unregisterBlockType ).not.toHaveBeenCalledWith(
				'woocommerce/product-search'
			);
			expect( unregisterBlockType ).not.toHaveBeenCalledWith(
				'woocommerce/product-filters'
			);
			expect( unregisterBlockType ).toHaveBeenCalledWith(
				'woocommerce/new-widget-compatible-block'
			);
			expect( unregisterBlockType ).not.toHaveBeenCalledWith(
				'myplugin/client-only'
			);
		}
	);

	it.each( [ 'site-editor-php', undefined ] )(
		'does not unregister blocks in unrestricted editor contexts (%s)',
		( adminPage ) => {
			loadFilter( adminPage, [
				'woocommerce/breadcrumbs',
				'woocommerce/catalog-sorting',
				'woocommerce/checkout',
				'woocommerce/product-results-count',
				'myplugin/client-only',
			] );

			expect( unregisterBlockType ).not.toHaveBeenCalled();
		}
	);
} );
