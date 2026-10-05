import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CustomerFeedbackSimple } from '../index';

const mockOnSelectCallback = vi.fn();

describe( 'CustomerFeedbackSimple', () => {
	it( 'should trigger recordScoreCallback when item is selected', () => {
		render(
			<CustomerFeedbackSimple
				onSelect={ mockOnSelectCallback }
				label=""
			/>
		);

		// Select the option.
		fireEvent.click( screen.getAllByText( '🙂' )[ 0 ] );

		expect( mockOnSelectCallback ).toHaveBeenCalledWith( 4 );
	} );
} );
