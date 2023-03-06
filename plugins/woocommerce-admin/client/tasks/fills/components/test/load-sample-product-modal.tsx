/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import LoadSampleProductModal from '../load-sample-product-modal';

describe( 'LoadSampleProductModal', () => {
	it( 'should render LoadSampleProductModal', () => {
		const { queryByText } = render( <LoadSampleProductModal /> );
		expect( queryByText( 'Loading sample products' ) ).toBeInTheDocument();
	} );
} );
