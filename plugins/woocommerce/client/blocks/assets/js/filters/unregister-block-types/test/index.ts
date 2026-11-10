/**
 * External dependencies
 */
import { getBlockTypes, unregisterBlockType } from '@wordpress/blocks';
import { getSetting } from '@woocommerce/settings';
import { select, subscribe } from '@wordpress/data';

jest.mock( '@woocommerce/utils', () =>
	jest.requireActual( '@woocommerce/utils/wordpress-stores' )
);

jest.mock( '@wordpress/data', () => ( {
	select: jest.fn(),
	subscribe: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn(),
	STORE_PAGES: { shop: { id: 42 } },
} ) );

jest.mock( '@wordpress/blocks', () => ( {
	getBlockTypes: jest.fn(),
	unregisterBlockType: jest.fn(),
} ) );

jest.mock( '@wordpress/dom-ready', () => ( {
	__esModule: true,
	default: jest.fn( ( callback ) => callback() ),
} ) );

const getCurrentPostId = jest.fn();
const unsubscribe = jest.fn();

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
	( getBlockTypes as jest.Mock ).mockReturnValue(
		blockTypes.map( ( name ) => ( { name } ) )
	);

	await jest.isolateModulesAsync( async () => {
		await import( '../index' );
	} );

	if ( getCurrentPostId() ) {
		jest.mocked( subscribe ).mock.calls[ 0 ]?.[ 0 ]();
	}
};

describe( 'unregister block types', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( getSetting as jest.Mock ).mockReturnValue( false );
		getCurrentPostId.mockReturnValue( 1 );
		( select as jest.Mock ).mockReturnValue( { getCurrentPostId } );
		( subscribe as jest.Mock ).mockReturnValue( unsubscribe );
	} );

	it.each( [
		[ 'the Shop page in a block theme', 42, true, 1 ],
		[ 'another page in a block theme', 43, true, 4 ],
		[ 'the Shop page in a classic theme', 42, false, 4 ],
	] )(
		'keeps catalog blocks registered only for the Shop page template preview (%s)',
		async ( _context, postId, isBlockTheme, expectedCount ) => {
			( getSetting as jest.Mock ).mockReturnValue( isBlockTheme );
			getCurrentPostId.mockReturnValue( postId );
			await loadFilter( 'post-php', [
				'woocommerce/breadcrumbs',
				'woocommerce/catalog-sorting',
				'woocommerce/product-results-count',
				'woocommerce/product-reviews',
			] );

			expect( unregisterBlockType ).toHaveBeenCalledTimes(
				expectedCount
			);
			expect( unregisterBlockType ).toHaveBeenCalledWith(
				'woocommerce/product-reviews'
			);
		}
	);

	it( 'waits for the Shop page to load before unregistering blocks', async () => {
		( getSetting as jest.Mock ).mockReturnValue( true );
		getCurrentPostId.mockReturnValue( undefined );
		await loadFilter( 'post-php', [
			'woocommerce/breadcrumbs',
			'woocommerce/product-reviews',
		] );
		expect( unregisterBlockType ).not.toHaveBeenCalled();

		getCurrentPostId.mockReturnValue( 42 );
		const [ onEditorChange ] = jest.mocked( subscribe ).mock.calls[ 0 ];
		onEditorChange();

		expect( unregisterBlockType ).toHaveBeenCalledTimes( 1 );
		expect( unregisterBlockType ).toHaveBeenCalledWith(
			'woocommerce/product-reviews'
		);
		expect( unsubscribe ).toHaveBeenCalledTimes( 1 );
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
