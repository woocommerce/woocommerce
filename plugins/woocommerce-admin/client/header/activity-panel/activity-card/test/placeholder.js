/** @format */
/**
 * External dependencies
 */
import { shallow } from 'enzyme';

/**
 * Internal dependencies
 */
import { ActivityCardPlaceholder } from '../';

describe( 'ActivityCardPlaceholder', () => {
	test( 'should render a default placeholder', () => {
		const card = shallow( <ActivityCardPlaceholder /> );
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with subtitle placeholder', () => {
		const card = shallow( <ActivityCardPlaceholder hasSubtitle /> );
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with date placeholder', () => {
		const card = shallow( <ActivityCardPlaceholder hasDate /> );
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with action placeholder', () => {
		const card = shallow( <ActivityCardPlaceholder hasAction /> );
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with all optional placeholder', () => {
		const card = shallow( <ActivityCardPlaceholder hasAction hasDate hasSubtitle /> );
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with multiple lines of content', () => {
		const card = shallow( <ActivityCardPlaceholder lines={ 3 } /> );
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with no content', () => {
		const card = shallow( <ActivityCardPlaceholder lines={ 0 } /> );
		expect( card ).toMatchSnapshot();
	} );
} );
