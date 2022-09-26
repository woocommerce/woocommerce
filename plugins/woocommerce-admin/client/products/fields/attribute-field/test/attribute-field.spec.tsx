/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { AttributeField } from '../attribute-field';

describe( 'AttributeField', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'empty state', () => {
		it( 'should show subtitle and "Add first attribute" button', () => {
			const { queryByText } = render(
				<AttributeField value={ [] } onChange={ () => {} } />
			);
			expect( queryByText( 'No attributes yet' ) ).toBeInTheDocument();
			expect( queryByText( 'Add first attribute' ) ).toBeInTheDocument();
		} );
	} );
} );
