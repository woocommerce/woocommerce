/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { Product } from '@woocommerce/data';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EditProductLinkModal } from '../';

jest.mock( '../../../hooks/use-product-helper', () => ( {
	useProductHelper: jest.fn().mockReturnValue( {
		updateProductWithStatus: jest.fn(),
		isUpdatingDraft: jest.fn(),
		isUpdatingPublished: jest.fn(),
	} ),
} ) );

describe( 'EditProductLinkModal', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should show a field with the permalink as label', () => {
		const { queryByText } = render(
			<EditProductLinkModal
				permalinkPrefix={ 'wootesting.com/product/' }
				permalinkSuffix={ '' }
				product={
					{
						slug: 'test',
						permalink: 'wootesting.com/product/test',
					} as Product
				}
				onCancel={ () => {} }
				onSaved={ () => {} }
				saveHandler={ () =>
					new Promise( () => ( {
						slug: 'test-slug',
						permalink: 'http://test-link',
					} ) )
				}
			/>
		);
		expect(
			queryByText( 'wootesting.com/product/test' )
		).toBeInTheDocument();
	} );

	it( 'should update the permalink label as the slug is being updated', () => {
		const { queryByText, getByLabelText } = render(
			<EditProductLinkModal
				permalinkPrefix={ 'wootesting.com/product/' }
				permalinkSuffix={ '' }
				product={
					{
						slug: 'test',
						permalink: 'wootesting.com/product/test',
					} as Product
				}
				onCancel={ () => {} }
				onSaved={ () => {} }
				saveHandler={ () =>
					new Promise( () => ( {
						slug: 'test-slug',
						permalink: 'http://test-link',
					} ) )
				}
			/>
		);
		userEvent.type(
			getByLabelText( 'Product link' ),
			'{esc}{space}update',
			{}
		);
		expect(
			queryByText( 'wootesting.com/product/test-update' )
		).toBeInTheDocument();
	} );

	it( 'should only update the end of the permalink incase the slug matches other parts of the url', () => {
		const { queryByText, getByLabelText } = render(
			<EditProductLinkModal
				permalinkPrefix={ 'wootesting.com/product/' }
				permalinkSuffix={ '' }
				product={
					{
						slug: 'product',
						permalink: 'wootesting.com/product/product',
					} as Product
				}
				onCancel={ () => {} }
				onSaved={ () => {} }
				saveHandler={ () =>
					new Promise( () => ( {
						slug: 'test-slug',
						permalink: 'http://test-link',
					} ) )
				}
			/>
		);
		userEvent.type(
			getByLabelText( 'Product link' ),
			'{esc}{space}update',
			{}
		);
		expect(
			queryByText( 'wootesting.com/product/product-update' )
		).toBeInTheDocument();
	} );
} );
