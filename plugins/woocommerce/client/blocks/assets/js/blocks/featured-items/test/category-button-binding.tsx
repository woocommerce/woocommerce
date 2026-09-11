/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import type { ComponentProps, ReactNode } from 'react';
import type { InnerBlockTemplate } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { withFeaturedItem } from '../with-featured-item';
import { FEATURED_CATEGORY_DEFAULT_TEMPLATE } from '../constants';

jest.mock(
	'../../product-collection/variations/elements/product-title',
	() => ( {
		VARIATION_NAME: 'woocommerce/product-title',
	} )
);
jest.mock( '@woocommerce/base-hooks', () => ( {
	useStyleProps: () => ( {} ),
} ) );
jest.mock( '@woocommerce/shared-context', () => ( {
	ProductDataContextProvider: ( { children }: { children: ReactNode } ) =>
		children,
} ) );
jest.mock( '../constrained-resizable', () => ( {
	ConstrainedResizable: () => null,
} ) );
jest.mock( '../use-background-image', () => ( {
	useBackgroundImage: () => ( {
		backgroundImageSrc: '',
		isImageBgTransparent: false,
		originalImgDimension: { width: 0, height: 0 },
	} ),
} ) );
jest.mock( '@wordpress/block-editor', () => ( {
	BlockContextProvider: ( {
		value,
		children,
	}: {
		value: Record< string, unknown >;
		children: ReactNode;
	} ) => (
		<div
			data-testid="category-context"
			data-context={ JSON.stringify( value ) }
		>
			{ children }
		</div>
	),
	InnerBlocks: ( { template }: { template: InnerBlockTemplate[] } ) => (
		<div
			data-testid="category-template"
			data-template={ JSON.stringify( template ) }
		/>
	),
} ) );

// The editor types Store API categories as Core REST terms.
const category = {
	id: 42,
	name: 'Clothing',
	permalink: 'https://example.com/product-category/clothing',
} as unknown as Parameters< typeof FEATURED_CATEGORY_DEFAULT_TEMPLATE >[ 0 ];

const TestComponent = withFeaturedItem( {
	emptyMessage: 'No category',
	icon: <span />,
	label: 'Featured Category',
	noSelectionButtonLabel: 'Select',
} )( () => null );

it.each( [ undefined, 0, 42 ] )(
	'supplies category context and the appropriate button for categoryId %s',
	( categoryId ) => {
		// The fixture supplies the runtime props consumed by this HOC.
		const props = {
			name: 'woocommerce/featured-category',
			attributes: {
				categoryId,
				contentAlign: 'center',
				focalPoint: { x: 0.5, y: 0.5 },
				minHeight: 500,
			},
			category,
			isLoading: false,
			setAttributes: jest.fn(),
			useEditingImage: [ false, jest.fn() ],
			useEditMode: [ false, jest.fn() ],
		} as unknown as ComponentProps< typeof TestComponent >;
		render( <TestComponent { ...props } /> );

		expect(
			JSON.parse(
				screen
					.getByTestId( 'category-context' )
					.getAttribute( 'data-context' ) || '{}'
			)
		).toEqual( {
			termId: 42,
			termTaxonomy: 'product_cat',
			taxonomy: 'product_cat',
		} );
		const template = JSON.parse(
			screen
				.getByTestId( 'category-template' )
				.getAttribute( 'data-template' ) || '[]'
		);
		const button = template[ 2 ][ 2 ][ 0 ];
		expect( button[ 0 ] ).toBe( 'core/button' );
		expect( button[ 1 ].url ).toBe( category.permalink );
		expect( button[ 1 ].metadata ).toEqual(
			categoryId
				? undefined
				: {
						bindings: {
							url: {
								source: 'core/term-data',
								args: { field: 'link' },
							},
						},
				  }
		);
		expect( props.setAttributes ).not.toHaveBeenCalled();
	}
);

it( 'keeps the existing selected-category template unbound by default', () => {
	const template = FEATURED_CATEGORY_DEFAULT_TEMPLATE( category );
	expect( template[ 2 ][ 2 ]?.[ 0 ] ).toEqual( [
		'core/button',
		{ text: 'Shop now', url: category.permalink },
	] );
} );
