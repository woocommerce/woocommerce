// write tests for PluginTermsOfService

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { PluginsTermsOfService } from '../PluginsTermsOfService';

describe( 'PluginsTermsOfService', () => {
	it( 'should not render anything when no plugins with TOS are selected', () => {
		const { container } = render(
			<PluginsTermsOfService
				selectedPlugins={ [
					{
						key: 'test-plugin',
						name: 'test-plugin',
						description: '',
						image_url: '',
						manage_url: '',
						is_built_by_wc: false,
						is_visible: false,
					},
				] }
			/>
		);
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'should render TOS message when Jetpack is selected', () => {
		render(
			<PluginsTermsOfService
				selectedPlugins={ [
					{
						key: 'jetpack',
						name: 'Jetpack',
						description: '',
						image_url: '',
						manage_url: '',
						is_built_by_wc: false,
						is_visible: false,
					},
				] }
			/>
		);
		expect(
			screen.getByText( /plugin for free you agree to our/i )
		).toMatchSnapshot();
		expect( screen.getByText( 'Jetpack' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Terms of Service' ) ).toBeInTheDocument();
	} );

	it( 'should render TOS message with multiple plugins when more than one plugin with TOS is selected', () => {
		render(
			<PluginsTermsOfService
				selectedPlugins={ [
					{
						key: 'jetpack',
						name: 'Jetpack',
						description: '',
						image_url: '',
						manage_url: '',
						is_built_by_wc: false,
						is_visible: false,
					},
					{
						key: 'woocommerce-shipping',
						name: 'WooCommerce Shipping',
						description: '',
						image_url: '',
						manage_url: '',
						is_built_by_wc: false,
						is_visible: false,
					},
					{
						key: 'woocommerce-services:tax',
						name: 'WooCommerce Tax',
						description: '',
						image_url: '',
						manage_url: '',
						is_built_by_wc: false,
						is_visible: false,
					},
				] }
			/>
		);
		expect( screen.getByText( /By installing/ ) ).toMatchSnapshot();
		expect( screen.getByText( 'Jetpack' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'WooCommerce Shipping' )
		).toBeInTheDocument();
		expect( screen.getByText( 'WooCommerce Tax' ) ).toBeInTheDocument();
		// add a snapshot test for the entire component
		expect( screen.getByText( /By installing/ ) ).toMatchSnapshot();
	} );
} );
