/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { ProductDataContextProvider } from '@woocommerce/shared-context';
import { ProductResponseItem } from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { Block } from '../block';

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );
	return {
		...originalModule,
		useSelect: jest.fn( () => ( {
			selectedProductType: {
				slug: 'simple',
			},
		} ) ),
	};
} );

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn().mockImplementation( ( param ) => {
		if ( param === 'wcBlocksConfig' ) {
			return {
				pluginUrl: '/mock-url/',
				productCount: 0,
				defaultAvatar: '',
				restApiRoutes: {},
				wordCountType: 'words',
			};
		}
		if ( param === 'attributes' ) {
			return [
				{
					attribute_id: '1',
					attribute_name: 'test',
					attribute_label: 'Test',
					attribute_orderby: 'menu_order',
					attribute_public: 1,
					attribute_type: 'select',
				},
			];
		}
		if ( param === 'stockStatusOptions' ) {
			return {
				instock: 'In stock',
				outofstock: 'Out of stock',
				onbackorder: 'On backorder',
			};
		}
		if ( param === 'productTypes' ) {
			return {
				simple: 'Simple product',
			};
		}
		if ( param === 'globalPaymentMethods' ) {
			return [];
		}
		return {};
	} ),
	getSettingWithCoercion: jest.fn().mockReturnValue( false ),
	STORE_PAGES: {
		shop: null,
		cart: null,
		checkout: null,
		myaccount: null,
		privacy: null,
		terms: null,
	},
	SITE_CURRENCY: {
		code: 'USD',
		symbol: '$',
		minorUnit: 2,
	},
	defaultFields: {
		first_name: '',
		last_name: '',
		company: '',
		address_1: '',
		address_2: '',
		city: '',
		state: '',
		postcode: '',
		country: '',
		phone: '',
		email: '',
	},
} ) );

jest.mock( '@woocommerce/base-hooks', () => ( {
	__esModule: true,
	useStyleProps: jest.fn( () => ( {
		className: '',
		style: {},
	} ) ),
} ) );

jest.mock( '@woocommerce/block-settings', () => ( {
	ADDRESS_FORM_KEYS: [
		'first_name',
		'last_name',
		'company',
		'address_1',
		'address_2',
		'city',
		'state',
		'postcode',
		'country',
		'phone',
		'email',
	],
	COUNTRY_LOCALE: {
		country: 'US',
		locale: 'en_US',
	},
	blocksConfig: {
		defaultAvatar: 'test-avatar-url',
	},
} ) );

const defaultProduct: ProductResponseItem = {
	name: 'Test Product',
	id: 1,
	type: 'simple',
	is_in_stock: true,
	is_on_backorder: false,
	stock_availability: {
		text: '',
		class: '',
	},
	parent: 0,
	permalink: '',
	images: [],
	variation: '',
	sku: '',
	short_description: '',
	description: '',
	on_sale: false,
	prices: {
		currency_code: 'USD',
		currency_symbol: '',
		currency_minor_unit: 0,
		currency_decimal_separator: '',
		currency_thousand_separator: '',
		currency_prefix: '',
		currency_suffix: '',
		price: '',
		regular_price: '',
		sale_price: '',
		price_range: null,
	},
	price_html: '',
	average_rating: '',
	review_count: 0,
	categories: [],
	tags: [],
	attributes: [],
	variations: [],
	has_options: false,
	is_purchasable: true,
	low_stock_remaining: null,
	sold_individually: false,
	add_to_cart: {
		text: '',
		description: '',
		url: '',
		minimum: 1,
		maximum: 99,
		multiple_of: 1,
	},
	slug: '',
};

describe( 'Stock Indicator Block', () => {
	beforeEach( () => {
		( getSetting as jest.Mock ).mockImplementation( ( setting ) => {
			if ( setting === 'productTypesWithoutStockIndicator' ) {
				return [ 'external', 'grouped', 'variable' ];
			}
			return undefined;
		} );
	} );
	it( 'should not show stock indicator when stock_availability is empty', () => {
		const product = {
			...defaultProduct,
		};

		const { container } = render(
			<ProductDataContextProvider product={ product } isLoading={ false }>
				<Block isDescendantOfAllProducts={ false } />
			</ProductDataContextProvider>
		);

		expect( container.firstChild ).toBeNull();
	} );

	it( 'should show stock indicator for out of stock products', () => {
		const product = {
			...defaultProduct,
			is_in_stock: false,
			stock_availability: {
				text: 'Out of stock',
				class: 'out-of-stock',
			},
		};

		const { container } = render(
			<ProductDataContextProvider product={ product } isLoading={ false }>
				<Block isDescendantOfAllProducts={ false } />
			</ProductDataContextProvider>
		);

		expect( container.firstChild ).not.toBeNull();
		expect( container.firstChild ).toHaveTextContent( 'Out of stock' );
	} );

	it( 'should show stock indicator for in stock products', () => {
		const product = {
			...defaultProduct,
			stock_availability: {
				text: 'In stock',
				class: 'in-stock',
			},
		};

		const { container } = render(
			<ProductDataContextProvider product={ product } isLoading={ false }>
				<Block isDescendantOfAllProducts={ false } />
			</ProductDataContextProvider>
		);

		expect( container.firstChild ).not.toBeNull();
		expect( container.firstChild ).toHaveTextContent( 'In stock' );
	} );

	it( 'should show stock indicator when is descendent of single product template', () => {
		const product = {
			...defaultProduct,
			id: 0,
			type: 'simple',
		};

		const { container } = render(
			<ProductDataContextProvider product={ product } isLoading={ false }>
				<Block isDescendantOfAllProducts={ false } />
			</ProductDataContextProvider>
		);

		expect( container.firstChild ).not.toBeNull();
		expect( container.firstChild ).toHaveTextContent( 'In stock' );
	} );
} );
