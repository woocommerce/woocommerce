/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import type { ComponentProps } from 'react';

/**
 * Internal dependencies
 */
import { withEditMode } from '../with-edit-mode';
import { useFeaturedItemStatus } from '../use-featured-item-status';

jest.mock( '../constants', () => ( {
	BLOCK_NAMES: {
		featuredProduct: 'woocommerce/featured-product',
		featuredCategory: 'woocommerce/featured-category',
	},
} ) );
jest.mock( '../use-featured-item-status', () => ( {
	useFeaturedItemStatus: jest.fn( () => ( {
		status: null,
		isDeleted: false,
		isLoading: false,
	} ) ),
} ) );
jest.mock( '@woocommerce/base-hooks', () => ( {
	usePreviewMode: () => false,
} ) );
jest.mock(
	'@woocommerce/editor-components/product-category-control',
	() => () => null
);
jest.mock( '@woocommerce/editor-components/product-control', () => () => null );
jest.mock( '@wordpress/components', () => ( {
	Placeholder: () => <div>Category picker</div>,
	Icon: () => null,
	Button: () => null,
	__experimentalHStack: () => null,
	__experimentalText: () => null,
} ) );

const TestComponent = withEditMode( {
	description: 'Category',
	editLabel: 'Edit',
	icon: <span />,
	label: 'Featured Category',
} )( () => <div>Featured category preview</div> );

it.each( [
	[ undefined, undefined, 'Category picker', undefined ],
	[ undefined, 42, 'Featured category preview', 42 ],
	[ 7, 42, 'Featured category preview', 7 ],
] )(
	'chooses the initial edit mode for selected %s and inherited %s categories',
	( categoryId, effectiveCategoryId, text, expectedId ) => {
		// This HOC fixture omits block registration metadata such as save and icon.
		const props = {
			name: 'woocommerce/featured-category',
			attributes: { categoryId, mediaId: 0, mediaSrc: '' },
			effectiveCategoryId,
			clientId: 'featured-category',
			setAttributes: jest.fn(),
			debouncedSpeak: jest.fn(),
			triggerUrlUpdate: jest.fn(),
			isLoading: false,
		} as unknown as ComponentProps< typeof TestComponent >;
		render( <TestComponent { ...props } /> );

		expect( screen.getByText( text ) ).toBeInTheDocument();
		expect( useFeaturedItemStatus ).toHaveBeenLastCalledWith( {
			itemId: expectedId,
			itemType: 'woocommerce/featured-category',
		} );
	}
);
