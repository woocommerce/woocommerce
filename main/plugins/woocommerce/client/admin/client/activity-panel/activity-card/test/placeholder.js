/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ActivityCardPlaceholder } from '../';

describe( 'ActivityCardPlaceholder', () => {
	test( 'should render a default placeholder', () => {
		const { container } = render( <ActivityCardPlaceholder /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with subtitle placeholder', () => {
		const { container } = render( <ActivityCardPlaceholder hasSubtitle /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with date placeholder', () => {
		const { container } = render( <ActivityCardPlaceholder hasDate /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with action placeholder', () => {
		const { container } = render( <ActivityCardPlaceholder hasAction /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with all optional placeholder', () => {
		const { container } = render(
			<ActivityCardPlaceholder hasAction hasDate hasSubtitle />
		);
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with multiple lines of content', () => {
		const { container } = render( <ActivityCardPlaceholder lines={ 3 } /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a card placeholder with no content', () => {
		const { container } = render( <ActivityCardPlaceholder lines={ 0 } /> );
		expect( container ).toMatchSnapshot();
	} );
} );
