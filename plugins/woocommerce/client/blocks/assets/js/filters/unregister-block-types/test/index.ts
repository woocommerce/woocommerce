import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { getBlockTypes, unregisterBlockType } from '@wordpress/blocks';
vi.mock( '@wordpress/blocks', () => {
	const mock = {
		getBlockTypes: vi.fn(),
		unregisterBlockType: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/dom-ready', () => {
	const mock = {
		__esModule: true,
		default: vi.fn( ( callback ) => callback() ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const loadFilter = async (
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
	( getBlockTypes as Mock ).mockReturnValue(
		blockTypes.map( ( name ) => ( {
			name,
		} ) )
	);
	vi.resetModules(),
		await ( async () => {
			await import( '../index' );
		} )();
};
describe( 'unregister block types', () => {
	beforeEach( () => {
		vi.clearAllMocks();
	} );
	it.each( [ 'post-php', 'post-new-php' ] )(
		'unregisters only post-editor block types in the deny list in %s',
		async ( adminPage ) => {
			await loadFilter( adminPage, [
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
		async ( _context, adminPage, pageNow ) => {
			await loadFilter(
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
		async ( adminPage ) => {
			await loadFilter( adminPage, [
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
