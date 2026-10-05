import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
const mockRegisterBlockType = vi.fn();
const mockUnregisterBlockType = vi.fn();
const mockRegisterBlockVariation = vi.fn();
const mockUnregisterBlockVariation = vi.fn();
const mockUnsubscribe = vi.fn();
const mockRegisterProductBlockTypeCallSite = vi.fn();
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
	select: vi.fn( ( storeName: string ) => {
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
	subscribe: vi.fn( ( callback: () => void, storeName?: string ) => {
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
const loadRegistrationFunction =
	async (): Promise< RegisterProductBlockType > => {
		let registerProductBlockType: RegisterProductBlockType | undefined;
		vi.resetModules(),
			await ( async () => {
				( { registerProductBlockType } = await vi.importActual(
					'../register-product-block-type'
				) );
			} )();
		if ( ! registerProductBlockType ) {
			throw new Error(
				'Expected registerProductBlockType to be loaded.'
			);
		}
		return registerProductBlockType;
	};
describe( 'registerProductBlockType', () => {
	beforeEach( () => {
		vi.resetModules();
		vi.clearAllMocks();
		mockPostType = 'post';
		mockTemplateSlug = '';
		mockIsSiteEditor = false;
		mockContextSubscription = () => {
			throw new Error( 'Expected a context subscription.' );
		};
		mockTemplateSubscription = () => {
			throw new Error( 'Expected a template subscription.' );
		};
		vi.doMock( '@wordpress/blocks', getBlocksMock );
		vi.doMock( '@wordpress/data', getDataMock );
		vi.doUnmock( '@woocommerce/utils/register-product-block-type' );
	} );
	it( 'registers only post-editor-enabled blocks with the Single Product ancestor', async () => {
		const registerProductBlockType = await loadRegistrationFunction();
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
	it( 'keeps the Single Product ancestor outside a Single Product template', async () => {
		mockPostType = 'wp_template';
		mockTemplateSlug = 'twentytwentyfour//coming-soon';
		mockIsSiteEditor = true;
		const registerProductBlockType = await loadRegistrationFunction();
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
	it( 're-registers a block with the ancestor required by each template', async () => {
		mockPostType = 'wp_template';
		mockTemplateSlug = 'twentytwentyfour//coming-soon';
		mockIsSiteEditor = true;
		const registerProductBlockType = await loadRegistrationFunction();
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
			expect.objectContaining( {
				ancestor: undefined,
			} )
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
	it( 're-registers variations when the template context changes', async () => {
		mockPostType = 'wp_template';
		mockTemplateSlug = 'twentytwentyfour//coming-soon';
		mockIsSiteEditor = true;
		const registerProductBlockType = await loadRegistrationFunction();
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
		vi.resetModules();
		vi.clearAllMocks();
		vi.doUnmock( '@wordpress/blocks' );
		vi.doUnmock( '@wordpress/data' );
		vi.doMock( '@woocommerce/utils/register-product-block-type', () => {
			const mock = {
				registerProductBlockType: mockRegisterProductBlockTypeCallSite,
			};
			return Object.defineProperties(
				{
					default: mock,
				},
				Object.getOwnPropertyDescriptors( mock )
			);
		} );
	} );
	it( 'declares the post-editor availability of Product Price and Product Image Gallery', async () => {
		vi.resetModules(),
			await ( async () => {
				await vi.importActual(
					'../../blocks/product-elements-blocks/price'
				);
				await vi.importActual(
					'../../blocks/product-elements-blocks/product-image-gallery'
				);
			} )();
		expect( mockRegisterProductBlockTypeCallSite ).toHaveBeenCalledTimes(
			2
		);
		expect( mockRegisterProductBlockTypeCallSite ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woocommerce/product-price',
			} ),
			expect.objectContaining( {
				isAvailableOnPostEditor: true,
			} )
		);
		expect( mockRegisterProductBlockTypeCallSite ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woocommerce/product-image-gallery',
			} ),
			expect.objectContaining( {
				isAvailableOnPostEditor: false,
			} )
		);
	}, 15000 );
	it( 'declares the deprecated Related Products block unavailable in the post editor and hidden from the inserter', async () => {
		// The editor components load the block editor and the Product Query
		// variations, which log to the console and aren't part of registration.
		vi.doMock(
			'../../blocks/product-elements-blocks/related-products/edit',
			() => {
				const mock = () => null;
				return {
					default: mock,
					...mock,
				};
			}
		);
		vi.doMock(
			'../../blocks/product-elements-blocks/related-products/save',
			() => {
				const mock = () => null;
				return {
					default: mock,
					...mock,
				};
			}
		);
		vi.resetModules(),
			await ( async () => {
				await vi.importActual(
					'../../blocks/product-elements-blocks/related-products'
				);
			} )();
		expect( mockRegisterProductBlockTypeCallSite ).toHaveBeenCalledTimes(
			1
		);
		expect( mockRegisterProductBlockTypeCallSite ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woocommerce/related-products',
				supports: expect.objectContaining( {
					inserter: false,
				} ),
			} ),
			expect.objectContaining( {
				isAvailableOnPostEditor: false,
			} )
		);
	} );
	it( 'declares the Product Details block available in the post editor', async () => {
		// The editor components pull in the block editor, which logs a duplicate Yjs
		// import when loaded inside isolateModules, and they aren't part of registration.
		vi.doMock( '../../blocks/product-details/edit', () => {
			const mock = () => null;
			return {
				default: mock,
				...mock,
			};
		} );
		vi.doMock( '../../blocks/product-details/save', () => {
			const mock = () => null;
			return {
				default: mock,
				...mock,
			};
		} );
		vi.resetModules(),
			await ( async () => {
				await vi.importActual( '../../blocks/product-details' );
			} )();
		expect( mockRegisterProductBlockTypeCallSite ).toHaveBeenCalledTimes(
			1
		);
		expect( mockRegisterProductBlockTypeCallSite ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woocommerce/product-details',
			} ),
			expect.objectContaining( {
				isAvailableOnPostEditor: true,
			} )
		);
	} );
} );
