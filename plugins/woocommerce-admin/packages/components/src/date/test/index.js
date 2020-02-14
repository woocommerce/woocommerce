/**
 * External dependencies
 *
 */
import { shallow } from 'enzyme';

/**
 * Internal dependencies
 */
import Date from '../';

describe( 'Date', () => {
	test( 'should use fallback formats', () => {
		const time = shallow( <Date date="2019-01-01" /> );
		const spans = time.find( 'span' );

		expect( time.prop( 'dateTime' ) ).toBe( '2019-01-01 00:00:00' );
		expect( spans.get( 0 ).props.children ).toBe( '2019-01-01' );
		expect( spans.get( 1 ).props.children ).toBe( 'January 1, 2019' );
	} );

	test( 'should respect formats from props', () => {
		const time = shallow(
			<Date
				date="2019-01-01"
				machineFormat="Y-m-d"
				screenReaderFormat="Y"
				visibleFormat="m/d/Y"
			/>
		);
		const spans = time.find( 'span' );

		expect( time.prop( 'dateTime' ) ).toBe( '2019-01-01' );
		expect( spans.get( 0 ).props.children ).toBe( '01/01/2019' );
		expect( spans.get( 1 ).props.children ).toBe( '2019' );
	} );
} );
