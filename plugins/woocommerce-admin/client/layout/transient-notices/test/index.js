/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import TransientNotices from '..';

jest.mock( '@wordpress/data' );

useDispatch.mockReturnValue( {
	removeNotice: jest.fn(),
	createNotice: jest.fn(),
} );

jest.mock( '../snackbar/list', () =>
	jest.fn( ( { notices } ) => {
		return notices.map( ( notice ) => (
			<div key={ notice.title }>{ notice.title }</div>
		) );
	} )
);

describe( 'TransientNotices', () => {
	it( 'combines both notices and notices2 together and passes them to snackbar list', () => {
		useSelect.mockReturnValue( {
			notices: [ { title: 'first' } ],
			notices2: [ { title: 'second' } ],
		} );
		const { queryByText } = render( <TransientNotices /> );
		expect( queryByText( 'first' ) ).toBeInTheDocument();
		expect( queryByText( 'second' ) ).toBeInTheDocument();
	} );

	it( 'should default notices2 to empty array if undefined', () => {
		useSelect.mockReturnValue( {
			notices: [ { title: 'first' } ],
			notices2: undefined,
		} );
		const { queryByText } = render( <TransientNotices /> );
		expect( queryByText( 'first' ) ).toBeInTheDocument();
		expect( queryByText( 'second' ) ).not.toBeInTheDocument();
	} );

	it( 'should create notices from the queued notices', () => {
		useSelect.mockReturnValue( {
			noticesQueue: [
				{
					id: 'test-queued-notice',
					status: 'success',
					content: 'Test message',
				},
			],
			hasFinishedResolution: true,
		} );
		const createNotice = jest.fn();
		useDispatch.mockReturnValue( {
			createNotice,
		} );

		render( <TransientNotices /> );
		expect( createNotice ).toHaveBeenCalledWith(
			'success',
			'Test message',
			expect.anything()
		);
	} );
} );
