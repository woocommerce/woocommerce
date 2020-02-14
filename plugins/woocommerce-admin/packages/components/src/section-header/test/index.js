/**
 * External dependencies
 */
import renderer from 'react-test-renderer';

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
		const tree = renderer
			.create( <SectionHeader title="A SectionHeader Example" /> )
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );
} );
