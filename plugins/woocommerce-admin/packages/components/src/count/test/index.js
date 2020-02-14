/**
 * External dependencies
 */
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import Count from '../';

describe( 'Count', () => {
	test( 'it renders correctly', () => {
		const tree = renderer.create( <Count count={ 33 } /> ).toJSON();
		expect( tree ).toMatchSnapshot();
	} );
} );
