/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { FeedbackBar } from '../feedback-bar';
import { useFeedbackBar } from '../../../hooks/use-feedback-bar';

jest.mock( '../../../hooks/use-feedback-bar', () => ( {
	...jest.requireActual( '../../../hooks/use-feedback-bar' ),
	useFeedbackBar: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	...jest.requireActual( '@woocommerce/tracks' ),
	recordEvent: jest.fn(),
} ) );

describe( 'FeedbackBar', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );
	it( 'should trigger product_editor_feedback_bar_turnoff_editor_click event when clicking turn off editor', () => {
		( useFeedbackBar as jest.Mock ).mockImplementation( () => ( {
			hideFeedbackBar: () => {},
			shouldShowFeedbackBar: true,
		} ) );
		render( <FeedbackBar productType={ 'testing' } /> );

		act( () => {
			fireEvent.click( screen.getByText( 'turn it off' ) );
		} );
		expect( recordEvent ).toBeCalledWith(
			'product_editor_feedback_bar_turnoff_editor_click',
			{ product_type: 'testing' }
		);
	} );
} );
