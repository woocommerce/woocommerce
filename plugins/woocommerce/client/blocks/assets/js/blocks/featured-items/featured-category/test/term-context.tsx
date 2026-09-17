/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import type { ComponentType } from 'react';
import type { WP_REST_API_Category as Category } from 'wp-types';

/**
 * Internal dependencies
 */
import FeaturedCategory from '../block';
import { useCoverImage } from '../use-cover-image';

const mockContext = jest.fn();
const mockInnerBlocks = jest.fn();
jest.mock( '@wordpress/block-editor', () => ( {
	BlockContextProvider: ( { value, children } ) => {
		mockContext( value );
		return children;
	},
	InnerBlocks: ( props ) => {
		mockInnerBlocks( props );
		return null;
	},
	BlockControls: ( { children } ) => children,
} ) );
jest.mock( '@woocommerce/block-hocs', () => ( {
	withCategory: ( Component: ComponentType ) => Component,
} ) );
jest.mock( '../../with-edit-mode', () => ( {
	withEditMode: () => ( Component: ComponentType ) => Component,
} ) );
jest.mock( '../../with-update-button-attributes', () => ( {
	withUpdateButtonAttributes: ( Component: ComponentType ) => Component,
} ) );
jest.mock( '../use-cover-image', () => ( { useCoverImage: jest.fn() } ) );

it( 'initializes an inherited Cover, shares term context and binds its button', () => {
	const category = {
		id: 42,
		permalink: '/category/books/',
	} as Category;
	const setAttributes = jest.fn();
	render(
		<FeaturedCategory
			name="woocommerce/featured-category"
			attributes={ {} }
			category={ category }
			effectiveCategoryId={ 42 }
			canEditItem={ false }
			clientId="featured-category"
			isLoading={ false }
			setAttributes={ setAttributes }
			useEditMode={ [ false, jest.fn() ] }
		/>
	);
	expect( setAttributes ).toHaveBeenCalledWith( { layout: 'cover' } );
	expect( useCoverImage ).toHaveBeenCalledWith(
		'featured-category',
		category
	);
	expect( mockContext ).toHaveBeenCalledWith( {
		termId: 42,
		termTaxonomy: 'product_cat',
		taxonomy: 'product_cat',
	} );
	const cover = mockInnerBlocks.mock.lastCall[ 0 ].template[ 0 ];
	expect( cover[ 0 ] ).toBe( 'core/cover' );
	expect( cover[ 2 ][ 2 ][ 2 ][ 0 ][ 1 ].metadata.bindings.url ).toEqual( {
		source: 'core/term-data',
		args: { field: 'link' },
	} );
	expect(
		screen.queryByRole( 'button', { name: 'Edit selected category' } )
	).not.toBeInTheDocument();
} );
