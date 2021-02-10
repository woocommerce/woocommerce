/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

/**
 * Internal dependencies
 */
import { Products, ProductTemplateModal } from '../tasks/products';

describe( 'products', () => {
	describe( 'Products', () => {
		afterEach( () => jest.clearAllMocks() );

		it( 'should render 4 different options to add products', () => {
			render( <Products /> );

			expect(
				screen.queryByText( 'Start with a template' )
			).toBeInTheDocument();
			expect( screen.queryByText( 'Add manually' ) ).toBeInTheDocument();
			expect(
				screen.queryByText( 'Import via CSV' )
			).toBeInTheDocument();
			expect(
				screen.queryByText( 'Import from another service' )
			).toBeInTheDocument();
		} );

		it( 'should not render the product template modal right away', () => {
			render( <Products /> );

			expect( screen.queryByText( '[ProductTemplateModal]' ) ).toBeNull();
		} );

		it( 'should render product template modal when start with template task is selected', () => {
			render( <Products /> );

			fireEvent(
				screen.queryByText( 'Start with a template' ),
				// eslint-disable-next-line no-undef
				new MouseEvent( 'click', { bubbles: true } )
			);
			expect(
				screen.queryByText( 'Physical product' )
			).toBeInTheDocument();
			expect(
				screen.queryByText( 'Digital product' )
			).toBeInTheDocument();
			expect(
				screen.queryByText( 'Variable product' )
			).toBeInTheDocument();
		} );

		it( 'should allow the user to close the template modal', () => {
			render( <Products /> );

			fireEvent(
				screen.queryByText( 'Start with a template' ),
				// eslint-disable-next-line no-undef
				new MouseEvent( 'click', { bubbles: true } )
			);
			expect(
				screen.queryByText( 'Physical product' )
			).toBeInTheDocument();
			const closeButton = screen.getByRole( 'button', {
				name: 'Close dialog',
			} );
			fireEvent.click( closeButton );
			expect(
				screen.queryByText( 'Physical product' )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'ProductWithTemplate', () => {
		afterEach( () => jest.clearAllMocks() );

		it( 'should render 3 different product types', () => {
			render( <ProductTemplateModal /> );

			expect(
				screen.queryByText( 'Physical product' )
			).toBeInTheDocument();
			expect(
				screen.queryByText( 'Digital product' )
			).toBeInTheDocument();
			expect(
				screen.queryByText( 'Variable product' )
			).toBeInTheDocument();
		} );
	} );
} );
