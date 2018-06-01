/**
 * External dependencies
 *
 * @format
 */

import { shallow } from 'enzyme';

/**
 * Internal dependencies
 */
import Card from '../';

describe( 'Card', () => {
	test( 'should have correct title', () => {
		const card = <Card title="A Card Example" />;
		expect( card.props.title ).toBe( 'A Card Example' );
	} );

	test( 'should have correct class', () => {
		const card = shallow( <Card title="A Card Example" /> );
		expect( card.hasClass( 'woocommerce-card' ) ).toBe( true );
	} );
} );
