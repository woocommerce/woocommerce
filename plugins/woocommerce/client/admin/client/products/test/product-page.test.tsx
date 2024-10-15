/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';
import { TRACKS_SOURCE } from '@woocommerce/product-editor';
import { useParams } from 'react-router-dom';

/**
 * Internal dependencies
 */
import ProductPage from '../product-page';
import ProductVariationPage from '../product-variation-page';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );
jest.mock( 'react-router-dom', () => ( { useParams: jest.fn() } ) );

// Mocks to prevent crashes.
jest.mock( '@wordpress/api-fetch', () => ( {
	apiFetch: jest.fn(),
} ) );
jest.mock( '@wordpress/core-data', () => ( {
	apiFetch: jest.fn(),
} ) );
jest.mock( '../hooks/use-product-entity-record', () => ( {
	useProductEntityRecord: jest.fn(),
} ) );
jest.mock( '../hooks/use-product-variation-entity-record', () => ( {
	useProductVariationEntityRecord: jest.fn(),
} ) );
jest.mock( '@woocommerce/product-editor', () => ( {
	...jest.requireActual( '@woocommerce/product-editor' ),
	productEditorHeaderApiFetchMiddleware: jest.fn(),
	productApiFetchMiddleware: jest.fn(),
	__experimentalInitBlocks: jest.fn().mockImplementation( () => () => {} ),
} ) );

describe( 'ProductPage', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );
	it( 'should trigger product_add_view on render without product_id defined', () => {
		( useParams as jest.Mock ).mockReturnValue( { productId: null } );
		render( <ProductPage /> );
		expect( recordEvent ).toBeCalledWith( 'product_add_view', {
			source: TRACKS_SOURCE,
		} );
	} );
	it( 'should trigger product_edit_view on render with product_id defined', () => {
		( useParams as jest.Mock ).mockReturnValue( { productId: 1 } );
		render( <ProductPage /> );
		expect( recordEvent ).toBeCalledWith( 'product_edit_view', {
			source: TRACKS_SOURCE,
			product_id: 1,
		} );
	} );
} );

describe( 'ProductVariationPage', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );
	it( 'should trigger product_add_view track event on render without product_id defined', () => {
		( useParams as jest.Mock ).mockReturnValue( { productId: null } );
		render( <ProductVariationPage /> );
		expect( recordEvent ).toBeCalledWith( 'product_add_view', {
			source: TRACKS_SOURCE,
		} );
	} );
	it( 'should trigger product_edit_view track event on render with product_id defined', () => {
		( useParams as jest.Mock ).mockReturnValue( { productId: 1 } );
		render( <ProductVariationPage /> );
		expect( recordEvent ).toBeCalledWith( 'product_edit_view', {
			source: TRACKS_SOURCE,
			product_id: 1,
		} );
	} );
} );
