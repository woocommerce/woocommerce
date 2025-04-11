/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { PluginErrorBanner } from '../PluginErrorBanner';

describe( 'PluginErrorBanner', () => {
	it( 'should render permissions failure message when pluginsInstallationPermissionsFailure is true', () => {
		render(
			<PluginErrorBanner pluginsInstallationPermissionsFailure={ true } />
		);
		expect(
			screen.getByText(
				/You do not have permissions to install plugins/i
			)
		).toBeInTheDocument();
	} );

	it( 'should render generic error message when pluginsInstallationErrors is provided', () => {
		const errors = [
			{
				plugin: 'test-plugin',
				errorDetails: {
					data: { data: { status: 500 }, code: 'error_code' },
				},
				error: 'An error occurred during installation',
			},
		];
		render(
			<PluginErrorBanner
				pluginsInstallationErrors={ errors }
				pluginsSlugToName={ {
					'test-plugin': 'Test Plugin',
				} }
			/>
		);
		expect(
			screen.getByText(
				/Oops! We encountered a problem while installing/i
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'Test Plugin' ) ).toBeInTheDocument();
	} );

	it( 'should render the correct strings if multiple plugins fail to install', () => {
		const errors = [
			{
				plugin: 'test-plugin-1',
				errorDetails: {
					data: { data: { status: 500 }, code: 'error_code' },
				},
				error: 'An error occurred during installation',
			},
			{
				plugin: 'test-plugin-2',
				errorDetails: {
					data: { data: { status: 500 }, code: 'error_code' },
				},
				error: 'An error occurred during installation',
			},
		];
		render(
			<PluginErrorBanner
				pluginsInstallationErrors={ errors }
				pluginsSlugToName={ {
					'test-plugin-1': 'Test Plugin 1',
					'test-plugin-2': 'Test Plugin 2',
				} }
			/>
		);
		expect(
			screen.getByText(
				/Oops! We encountered a problem while installing/i
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'Test Plugin 1' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Test Plugin 2' ) ).toBeInTheDocument();
	} );

	it( 'should render permissions failure message when pluginsInstallationErrors contains a 403 error', () => {
		const errors = [
			{
				plugin: 'test-plugin',
				errorDetails: {
					data: {
						data: { status: 403 },
						code: 'woocommerce_rest_cannot_update',
					},
				},
				error: 'An error occurred during installation',
			},
		];
		render(
			<PluginErrorBanner
				pluginsSlugToName={ {
					'test-plugin': 'Test Plugin',
				} }
				pluginsInstallationErrors={ errors }
			/>
		);
		expect(
			screen.getByText(
				/You do not have permissions to install plugins/i
			)
		).toBeInTheDocument();
	} );

	it( 'should call onClick when "Please try again" link is clicked', () => {
		const mockOnClick = jest.fn();
		const errors = [
			{
				plugin: 'test-plugin',
				errorDetails: {
					data: { data: { status: 500 }, code: 'error_code' },
				},
				error: 'An error occurred during installation',
			},
		];
		render(
			<PluginErrorBanner
				pluginsSlugToName={ {
					'test-plugin': 'Test Plugin',
				} }
				pluginsInstallationErrors={ errors }
				onClick={ mockOnClick }
			/>
		);
		screen.getByText( 'Please try again' ).click();
		expect( mockOnClick ).toHaveBeenCalledTimes( 1 );
	} );
} );
