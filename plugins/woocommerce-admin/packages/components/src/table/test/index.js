/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { toHaveClass } from '@testing-library/jest-dom/matchers';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import TableCard from '../index';
import mockHeaders from './data/table-mock-headers';
import mockData from './data/table-mock-data';

expect.extend( { toHaveClass } );

describe( 'TableCard', () => {
	it( 'should render placeholder table while loading', () => {
		render(
			<TableCard
				title="Revenue"
				headers={ mockHeaders }
				isLoading={ true }
				rows={ [] }
				rowsPerPage={ 5 }
				totalRows={ 5 }
			/>
		);

		expect( screen.getByRole( 'group', { hidden: true } ) ).toHaveClass(
			'is-loading'
		);
	} );

	it( 'should not render placeholder table when not loading', () => {
		render(
			<TableCard
				title="Revenue"
				headers={ mockHeaders }
				isLoading={ false }
				rows={ mockData }
				rowsPerPage={ 5 }
				totalRows={ 5 }
			/>
		);

		expect( screen.getByRole( 'group' ) ).not.toHaveClass( 'is-loading' );
	} );

	it( 'should not error with default callback props', () => {
		render(
			<TableCard
				title="Revenue"
				headers={ mockHeaders }
				isLoading={ false }
				rows={ mockData }
				rowsPerPage={ 1 }
				totalRows={ 5 }
			/>
		);

		// Trigger a query change (next page).
		userEvent.click(
			screen.getByLabelText( 'Next Page', { selector: 'button' } )
		);

		// Trigger a column change.
		userEvent.click(
			screen.getByTitle( 'Choose which values to display', {
				selector: 'button',
			} )
		);

		// We shouldn't get here if an error occurred.
		expect( true ).toBe( true );
	} );

	it( 'should render rows correctly with custom rowKey prop', () => {
		render(
			<TableCard
				title="Revenue"
				headers={ mockHeaders }
				isLoading={ false }
				rows={ mockData }
				rowsPerPage={ 1 }
				totalRows={ 5 }
				rowKey={ ( row ) => row[ 1 ].value }
			/>
		);

		for ( const row of mockData ) {
			expect(
				screen.queryByText( row[ 0 ].display )
			).toBeInTheDocument();
		}
	} );
} );
