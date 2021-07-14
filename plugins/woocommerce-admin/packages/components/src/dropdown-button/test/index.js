/**
 * External dependencies
 */
import renderer from 'react-test-renderer';
import { createElement } from '@wordpress/element';

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
