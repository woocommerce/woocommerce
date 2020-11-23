/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { RecommendedExtensionsPlaceholder } from '../';

describe( 'RecommendedExtensionsPlaceholder', () => {
	test( 'should render a default placeholder', () => {
		const { container } = render( <RecommendedExtensionsPlaceholder /> );
		expect( container ).toMatchSnapshot();
	} );
} );
