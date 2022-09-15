/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DateTimePickerControl } from '../';

describe( 'DateTimePickerControl', () => {
	it( 'should add the supplied class name', () => {
		const { container } = render(
			<DateTimePickerControl className="custom-class-name" />
		);

		const control = container.querySelector( '.custom-class-name' );
		expect( control ).toBeInTheDocument();
	} );
} );
