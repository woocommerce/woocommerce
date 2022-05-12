/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CardLayout from '../card-layout';
import { productTypes } from '../constants';

describe( 'CardLayout', () => {
	it( 'should render all products types in CardLayout', () => {
		const { queryByText, queryAllByRole } = render(
			<CardLayout
				items={ [
					{
						...productTypes[ 0 ],
						onClick: () => {},
					},
				] }
			/>
		);

		expect( queryByText( productTypes[ 0 ].title ) ).toBeInTheDocument();

		expect( queryAllByRole( 'link' ) ).toHaveLength( 1 );
	} );
} );
