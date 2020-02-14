/**
 * External dependencies
 */
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import DropdownButton from '../';

describe( 'DropdownButton', () => {
	test( 'it renders correctly', () => {
		const tree = renderer
			.create( <DropdownButton labels={ [ 'foo' ] } /> )
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );
} );
