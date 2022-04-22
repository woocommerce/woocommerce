/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Date from '../';

describe( 'Date', () => {
	test( 'should use fallback formats', () => {
		const { container } = render( <Date date="2019-01-01" /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should respect formats from props', () => {
		const { container } = render(
			<Date
				date="2019-01-01"
				machineFormat="Y-m-d"
				screenReaderFormat="Y"
				visibleFormat="m/d/Y"
			/>
		);
		expect( container ).toMatchSnapshot();
	} );
} );
