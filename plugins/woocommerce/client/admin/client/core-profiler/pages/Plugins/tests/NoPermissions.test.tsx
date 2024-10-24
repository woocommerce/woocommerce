/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import React from 'react';
import { WCUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { NoPermissionsError } from '../NoPermissions';

describe( 'NoPermissions', () => {
	const mockSendEvent = jest.fn();
	const defaultProps = {
		context: {
			pluginsAvailable: [
				{
					key: 'jetpack',
					name: 'Jetpack',
					learn_more_link: 'https://jetpack.com',
					description: 'Jetpack description',
					image_url: 'jetpack-image-url',
					manage_url: 'jetpack-manage-url',
					is_built_by_wc: false,
					is_visible: true,
				},
				{
					key: 'woocommerce-payments',
					name: 'WooCommerce Payments',
					description: 'WooCommerce Payments description',
					image_url: 'woocommerce-payments-image-url',
					manage_url: 'woocommerce-payments-manage-url',
					is_built_by_wc: true,
					is_visible: true,
				},
			],
			currentUser: {
				capabilities: {},
			} as WCUser< 'capabilities' >,
		},
		sendEvent: mockSendEvent,
		navigationProgress: 50,
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );
	it( 'should render the component with correct title and subtitle', () => {
		render(
			<NoPermissionsError
				{ ...defaultProps }
				context={ {
					...defaultProps.context,
					pluginsAvailable: defaultProps.context.pluginsAvailable,
				} }
			/>
		);

		expect(
			screen.getByText( 'Get a boost with our free features' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Enhance your store by installing these free business features. No commitment required – you can remove them at any time.'
			)
		).toBeInTheDocument();
	} );

	it( 'should render PluginErrorBanner with permissions failure', () => {
		render( <NoPermissionsError { ...defaultProps } /> );

		expect(
			screen.getByText(
				'You do not have permissions to install plugins. Please contact your site administrator.'
			)
		).toBeInTheDocument();
	} );

	it( 'should call sendEvent with PLUGINS_LEARN_MORE_LINK_CLICKED when learn more is clicked', () => {
		render( <NoPermissionsError { ...defaultProps } /> );

		fireEvent.click( screen.getByText( 'Learn More' ) );

		expect( mockSendEvent ).toHaveBeenCalledWith( {
			type: 'PLUGINS_LEARN_MORE_LINK_CLICKED',
			payload: {
				plugin: 'jetpack',
				learnMoreLink: 'https://jetpack.com',
			},
		} );
	} );

	it( 'should call sendEvent with PLUGINS_PAGE_SKIPPED when continue button is clicked', () => {
		render( <NoPermissionsError { ...defaultProps } /> );

		fireEvent.click( screen.getByText( 'Continue' ) );

		expect( mockSendEvent ).toHaveBeenCalledWith( {
			type: 'PLUGINS_PAGE_SKIPPED',
		} );
	} );
} );
