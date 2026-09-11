/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';

const mockRegisterBlockType = jest.fn();
const mockUnregisterBlockType = jest.fn();
const mockRegisterBlockVariation = jest.fn();
const mockUnregisterBlockVariation = jest.fn();
const mockUnsubscribe = jest.fn();
const mockRegisterProductBlockTypeCallSite = jest.fn();

let mockContextSubscription: () => void;
let mockTemplateSubscription: () => void;
let mockPostType: string;
let mockTemplateSlug: string;
let mockIsSiteEditor: boolean;

const getBlocksMock = () => ( {
	registerBlockType: mockRegisterBlockType,
	unregisterBlockType: mockUnregisterBlockType,
	registerBlockVariation: mockRegisterBlockVariation,
	unregisterBlockVariation: mockUnregisterBlockVariation,
} );

const getDataMock = () => ( {
	select: jest.fn( ( storeName: string ) => {
		if ( storeName === 'core/editor' ) {
			return {
				getCurrentPostType: () => mockPostType,
				getEditedPostSlug: () => mockTemplateSlug,
			};
		}

		if ( storeName === 'core/edit-site' ) {
			return mockIsSiteEditor ? {} : undefined;
		}

		return undefined;
	} ),
	subscribe: jest.fn( ( callback: () => void, storeName?: string ) => {
		if ( storeName === 'core/editor' ) {
			mockTemplateSubscription = callback;
		} else {
			mockContextSubscription = callback;
		}

		return mockUnsubscribe;
	} ),
} );

type RegisterProductBlockType =
	typeof import('../register-product-block-type').registerProductBlockType;

const blockSettings = {
	title: 'Test product block',
	category: 'woocommerce',
} as Partial< BlockConfiguration >;

const loadRegistrationFunction = (): RegisterProductBlockType => {
	let registerProductBlockType: RegisterProductBlockType | undefined;

	jest.isolateModules( () => {
		( { registerProductBlockType } = jest.requireActual(
			'../register-product-block-type'
		) );
	} );

	if ( ! registerProductBlockType ) {
		throw new Error( 'Expected registerProductBlockType to be loaded.' );
	}

	return registerProductBlockType;
};

describe( 'registerProductBlockType', () => {
	beforeEach( () => {
		jest.resetModules();
		jest.clearAllMocks();
		mockPostType = 'post';
		mockTemplateSlug = '';
		mockIsSiteEditor = false;
		mockContextSubscription = () => {
			throw new Error( 'Expected a context subscription.' );
		};
		mockTemplateSubscription = () => {
			throw new Error( 'Expected a template subscription.' );
		};
		jest.doMock( '@wordpress/blocks', getBlocksMock );
		jest.doMock( '@wordpress/data', getDataMock );
		jest.dontMock( '@woocommerce/atomic-utils' );
	} );

	it( 'registers only post-editor-enabled blocks with the Single Product ancestor', () => {
		const registerProductBlockType = loadRegistrationFunction();

		registerProductBlockType( 'woocommerce/post-enabled', {
			...blockSettings,
			isAvailableOnPostEditor: true,
		} );
		registerProductBlockType( 'woocommerce/post-disabled', {
			...blockSettings,
			isAvailableOnPostEditor: false,
		} );
		mockContextSubscription();

		expect( mockRegisterBlockType ).toHaveBeenCalledTimes( 1 );
		expect( mockRegisterBlockType ).toHaveBeenCalledWith(
			'woocommerce/post-enabled',
			expect.objectContaining( {
				ancestor: [ 'woocommerce/single-product' ],
			} )
		);
		expect( mockUnsubscribe ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'keeps the Single Product ancestor outside a Single Product template', () => {
		mockPostType = 'wp_template';
		mockTemplateSlug = 'twentytwentyfour//coming-soon';
		mockIsSiteEditor = true;
		const registerProductBlockType = loadRegistrationFunction();

		registerProductBlockType(
			'woocommerce/site-editor-block',
			blockSettings
		);
		mockContextSubscription();

		expect( mockRegisterBlockType ).toHaveBeenCalledTimes( 1 );
		expect( mockRegisterBlockType ).toHaveBeenCalledWith(
			'woocommerce/site-editor-block',
			expect.objectContaining( {
				ancestor: [ 'woocommerce/single-product' ],
			} )
		);
		expect( mockUnregisterBlockType ).not.toHaveBeenCalled();
	} );

	it( 're-registers a block with the ancestor required by each template', () => {
		mockPostType = 'wp_template';
		mockTemplateSlug = 'twentytwentyfour//coming-soon';
		mockIsSiteEditor = true;
		const registerProductBlockType = loadRegistrationFunction();

		registerProductBlockType(
			'woocommerce/transition-block',
			blockSettings
		);
		registerProductBlockType(
			'woocommerce/transition-block',
			blockSettings
		);
		mockContextSubscription();

		expect( mockRegisterBlockType ).toHaveBeenCalledTimes( 1 );

		mockTemplateSlug = 'woocommerce//single-product';
		mockTemplateSubscription();

		expect( mockUnregisterBlockType ).toHaveBeenNthCalledWith(
			1,
			'woocommerce/transition-block'
		);
		expect( mockRegisterBlockType ).toHaveBeenNthCalledWith(
			2,
			'woocommerce/transition-block',
			expect.objectContaining( { ancestor: undefined } )
		);

		mockTemplateSlug = 'twentytwentyfour//page';
		mockTemplateSubscription();

		expect( mockUnregisterBlockType ).toHaveBeenNthCalledWith(
			2,
			'woocommerce/transition-block'
		);
		expect( mockUnregisterBlockType ).toHaveBeenCalledTimes( 2 );
		expect( mockRegisterBlockType ).toHaveBeenCalledTimes( 3 );
		expect( mockRegisterBlockType ).toHaveBeenNthCalledWith(
			3,
			'woocommerce/transition-block',
			expect.objectContaining( {
				ancestor: [ 'woocommerce/single-product' ],
			} )
		);
	} );

	it( 're-registers variations when the template context changes', () => {
		mockPostType = 'wp_template';
		mockTemplateSlug = 'twentytwentyfour//coming-soon';
		mockIsSiteEditor = true;
		const registerProductBlockType = loadRegistrationFunction();
		const variationSettings = {
			name: 'related-products',
			title: 'Related products',
			isVariationBlock: true,
			variationName: 'related-products',
		};

		registerProductBlockType(
			'woocommerce/product-query',
			variationSettings
		);
		mockContextSubscription();

		expect( mockRegisterBlockVariation ).toHaveBeenCalledTimes( 1 );
		expect( mockRegisterBlockVariation ).toHaveBeenCalledWith(
			'woocommerce/product-query',
			expect.objectContaining( {
				name: 'related-products',
				title: 'Related products',
			} )
		);

		mockTemplateSlug = 'woocommerce//single-product';
		mockTemplateSubscription();

		expect( mockUnregisterBlockVariation ).toHaveBeenCalledWith(
			'woocommerce/product-query',
			'related-products'
		);
		expect( mockRegisterBlockVariation ).toHaveBeenCalledTimes( 2 );
		expect( mockRegisterBlockVariation ).toHaveBeenLastCalledWith(
			'woocommerce/product-query',
			expect.objectContaining( {
				name: 'related-products',
				title: 'Related products',
			} )
		);

		mockTemplateSlug = 'twentytwentyfour//page';
		mockTemplateSubscription();

		expect( mockUnregisterBlockVariation ).toHaveBeenNthCalledWith(
			2,
			'woocommerce/product-query',
			'related-products'
		);
		expect( mockUnregisterBlockVariation ).toHaveBeenCalledTimes( 2 );
		expect( mockRegisterBlockVariation ).toHaveBeenCalledTimes( 3 );
		expect( mockRegisterBlockVariation ).toHaveBeenLastCalledWith(
			'woocommerce/product-query',
			expect.objectContaining( {
				name: 'related-products',
				title: 'Related products',
			} )
		);
	} );
} );

describe( 'product block registration call sites', () => {
	beforeEach( () => {
		jest.resetModules();
		jest.clearAllMocks();
		jest.dontMock( '@wordpress/blocks' );
		jest.dontMock( '@wordpress/data' );
		jest.doMock( '@woocommerce/atomic-utils', () => ( {
			registerProductBlockType: mockRegisterProductBlockTypeCallSite,
		} ) );
	} );

	it( 'declares the post-editor availability of Product Price and Product Image Gallery', () => {
		jest.isolateModules( () => {
			jest.requireActual( '../../blocks/product-elements/price' );
			jest.requireActual(
				'../../blocks/product-elements/product-image-gallery'
			);
		} );

		expect( mockRegisterProductBlockTypeCallSite ).toHaveBeenCalledTimes(
			2
		);
		expect( mockRegisterProductBlockTypeCallSite ).toHaveBeenCalledWith(
			expect.objectContaining( { name: 'woocommerce/product-price' } ),
			expect.objectContaining( { isAvailableOnPostEditor: true } )
		);
		expect( mockRegisterProductBlockTypeCallSite ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woocommerce/product-image-gallery',
			} ),
			expect.objectContaining( { isAvailableOnPostEditor: false } )
		);
	} );
} );
