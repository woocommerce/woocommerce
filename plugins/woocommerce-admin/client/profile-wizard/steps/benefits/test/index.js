/**
 * External dependencies
 */
import { act, fireEvent, render } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { BenefitsLayout } from '../';

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useDispatch: jest.fn().mockReturnValue( {} ),
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

describe( 'BenefitsLayout', () => {
	it( 'should show WooCommerce Service when it is to be installed', () => {
		useSelect.mockImplementation( () => ( {
			activePlugins: [ 'jetpack' ],
			isJetpackConnected: true,
		} ) );

		const { container, queryByText } = render( <BenefitsLayout /> );

		expect(
			container.querySelectorAll(
				'.woocommerce-profile-wizard__benefit-card'
			).length
		).toBe( 2 );
		expect( queryByText( 'Print shipping labels at home' ) ).not.toBeNull();
		expect( queryByText( 'Automated sales taxes' ) ).not.toBeNull();
	} );

	it( 'should show Jetpack benefits when Jetpack is to be installed', () => {
		useSelect.mockImplementation( () => ( {
			activePlugins: [ 'woocommerce-services' ],
			isJetpackConnected: false,
		} ) );

		const { container, queryByText } = render( <BenefitsLayout /> );

		expect(
			container.querySelectorAll(
				'.woocommerce-profile-wizard__benefit-card'
			).length
		).toBe( 3 );
		expect( queryByText( 'Store management on the go' ) ).not.toBeNull();
		expect( queryByText( 'Automated sales taxes' ) ).not.toBeNull();
		expect( queryByText( 'Improved speed & security' ) ).not.toBeNull();

		useSelect.mockImplementation( () => ( {
			activePlugins: [ 'jetpack', 'woocommerce-services' ],
			isJetpackConnected: false,
		} ) );

		expect(
			container.querySelectorAll(
				'.woocommerce-profile-wizard__benefit-card'
			).length
		).toBe( 3 );
		expect( queryByText( 'Store management on the go' ) ).not.toBeNull();
		expect( queryByText( 'Automated sales taxes' ) ).not.toBeNull();
		expect( queryByText( 'Improved speed & security' ) ).not.toBeNull();
	} );

	it( 'should show Jetpack benefits when Jetpack is not connected', () => {
		useSelect.mockImplementation( () => ( {
			activePlugins: [ 'jetpack', 'woocommerce-services' ],
			isJetpackConnected: false,
		} ) );

		const { container, queryByText } = render( <BenefitsLayout /> );

		expect(
			container.querySelectorAll(
				'.woocommerce-profile-wizard__benefit-card'
			).length
		).toBe( 3 );
		expect( queryByText( 'Store management on the go' ) ).not.toBeNull();
		expect( queryByText( 'Automated sales taxes' ) ).not.toBeNull();
		expect( queryByText( 'Improved speed & security' ) ).not.toBeNull();
	} );

	it( 'should skip the benefits step when setup is already complete', () => {
		useSelect.mockImplementation( () => ( {
			activePlugins: [ 'jetpack', 'woocommerce-services' ],
			isJetpackConnected: true,
		} ) );
		const goToNextStep = jest.fn();

		const { container } = render(
			<BenefitsLayout goToNextStep={ goToNextStep } />
		);

		expect( container ).toBeEmptyDOMElement();
		expect( goToNextStep ).toHaveBeenCalled();
	} );

	it( 'should skip the plugin installation when clicking skip', () => {
		const goToNextStep = jest.fn();
		const updateProfileItems = jest.fn();

		useSelect.mockImplementation( () => ( {
			activePlugins: [],
			isJetpackConnected: false,
		} ) );
		useDispatch.mockReturnValue( {
			updateProfileItems,
		} );

		const { queryByText } = render(
			<BenefitsLayout goToNextStep={ goToNextStep } />
		);

		fireEvent.click( queryByText( 'No thanks' ) );
		expect( updateProfileItems ).toHaveBeenCalledWith( {
			plugins: 'skipped',
		} );
	} );

	it( 'should install the plugins when opting in', async () => {
		const goToNextStep = jest.fn();
		const updateProfileItems = jest.fn();
		const installAndActivatePlugins = jest.fn();

		useSelect.mockImplementation( () => ( {
			activePlugins: [],
			isJetpackConnected: false,
		} ) );
		useDispatch.mockReturnValue( {
			installAndActivatePlugins,
			updateOptions: jest.fn(),
			updateProfileItems,
		} );

		await act( async () => {
			const { queryByText } = render(
				<BenefitsLayout goToNextStep={ goToNextStep } />
			);

			fireEvent.click( queryByText( 'Yes please!' ) );
			expect( updateProfileItems ).toHaveBeenCalledWith( {
				plugins: 'installed',
			} );
			expect( installAndActivatePlugins ).toHaveBeenCalledWith( [
				'jetpack',
				'woocommerce-services',
			] );
		} );
	} );

	it( 'should show a busy state for the install action', () => {
		// Prevent the promise from resolving right away.
		useDispatch.mockReturnValue( {
			installAndActivatePlugins: () => new Promise( () => {} ),
			updateOptions: jest.fn(),
			updateProfileItems: jest.fn(),
		} );

		const { queryByText } = render( <BenefitsLayout /> );

		const installButton = queryByText( 'Yes please!' );
		const skipButton = queryByText( 'No thanks' );

		act( () => {
			fireEvent.click( installButton );
		} );

		expect( installButton.classList ).toContain( 'is-busy' );
		expect( installButton.disabled ).toBeTruthy();
		expect( skipButton.disabled ).toBeTruthy();
	} );

	it( 'should show a busy state for the skip action', async () => {
		const { queryByText, rerender } = render( <BenefitsLayout /> );

		// Prevent the promise from resolving right away.
		useDispatch.mockReturnValue( {
			updateProfileItems: new Promise( () => {} ),
		} );
		useSelect.mockImplementation( () => ( {
			activePlugins: [],
			isJetpackConnected: false,
			isUpdatingProfileItems: true,
		} ) );

		const installButton = queryByText( 'Yes please!' );
		const skipButton = queryByText( 'No thanks' );
		fireEvent.click( skipButton );

		rerender( <BenefitsLayout /> );

		expect( installButton.disabled ).toBeTruthy();
		expect( skipButton.disabled ).toBeTruthy();
		expect( skipButton.classList ).toContain( 'is-busy' );
	} );
} );
