/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { InboxDismissConfirmationModal } from '../inbox-dismiss-confirmation-modal';

describe( 'InboxDismissConfirmationModal', () => {
	it( "should render with default button label - Yes, I'am sure", () => {
		const { queryByText } = render(
			<InboxDismissConfirmationModal
				onClose={ jest.fn() }
				onDismiss={ jest.fn() }
			/>
		);
		expect( queryByText( "Yes, I'm sure" ) ).toBeInTheDocument();
	} );

	it( 'should render passed in button label if provided', () => {
		const { queryByText } = render(
			<InboxDismissConfirmationModal
				onClose={ jest.fn() }
				onDismiss={ jest.fn() }
				buttonLabel="Custom button"
			/>
		);
		expect( queryByText( 'Custom button' ) ).toBeInTheDocument();
	} );

	it( 'should call onClose if Cancel is clicked', () => {
		const onClose = jest.fn();
		const { getByText } = render(
			<InboxDismissConfirmationModal
				onClose={ onClose }
				onDismiss={ jest.fn() }
			/>
		);
		userEvent.click( getByText( 'Cancel' ) );
		expect( onClose ).toHaveBeenCalled();
	} );

	it( 'should call onDismiss if dismiss button is clicked', () => {
		const onDismiss = jest.fn();
		const { getByText } = render(
			<InboxDismissConfirmationModal
				onClose={ jest.fn() }
				onDismiss={ onDismiss }
			/>
		);
		userEvent.click( getByText( "Yes, I'm sure" ) );
		expect( onDismiss ).toHaveBeenCalled();
	} );
} );
