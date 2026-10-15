/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SummaryNumber from '../number';

describe( 'SummaryNumber', () => {
	test( 'does not trigger the Tooltip `position` deprecation', () => {
		const warn = jest
			.spyOn( console, 'warn' )
			.mockImplementation( () => {} );

		render(
			<SummaryNumber
				label="Total sales"
				labelTooltipText="Sales after refunds"
				value="$100"
				prevLabel="Previous period:"
				prevValue="$50"
				delta={ 100 }
				href="#"
			/>
		);

		expect( warn ).not.toHaveBeenCalledWith(
			expect.stringContaining(
				'`position` prop in wp.components.tooltip'
			)
		);

		warn.mockRestore();
	} );
} );
