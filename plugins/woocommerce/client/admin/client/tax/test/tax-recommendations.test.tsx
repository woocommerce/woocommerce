/**
 * External dependencies
 */
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import TaxRecommendations from '../tax-recommendations';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '~/components/tracked-link/tracked-link', () => ( {
	TrackedLink: ( { message } ) => <div>{ message }</div>,
} ) );

jest.mock( '../../settings-recommendations/dismissable-list', () => ( {
	DismissableList: ( { children } ) => children,
	DismissableListHeading: ( { children } ) => children,
} ) );

jest.mock( '../../lib/notices', () => ( {
	createNoticesFromResponse: () => null,
} ) );

describe( 'TaxRecommendations', () => {
	const installPluginsMock = jest.fn().mockResolvedValue( undefined );
	const activatePluginsMock = jest.fn().mockResolvedValue( undefined );
	const createSuccessNoticeMock = jest.fn();

	beforeEach( () => {
		installPluginsMock.mockClear();
		activatePluginsMock.mockClear();
		createSuccessNoticeMock.mockClear();

		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getInstalledPlugins: () => [],
				getActivePlugins: () => [],
			} ) )
		);

		( useDispatch as jest.Mock ).mockImplementation( ( store ) => {
			if ( store === 'core/notices' ) {
				return {
					createSuccessNotice: createSuccessNoticeMock,
				};
			}

			return {
				installPlugins: installPluginsMock,
				activatePlugins: activatePluginsMock,
			};
		} );
	} );

	it( 'renders WooCommerce Tax and Anrok with install buttons when no related plugins are present', () => {
		render( <TaxRecommendations /> );

		expect( screen.getByText( 'WooCommerce Tax' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Anrok' ) ).toBeInTheDocument();
		expect( screen.getAllByText( 'Install' ) ).toHaveLength( 2 );
	} );

	it( 'shows WooCommerce Tax as active when a shipping-related Woo plugin is active', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getInstalledPlugins: () => [],
				getActivePlugins: () => [ 'woocommerce-shipping' ],
			} ) )
		);

		render( <TaxRecommendations /> );

		expect( screen.getByText( 'WooCommerce Tax' ) ).toBeInTheDocument();
		expect(
			screen.getByLabelText( 'WooCommerce Tax is already active' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Anrok' ) ).toBeInTheDocument();
	} );

	it( 'shows Activate when Anrok is installed but inactive', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getInstalledPlugins: () => [ 'anrok-tax' ],
				getActivePlugins: () => [],
			} ) )
		);

		render( <TaxRecommendations /> );

		expect( screen.getByText( 'WooCommerce Tax' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Anrok' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Activate' ) ).toBeInTheDocument();
	} );

	it( 'installs WooCommerce Tax using the WooCommerce Services slug', async () => {
		render( <TaxRecommendations /> );

		const wooCommerceTaxItem = screen
			.getByText( 'WooCommerce Tax' )
			.closest( '.woocommerce-list__item' );

		expect( wooCommerceTaxItem ).not.toBeNull();

		userEvent.click(
			within( wooCommerceTaxItem as HTMLElement ).getByRole( 'button', {
				name: 'Install',
			} )
		);

		await waitFor( () => {
			expect( installPluginsMock ).toHaveBeenCalledWith( [
				'woocommerce-services',
			] );
		} );

		await waitFor( () => {
			expect( createSuccessNoticeMock ).toHaveBeenCalledWith(
				'WooCommerce Tax is installed!',
				expect.anything()
			);
		} );
	} );

	it( 'activates the installed WooCommerce Tax alias when one is already present', async () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getInstalledPlugins: () => [ 'woocommerce-tax' ],
				getActivePlugins: () => [],
			} ) )
		);

		render( <TaxRecommendations /> );

		const wooCommerceTaxItem = screen
			.getByText( 'WooCommerce Tax' )
			.closest( '.woocommerce-list__item' );

		expect( wooCommerceTaxItem ).not.toBeNull();

		userEvent.click(
			within( wooCommerceTaxItem as HTMLElement ).getByRole( 'button', {
				name: 'Activate',
			} )
		);

		await waitFor( () => {
			expect( activatePluginsMock ).toHaveBeenCalledWith( [
				'woocommerce-tax',
			] );
		} );

		await waitFor( () => {
			expect( createSuccessNoticeMock ).toHaveBeenCalledWith(
				'WooCommerce Tax activated!',
				expect.anything()
			);
		} );
	} );
} );
