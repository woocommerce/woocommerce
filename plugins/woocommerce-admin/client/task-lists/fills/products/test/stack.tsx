/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import Stack from '../stack';
import { productTypes } from '../constants';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

describe( 'Stack', () => {
	beforeEach( () => {
		( recordEvent as jest.Mock ).mockClear();
	} );

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

	it( 'should fire the tasklist_add_product and task_completion_time events when the "Start Blank" link is clicked', async () => {
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

		userEvent.click( getByRole( 'link', { name: 'Start Blank' } ) );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			1,
			'tasklist_add_product',
			{ method: 'manually' }
		);
		expect( recordEvent ).toHaveBeenNthCalledWith(
			2,
			'task_completion_time',
			{ task_name: 'products', time: '0-2s' }
		);
	} );
} );
