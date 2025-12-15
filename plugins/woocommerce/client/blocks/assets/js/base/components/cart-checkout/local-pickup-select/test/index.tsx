/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useShippingData, useStoreCart } from '@woocommerce/base-context/hooks';
import { CartShippingPackageShippingRate } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import LocalPickupSelect from '..';
import {
	generateShippingRate,
	generateShippingPackage,
} from '../../../../../mocks/shipping-package';

jest.mock( '@woocommerce/base-context/hooks' );

describe( 'LocalPickupSelect', () => {
	const renderPickupLocationMock = jest.fn().mockImplementation(
		// eslint-disable-next-line @typescript-eslint/no-unused-vars
		( location, pickupLocationsCount, clientSelectedOption ) => {
			return {
				value: `${ location.rate_id }`,
				onChange: jest.fn(),
				label: `${ location.name }`,
				description: `${ location.description }`,
				clientSelectedOption,
			};
		}
	);

	const defaultPackageData = generateShippingPackage( {
		packageId: 0,
		shippingRates: [],
	} );

	const mockShippingData = ( packageData = defaultPackageData ) => {
		( useShippingData as jest.Mock ).mockImplementation( () => ( {
			shippingRates: [ packageData ],
		} ) );
	};

	const defaultRenderPickupLocation = (
		location: CartShippingPackageShippingRate
	) => ( {
		value: `${ location.rate_id }`,
		onChange: jest.fn(),
		label: `${ location.name }`,
		description: `${ location.description }`,
	} );

	const defaultPickupLocations = [
		generateShippingRate( {
			rateId: '1',
			name: 'Store 1',
			instanceID: 1,
			price: '0',
		} ),
		generateShippingRate( {
			rateId: '2',
			name: 'Store 2',
			instanceID: 1,
			price: '0',
		} ),
	];

	beforeEach( () => {
		mockShippingData();

		( useStoreCart as jest.Mock ).mockImplementation( () => ( {
			cartItems: [],
		} ) );
	} );

	const TestComponent = ( {
		onChange,
		renderPickupLocation = defaultRenderPickupLocation,
		pickupLocations = defaultPickupLocations,
		packageCount = 1,
	}: {
		onChange?: ( value: string ) => void;
		renderPickupLocation?: typeof defaultRenderPickupLocation;
		pickupLocations?: typeof defaultPickupLocations;
		packageCount?: number;
	} ) => (
		<LocalPickupSelect
			title="Package 1"
			onChange={ onChange ?? jest.fn() }
			selectedOption=""
			pickupLocations={ pickupLocations }
			packageCount={ packageCount }
			renderPickupLocation={ renderPickupLocation }
		/>
	);
	it( 'Does not render the title if only one package is present on the page', () => {
		render( <TestComponent /> );
		expect( screen.queryByText( 'Package 1' ) ).not.toBeInTheDocument();
	} );
	it( 'Does render the title if more than one package is present on the page', () => {
		const { rerender } = render(
			<div className="wc-block-components-local-pickup-select">
				<div className="wc-block-components-radio-control"></div>
			</div>
		);
		// Render twice so our component can check the DOM correctly.
		rerender(
			<>
				<div className="wc-block-components-local-pickup-select">
					<div className="wc-block-components-radio-control"></div>
				</div>
				<TestComponent />
			</>
		);
		rerender(
			<>
				<div className="wc-block-components-local-pickup-select">
					<div className="wc-block-components-radio-control"></div>
				</div>
				<TestComponent />
			</>
		);

		expect( screen.getByText( 'Package 1' ) ).toBeInTheDocument();
	} );
	it( 'Calls the correct functions when changing selected option', async () => {
		const user = userEvent.setup();
		const onChange = jest.fn();
		render( <TestComponent onChange={ onChange } /> );

		await user.click( screen.getByText( 'Store 2' ) );
		expect( onChange ).toHaveBeenLastCalledWith( '2' );

		await user.click( screen.getByText( 'Store 1' ) );
		expect( onChange ).toHaveBeenLastCalledWith( '1' );
	} );
	it( 'Calls renderPickupLocation with correct parameters', () => {
		renderPickupLocationMock.mockClear();
		render(
			<LocalPickupSelect
				title="Package 1"
				onChange={ jest.fn() }
				selectedOption="store_2"
				pickupLocations={ [
					generateShippingRate( {
						rateId: 'store_1',
						name: 'Store 1',
						instanceID: 1,
						price: '0',
					} ),
					generateShippingRate( {
						rateId: 'store_2',
						name: 'Store 2',
						instanceID: 1,
						price: '0',
					} ),
				] }
				packageCount={ 1 }
				renderPickupLocation={ renderPickupLocationMock }
			/>
		);

		// First location: not selected
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			1,
			expect.objectContaining( {
				rate_id: 'store_1',
				name: 'Store 1',
			} ),
			1, // packageCount
			'store_2' // gets the currently selected option.
		);

		// Second location: selected
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			2,
			expect.objectContaining( {
				rate_id: 'store_2',
				name: 'Store 2',
			} ),
			1, // packageCount
			'store_2' // gets the currently selected option.
		);
	} );
	it( 'Updates clientSelectedOption parameter when selection changes', () => {
		renderPickupLocationMock.mockClear();
		const pickupLocations = [
			generateShippingRate( {
				rateId: 'store_1',
				name: 'Store 1',
				instanceID: 1,
				price: '0',
			} ),
			generateShippingRate( {
				rateId: 'store_2',
				name: 'Store 2',
				instanceID: 1,
				price: '0',
			} ),
		];

		const { rerender } = render(
			<LocalPickupSelect
				title="Package 1"
				onChange={ jest.fn() }
				selectedOption="store_1"
				pickupLocations={ pickupLocations }
				packageCount={ 1 }
				renderPickupLocation={ renderPickupLocationMock }
			/>
		);

		// Initial render: Store 1 selected
		expect( renderPickupLocationMock ).toHaveBeenCalledTimes( 2 );
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			1,
			expect.objectContaining( { rate_id: 'store_1' } ),
			1,
			'store_1' // Store 1 is selected
		);
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			2,
			expect.objectContaining( { rate_id: 'store_2' } ),
			1,
			'store_1' // Store 2 is not selected
		);

		// Clear mock and rerender with different selection
		renderPickupLocationMock.mockClear();
		rerender(
			<LocalPickupSelect
				title="Package 1"
				onChange={ jest.fn() }
				selectedOption="store_2"
				pickupLocations={ pickupLocations }
				packageCount={ 1 }
				renderPickupLocation={ renderPickupLocationMock }
			/>
		);

		// After rerender: Store 2 selected
		expect( renderPickupLocationMock ).toHaveBeenCalledTimes( 2 );
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			1,
			expect.objectContaining( { rate_id: 'store_1' } ),
			1,
			'store_2' // Store 1 is not selected
		);
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			2,
			expect.objectContaining( { rate_id: 'store_2' } ),
			1,
			'store_2' // Store 2 is selected
		);
	} );

	describe( 'packageData prop', () => {
		it( 'Renders package name when multiple packages are present', () => {
			const packageDataWithName = {
				...generateShippingPackage( {
					packageId: 0,
					shippingRates: [],
				} ),
				name: 'Test Package Name',
			};

			mockShippingData( packageDataWithName );

			const { rerender } = render(
				<div className="wc-block-components-local-pickup-select">
					<div className="wc-block-components-radio-control"></div>
				</div>
			);

			rerender(
				<>
					<div className="wc-block-components-local-pickup-select">
						<div className="wc-block-components-radio-control"></div>
					</div>
					<LocalPickupSelect
						title="Package 1"
						packageData={ packageDataWithName }
						onChange={ jest.fn() }
						selectedOption=""
						pickupLocations={ [
							generateShippingRate( {
								rateId: '1',
								name: 'Store 1',
								instanceID: 1,
								price: '0',
							} ),
						] }
						packageCount={ 2 }
						renderPickupLocation={ defaultRenderPickupLocation }
					/>
				</>
			);

			rerender(
				<>
					<div className="wc-block-components-local-pickup-select">
						<div className="wc-block-components-radio-control"></div>
					</div>
					<LocalPickupSelect
						title="Package 1"
						packageData={ packageDataWithName }
						onChange={ jest.fn() }
						selectedOption=""
						pickupLocations={ defaultPickupLocations }
						packageCount={ 2 }
						renderPickupLocation={ defaultRenderPickupLocation }
					/>
				</>
			);

			expect(
				screen.getByText( 'Test Package Name' )
			).toBeInTheDocument();
		} );

		it( 'Renders package header with items when showItems is true', () => {
			const packageDataWithItems = {
				...generateShippingPackage( {
					packageId: 0,
					shippingRates: [],
				} ),
				name: 'Package with Items',
			};

			mockShippingData( packageDataWithItems );

			render(
				<LocalPickupSelect
					title="Package 1"
					packageData={ packageDataWithItems }
					showItems={ true }
					onChange={ jest.fn() }
					selectedOption=""
					pickupLocations={ defaultPickupLocations.slice( 0, 1 ) }
					packageCount={ 1 }
					renderPickupLocation={ defaultRenderPickupLocation }
				/>
			);

			expect(
				document.querySelector(
					'.wc-block-components-shipping-rates-control__package-header'
				)
			).toBeInTheDocument();
		} );

		it( 'Does not render package header when showItems is false and single package', () => {
			const packageDataWithItems = {
				...generateShippingPackage( {
					packageId: 0,
					shippingRates: [],
				} ),
				name: 'Single Package',
			};

			mockShippingData( packageDataWithItems );

			render(
				<LocalPickupSelect
					title="Package 1"
					packageData={ packageDataWithItems }
					showItems={ false }
					onChange={ jest.fn() }
					selectedOption=""
					pickupLocations={ defaultPickupLocations.slice( 0, 1 ) }
					packageCount={ 1 }
					renderPickupLocation={ defaultRenderPickupLocation }
				/>
			);

			expect(
				document.querySelector(
					'.wc-block-components-shipping-rates-control__package-header'
				)
			).not.toBeInTheDocument();
		} );

		it( 'Renders package thumbnails when multiple packages are present', () => {
			const packageDataWithItems = {
				...generateShippingPackage( {
					packageId: 0,
					shippingRates: [],
				} ),
				name: 'Package with Thumbnails',
			};

			mockShippingData( packageDataWithItems );

			const { rerender } = render(
				<div className="wc-block-components-local-pickup-select">
					<div className="wc-block-components-radio-control"></div>
				</div>
			);

			rerender(
				<>
					<div className="wc-block-components-local-pickup-select">
						<div className="wc-block-components-radio-control"></div>
					</div>
					<LocalPickupSelect
						title="Package 1"
						packageData={ packageDataWithItems }
						onChange={ jest.fn() }
						selectedOption=""
						pickupLocations={ defaultPickupLocations }
						packageCount={ 2 }
						renderPickupLocation={ defaultRenderPickupLocation }
					/>
				</>
			);

			rerender(
				<>
					<div className="wc-block-components-local-pickup-select">
						<div className="wc-block-components-radio-control"></div>
					</div>
					<LocalPickupSelect
						title="Package 1"
						packageData={ packageDataWithItems }
						onChange={ jest.fn() }
						selectedOption=""
						pickupLocations={ defaultPickupLocations }
						packageCount={ 2 }
						renderPickupLocation={ defaultRenderPickupLocation }
					/>
				</>
			);

			expect(
				document.querySelector(
					'.wc-block-components-shipping-rates-control__package-thumbnails'
				)
			).toBeInTheDocument();
		} );

		it( 'Limits package thumbnails to first 3 items', () => {
			const packageDataWithManyItems = {
				...generateShippingPackage( {
					packageId: 0,
					shippingRates: [],
				} ),
				name: 'Package with Many Items',
			};

			// Add extra items to test the slice functionality
			const extraItems = Array.from( { length: 5 }, ( _, i ) => ( {
				key: `extra-${ i }`,
				name: `Extra Item ${ i }`,
				quantity: 1,
			} ) );
			packageDataWithManyItems.items = [
				...packageDataWithManyItems.items,
				...extraItems,
			];

			mockShippingData( packageDataWithManyItems );

			const { rerender } = render(
				<div className="wc-block-components-local-pickup-select">
					<div className="wc-block-components-radio-control"></div>
				</div>
			);

			rerender(
				<>
					<div className="wc-block-components-local-pickup-select">
						<div className="wc-block-components-radio-control"></div>
					</div>
					<LocalPickupSelect
						title="Package 1"
						packageData={ packageDataWithManyItems }
						onChange={ jest.fn() }
						selectedOption=""
						pickupLocations={ defaultPickupLocations }
						packageCount={ 2 }
						renderPickupLocation={ defaultRenderPickupLocation }
					/>
				</>
			);

			rerender(
				<>
					<div className="wc-block-components-local-pickup-select">
						<div className="wc-block-components-radio-control"></div>
					</div>
					<LocalPickupSelect
						title="Package 1"
						packageData={ packageDataWithManyItems }
						onChange={ jest.fn() }
						selectedOption=""
						pickupLocations={ defaultPickupLocations }
						packageCount={ 2 }
						renderPickupLocation={ defaultRenderPickupLocation }
					/>
				</>
			);

			const thumbnailsContainer = document.querySelector(
				'.wc-block-components-shipping-rates-control__package-thumbnails'
			);
			// Should only render 3 thumbnails even though there are more items
			expect(
				thumbnailsContainer?.querySelectorAll(
					'.wc-block-components-shipping-package-item-icon'
				).length
			).toBeLessThanOrEqual( 3 );
		} );
	} );
} );
