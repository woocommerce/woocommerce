/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SplitDropdown } from '..';

describe( 'SplitDropdown', () => {
	test( 'it renders correctly collapsed', () => {
		const { container, getByText, queryByText } = render(
			<SplitDropdown>
				<Button>Default Action</Button>
				<Button className="test-collapsed-button">
					Secondary Action
				</Button>
				<Button className="test-collapsed-button">
					Tertiary Action
				</Button>
			</SplitDropdown>
		);
		expect( container ).toMatchSnapshot();

		// should have correct content
		expect( getByText( 'Default Action' ) ).toBeInTheDocument();
		expect( queryByText( 'Secondary Action' ) ).not.toBeInTheDocument();

		// should have correct classes
		const presentClassNames = [
			'woocommerce-split-dropdown__container',
			'woocommerce-split-dropdown__main-button',
			'woocommerce-split-dropdown__toggle',
		];
		presentClassNames.map( ( className ) =>
			expect(
				container.getElementsByClassName( className )
			).toHaveLength( 1 )
		);
	} );
} );
