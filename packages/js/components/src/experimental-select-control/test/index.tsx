/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SelectControl } from '../';

describe( 'SelectControl', () => {
	it( 'should render the default suffix if none is specified', () => {
		const { container } = render(
			<SelectControl label="Select" items={ [] } selected={ null } />
		);

		// We can't really determine if the correct suffix icon is being used
		// without checking against the SVG path, which would be brittle;
		// so, we just check if any suffix icon has been rendered
		expect(
			container.querySelector(
				'.woocommerce-experimental-select-control__suffix-icon'
			)
		).toBeInTheDocument();
	} );

	it( 'should render a custom suffix if one is specified', () => {
		const { getByText } = render(
			<SelectControl
				label="Select"
				items={ [] }
				selected={ null }
				suffix={ <div>custom suffix</div> }
			/>
		);

		expect( getByText( 'custom suffix' ) ).toBeInTheDocument();
	} );

	it( 'should render no suffix if null is specified', () => {
		const { container } = render(
			<SelectControl
				label="Select"
				items={ [] }
				selected={ null }
				suffix={ null }
			/>
		);

		expect(
			container.querySelector(
				'.woocommerce-experimental-select-control__suffix'
			)
		).not.toBeInTheDocument();
	} );
} );
