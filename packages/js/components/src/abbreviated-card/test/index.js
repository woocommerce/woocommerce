/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AbbreviatedCard from '../';

describe( 'AbbreviatedCard', () => {
	test( 'it renders correctly', () => {
		const { container, getByText } = render(
			<AbbreviatedCard icon={ <div>icon</div> } href="#">
				<p>Abbreviated card content</p>
			</AbbreviatedCard>
		);
		expect( container ).toMatchSnapshot();

		// should have correct content
		expect( getByText( 'Abbreviated card content' ) ).toBeInTheDocument();

		// should have correct class
		expect(
			container.getElementsByClassName( 'woocommerce-abbreviated-card' )
		).toHaveLength( 1 );
	} );
} );
