/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CustomerFeedbackSimple } from '../index';

const mockRecordScoreCallback = jest.fn();

describe( 'CustomerFeedbackSimple', () => {
	it( 'should trigger recordScoreCallback when item is selected', () => {
		render(
			<CustomerFeedbackSimple
				recordScoreCallback={ mockRecordScoreCallback }
			/>
		);

		// Select the option.
		fireEvent.click( screen.getAllByText( '🙂' )[ 0 ] );

		expect( mockRecordScoreCallback ).toHaveBeenCalledWith( 4 );
	} );
} );
