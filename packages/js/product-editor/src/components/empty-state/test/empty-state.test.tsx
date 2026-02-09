/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EmptyState } from '../empty-state';

describe( 'EmptyState', () => {
	it( 'should render empty name rows when names are empty', () => {
		const { container } = render( <EmptyState names={ [ '', '', '' ] } /> );
		const rows = container.querySelectorAll(
			'.woocommerce-product-empty-state__row'
		);
		expect( rows.length ).toBe( 3 );
		rows.forEach( ( row ) => {
			expect(
				row.querySelector( '.woocommerce-product-empty-state__name' )
			).toBeInTheDocument();
			expect(
				row.querySelector( '.woocommerce-product-empty-state__value' )
			).toBeInTheDocument();
			expect(
				row.querySelector( '.woocommerce-product-empty-state__actions' )
			).toBeInTheDocument();
		} );
	} );

	it( 'should render names when provided', () => {
		const names = [ 'Name 1', 'Name 2', 'Name 3' ];
		const { container, queryByText } = render(
			<EmptyState names={ names } />
		);
		const rows = container.querySelectorAll(
			'.woocommerce-product-empty-state__row'
		);
		expect( rows.length ).toBe( 3 );
		names.forEach( ( name ) => {
			expect( queryByText( name ) ).toBeInTheDocument();
		} );
	} );

	it( 'should render the correct number of rows based on the provided array', () => {
		const testCases = [
			{ names: [ 'Name 1', 'Name 2' ], expectedLength: 2 },
			{
				names: [ 'Name 1', 'Name 2', 'Name 3', 'Name 4' ],
				expectedLength: 4,
			},
		];

		testCases.forEach( ( { names, expectedLength } ) => {
			const { container } = render( <EmptyState names={ names } /> );
			const rows = container.querySelectorAll(
				'.woocommerce-product-empty-state__row'
			);
			expect( rows.length ).toBe( expectedLength );
		} );
	} );
} );
