/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { Form } from '@woocommerce/components';
import userEvent from '@testing-library/user-event';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ProductSettings } from '../';

jest.mock( '@woocommerce/admin-layout', () => ( {
	WooHeaderItem: ( props: { children: () => React.ReactElement } ) => (
		<Fragment { ...props }>{ props.children }</Fragment>
	),
} ) );

describe( 'ProductSettings', () => {
	it( 'should render the menu settings button', () => {
		const { getByLabelText } = render(
			<Form initialValues={ {} }>
				<ProductSettings />
			</Form>
		);
		expect( getByLabelText( 'Settings' ) ).toBeInTheDocument();
	} );

	it( 'should not show the settings menu on initial render', () => {
		const { queryByText } = render(
			<Form initialValues={ {} }>
				<ProductSettings />
			</Form>
		);
		expect( queryByText( 'Settings' ) ).not.toBeInTheDocument();
	} );

	it( 'should render the settings menu on clicking the settings button', () => {
		const { getByLabelText, getByText } = render(
			<Form initialValues={ {} }>
				<ProductSettings />
			</Form>
		);

		userEvent.click( getByLabelText( 'Settings' ) );
		expect( getByText( 'Settings' ) ).toBeInTheDocument();
	} );

	it( 'should close the settings menu on clicking the settings button', () => {
		const { getByLabelText, queryByText } = render(
			<Form initialValues={ {} }>
				<ProductSettings />
			</Form>
		);

		userEvent.click( getByLabelText( 'Settings' ) );
		userEvent.click( getByLabelText( 'Settings' ) );
		expect( queryByText( 'Settings' ) ).not.toBeInTheDocument();
	} );

	it( 'should close the settings menu on clicking the close button', () => {
		const { getByLabelText, queryByText } = render(
			<Form initialValues={ {} }>
				<ProductSettings />
			</Form>
		);

		userEvent.click( getByLabelText( 'Settings' ) );
		userEvent.click( getByLabelText( 'Close settings' ) );
		expect( queryByText( 'Settings' ) ).not.toBeInTheDocument();
	} );
} );
