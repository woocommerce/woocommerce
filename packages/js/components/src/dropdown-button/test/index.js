/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import DropdownButton from '../';

describe( 'DropdownButton', () => {
	test( 'it renders correctly', () => {
		const component = render( <DropdownButton labels={ [ 'foo' ] } /> );
		expect( component ).toMatchSnapshot();
	} );
} );
