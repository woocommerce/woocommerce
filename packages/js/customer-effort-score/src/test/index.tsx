/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CustomerEffortScore } from '../customer-effort-score';

const noop = () => {};

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true,
		...originalModule,
		useDispatch: jest.fn().mockReturnValue( {
			createNotice: jest.fn(),
		} ),
	};
} );

describe( 'CustomerEffortScore', () => {
	it( 'should call createNotice with appropriate parameters', async () => {
		const mockCreateNotice = jest.fn();
		( useDispatch as jest.Mock ).mockReturnValue( {
			createNotice: mockCreateNotice,
		} );
		const icon = <span>icon</span>;

		render(
			<CustomerEffortScore
				recordScoreCallback={ noop }
				title={ 'title' }
				firstQuestion="First question"
				secondQuestion="Second question"
				onNoticeDismissedCallback={ noop }
				icon={ icon }
			/>
		);

		expect( mockCreateNotice ).toHaveBeenCalledWith(
			// Notice status.
			expect.any( String ),
			// Notice message.
			'title',
			// Notice options.
			expect.objectContaining( {
				icon,
				onDismiss: noop,
			} )
		);
	} );

	it( 'should not call createNotice on rerender', async () => {
		const mockCreateNotice = jest.fn();
		( useDispatch as jest.Mock ).mockReturnValue( {
			createNotice: mockCreateNotice,
		} );

		const { rerender } = render(
			<CustomerEffortScore
				recordScoreCallback={ noop }
				title={ 'title' }
				firstQuestion="First question"
				secondQuestion="Second question"
			/>
		);

		// Simulate rerender by changing label prop.
		rerender(
			<CustomerEffortScore
				recordScoreCallback={ noop }
				title={ 'title2' }
				firstQuestion="First question"
				secondQuestion="Second question"
			/>
		);

		expect( mockCreateNotice ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should not show dialog if no action is taken', async () => {
		render(
			<CustomerEffortScore
				recordScoreCallback={ noop }
				title={ 'title' }
				firstQuestion="First question"
				secondQuestion="Second question"
			/>
		);

		const dialog = screen.queryByRole( 'dialog' );
		expect( dialog ).toBeNull();
	} );

	it( 'should show dialog if "Give feedback" callback is run', async () => {
		const mockOnModalShownCallback = jest.fn();
		const createNotice = (
			...args: [
				unknown,
				unknown,
				{
					actions: [ { onClick: () => void } ];
				}
			]
		) => {
			// We're only interested in the 3rd argument.
			const { actions } = args[ 2 ];

			// Assuming the first action is the "Give feedback" action,
			// manually call callback.
			const callback = actions[ 0 ].onClick;
			if ( typeof callback === 'function' ) {
				callback();
			}

			// Modal shown callback should also be called.
			expect( mockOnModalShownCallback ).toHaveBeenCalled();
		};
		( useDispatch as jest.Mock ).mockReturnValue( {
			createNotice,
		} );

		render(
			<CustomerEffortScore
				recordScoreCallback={ noop }
				title={ 'title' }
				firstQuestion="First question"
				secondQuestion="Second question"
				onModalShownCallback={ mockOnModalShownCallback }
			/>
		);

		const dialog = screen.queryByRole( 'dialog' );
		expect( dialog ).not.toBeNull();
	} );
} );
