/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import Stack from '../stack';
import { productTypes } from '../constants';

describe( 'Stack', () => {
	it( 'should render stack with given product type and two links', () => {
		const { queryByText, queryAllByRole } = render(
			<Stack
				onClickLoadSampleProduct={ () => {} }
				items={ [
					{
						...productTypes[ 0 ],
						onClick: () => {},
					},
				] }
			/>
		);
		expect( queryByText( productTypes[ 0 ].title ) ).toBeInTheDocument();
		expect( queryAllByRole( 'link' ) ).toHaveLength( 2 );
	} );

	it( 'should not render other product options', () => {
		const { queryByText } = render(
			<Stack
				showOtherOptions={ false }
				onClickLoadSampleProduct={ () => {} }
				items={ [
					{
						...productTypes[ 0 ],
						onClick: () => {},
					},
				] }
			/>
		);

		expect( queryByText( 'Start Blank' ) ).not.toBeInTheDocument();
		expect( queryByText( 'Load Sample Products' ) ).not.toBeInTheDocument();
	} );

	it( 'should call onClickLoadSampleProduct when the "Load Sample Products" link is clicked', async () => {
		const onClickLoadSampleProduct = jest.fn();
		const { getByRole } = render(
			<Stack
				onClickLoadSampleProduct={ onClickLoadSampleProduct }
				items={ [
					{
						...productTypes[ 0 ],
						onClick: () => {},
					},
				] }
			/>
		);

		userEvent.click(
			getByRole( 'link', { name: 'Load Sample Products' } )
		);
		await waitFor( () => expect( onClickLoadSampleProduct ).toBeCalled() );
	} );
} );
