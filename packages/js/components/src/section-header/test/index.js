/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SectionHeader from '../';

describe( 'SectionHeader', () => {
	test( 'should have correct title', () => {
		const sectionHeader = <SectionHeader title="A SectionHeader Example" />;
		expect( sectionHeader.props.title ).toBe( 'A SectionHeader Example' );
	} );

	test( 'it renders correctly', () => {
		const component = render(
			<SectionHeader title="A SectionHeader Example" />
		);
		expect( component ).toMatchSnapshot();
	} );
} );
