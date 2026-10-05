import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
const { mockShippingRatesControlPackage, mockSlotRender } = vi.hoisted( () => {
	const mockShippingRatesControlPackage = vi.fn(
		( props: Record< string, unknown > ) => {
			void props;
			return <div data-testid="shipping-package" />;
		}
	);
	const mockSlotRender = vi.fn( ( props: Record< string, unknown > ) => {
		void props;
		return <div data-testid="shipping-slot" />;
	} );
	return {
		mockShippingRatesControlPackage,
		mockSlotRender,
	};
} );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import {
	useEditorContext,
	useShippingData,
	useStoreCart,
} from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import ShippingRatesControl from '..';
import type { ShippingRatesControlProps } from '../types';
import {
	generateShippingPackage,
	generateShippingRate,
} from '../../../../../mocks/shipping-package';
vi.mock( '@woocommerce/base-context', () => {
	const mock = {
		useStoreCart: vi.fn(),
		useEditorContext: vi.fn( () => ( {
			isEditor: false,
		} ) ),
		useShippingData: vi.fn( () => ( {
			hasSelectedLocalPickup: false,
			selectedRates: {},
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../shipping-rates-control-package', () => {
	const mock = {
		__esModule: true,
		default: ( props: Record< string, unknown > ) =>
			mockShippingRatesControlPackage( props ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/base-hooks', () => {
	const mock = {
		usePrevious: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/blocks-checkout', () => {
	const MockFill = ( { children }: { children: React.ReactNode } ) => (
		<>{ children }</>
	);
	MockFill.Slot = ( props: Record< string, unknown > ) =>
		mockSlotRender( props );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		ExperimentalOrderShippingPackages: MockFill,
	} );
} );
const defaultProps: ShippingRatesControlProps = {
	shippingRates: [],
	isLoadingRates: false,
	className: 'test-class',
	collapsible: false,
	showItems: false,
	noResultsMessage: <span>No rates</span>,
	renderOption: vi.fn(),
	context: 'woocommerce/checkout',
};
const createShippingPackage = (
	packageId: string | number,
	rateId: string
) => ( {
	...generateShippingPackage( {
		packageId: 0,
		shippingRates: [
			generateShippingRate( {
				rateId,
				name: 'Flat rate',
				price: '1000',
				instanceID: 1,
			} ),
		],
	} ),
	package_id: packageId,
} );
describe( 'ShippingRatesControl slot rendering', () => {
	beforeEach( () => {
		vi.clearAllMocks();
		( useStoreCart as Mock ).mockReturnValue( {
			extensions: {
				'ship-ext': true,
			},
			receiveCart: vi.fn(),
			cartTotals: {},
		} );
	} );
	it( 'renders ExperimentalOrderShippingPackages.Slot with correct props when not loading', () => {
		render( <ShippingRatesControl { ...defaultProps } /> );
		expect( screen.getByTestId( 'shipping-slot' ) ).toBeInTheDocument();
		expect( mockSlotRender ).toHaveBeenCalledWith(
			expect.objectContaining( {
				context: 'woocommerce/checkout',
				extensions: {
					'ship-ext': true,
				},
				collapsible: false,
				showItems: false,
			} )
		);
		const slotProps = mockSlotRender.mock.calls[ 0 ][ 0 ];
		expect( slotProps.cart ).not.toHaveProperty( 'receiveCart' );
		expect( slotProps ).toHaveProperty( 'components' );
		expect( slotProps ).toHaveProperty( 'renderOption' );
		expect( slotProps ).toHaveProperty( 'noResultsMessage' );
	} );
	it( 'does not render the slot when rates are loading', () => {
		render(
			<ShippingRatesControl { ...defaultProps } isLoadingRates={ true } />
		);
		expect(
			screen.queryByTestId( 'shipping-slot' )
		).not.toBeInTheDocument();
		expect( mockSlotRender ).not.toHaveBeenCalled();
	} );
	it( 'selects initial rates for all packages when rendering them', () => {
		const selectShippingRate = vi.fn();
		( useShippingData as Mock ).mockReturnValue( {
			hasSelectedLocalPickup: false,
			selectedRates: {},
			selectShippingRate,
		} );
		const shippingRates = [
			generateShippingPackage( {
				packageId: 0,
				shippingRates: [
					generateShippingRate( {
						rateId: 'flat_rate:1',
						name: 'Flat rate',
						price: '1000',
						instanceID: 1,
					} ),
				],
			} ),
			generateShippingPackage( {
				packageId: 1,
				shippingRates: [
					generateShippingRate( {
						rateId: 'free_shipping:2',
						name: 'Free shipping',
						price: '0',
						instanceID: 2,
					} ),
					generateShippingRate( {
						rateId: 'flat_rate:3',
						name: 'Flat rate',
						price: '1000',
						instanceID: 3,
						selected: true,
					} ),
				],
			} ),
			generateShippingPackage( {
				packageId: 2,
				shippingRates: [
					generateShippingRate( {
						rateId: 'flat_rate:4',
						name: 'Flat rate',
						price: '1000',
						instanceID: 4,
					} ),
				],
			} ),
			generateShippingPackage( {
				packageId: 3,
				shippingRates: [],
			} ),
		];
		render(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ shippingRates }
			/>
		);
		expect( selectShippingRate.mock.calls ).toEqual( [
			[ 'flat_rate:1', 0 ],
			[ 'flat_rate:3', 1 ],
			[ 'flat_rate:4', 2 ],
		] );
		expect( screen.getAllByTestId( 'shipping-package' ) ).toHaveLength( 4 );
		expect( mockShippingRatesControlPackage ).toHaveBeenCalledTimes( 4 );
		mockShippingRatesControlPackage.mock.calls.forEach( ( [ props ] ) => {
			expect( props ).toEqual(
				expect.objectContaining( {
					manageSelectionLocally: false,
				} )
			);
		} );
	} );
	it( 'selects initial rates when packages become available after an empty render', () => {
		const selectShippingRate = vi.fn();
		( useShippingData as Mock ).mockReturnValue( {
			hasSelectedLocalPickup: false,
			selectedRates: {},
			selectShippingRate,
		} );
		const firstPackage = createShippingPackage( 0, 'flat_rate:1' );
		const secondPackage = createShippingPackage(
			'subscription-package',
			'flat_rate:2'
		);
		const { rerender } = render(
			<ShippingRatesControl { ...defaultProps } shippingRates={ [] } />
		);
		rerender(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ firstPackage, secondPackage ] }
			/>
		);
		expect( selectShippingRate.mock.calls ).toEqual( [
			[ 'flat_rate:1', 0 ],
			[ 'flat_rate:2', 'subscription-package' ],
		] );
	} );
	it( 'selects an initial rate when an existing empty package becomes populated', () => {
		const selectShippingRate = vi.fn();
		( useShippingData as Mock ).mockReturnValue( {
			hasSelectedLocalPickup: false,
			selectedRates: {},
			selectShippingRate,
		} );
		const emptyPackage = generateShippingPackage( {
			packageId: 0,
			shippingRates: [],
		} );
		const populatedPackage = createShippingPackage( 0, 'flat_rate:1' );
		const { rerender } = render(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ emptyPackage ] }
			/>
		);
		expect( selectShippingRate ).not.toHaveBeenCalled();
		rerender(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ populatedPackage ] }
			/>
		);
		expect( selectShippingRate.mock.calls ).toEqual( [
			[ 'flat_rate:1', 0 ],
		] );
		rerender(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ createShippingPackage( 0, 'flat_rate:1' ) ] }
			/>
		);
		expect( selectShippingRate ).toHaveBeenCalledTimes( 1 );
	} );
	it( 'selects only newly added packages on subsequent renders', () => {
		const selectShippingRate = vi.fn();
		( useShippingData as Mock ).mockReturnValue( {
			hasSelectedLocalPickup: false,
			selectedRates: {},
			selectShippingRate,
		} );
		const firstPackage = createShippingPackage( 0, 'flat_rate:1' );
		const secondPackage = createShippingPackage( 1, 'flat_rate:2' );
		const { rerender } = render(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ firstPackage ] }
			/>
		);
		selectShippingRate.mockClear();
		rerender(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ firstPackage, secondPackage ] }
			/>
		);
		expect( selectShippingRate.mock.calls ).toEqual( [
			[ 'flat_rate:2', 1 ],
		] );
		rerender(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ firstPackage, secondPackage ] }
			/>
		);
		expect( selectShippingRate ).toHaveBeenCalledTimes( 1 );
	} );
	it( 'selects a package again after it is removed and re-added', () => {
		const selectShippingRate = vi.fn();
		( useShippingData as Mock ).mockReturnValue( {
			hasSelectedLocalPickup: false,
			selectedRates: {},
			selectShippingRate,
		} );
		const firstPackage = createShippingPackage( 0, 'flat_rate:1' );
		const secondPackage = createShippingPackage( 1, 'flat_rate:2' );
		const { rerender } = render(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ firstPackage, secondPackage ] }
			/>
		);
		selectShippingRate.mockClear();
		rerender(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ firstPackage ] }
			/>
		);
		expect( selectShippingRate ).not.toHaveBeenCalled();
		rerender(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ firstPackage, secondPackage ] }
			/>
		);
		expect( selectShippingRate.mock.calls ).toEqual( [
			[ 'flat_rate:2', 1 ],
		] );
	} );
	it( 'selects again for a package whose rates disappear and come back', () => {
		const selectShippingRate = vi.fn();
		( useShippingData as Mock ).mockReturnValue( {
			hasSelectedLocalPickup: false,
			selectedRates: {},
			selectShippingRate,
		} );
		const { rerender } = render(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ createShippingPackage( 0, 'flat_rate:1' ) ] }
			/>
		);
		expect( selectShippingRate.mock.calls ).toEqual( [
			[ 'flat_rate:1', 0 ],
		] );

		// An address the store cannot ship to leaves the package without rates.
		rerender(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [
					generateShippingPackage( {
						packageId: 0,
						shippingRates: [],
					} ),
				] }
			/>
		);

		// A shippable address brings different rates back, none of them selected.
		rerender(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ createShippingPackage( 0, 'flat_rate:9' ) ] }
			/>
		);
		expect( selectShippingRate.mock.calls ).toEqual( [
			[ 'flat_rate:1', 0 ],
			[ 'flat_rate:9', 0 ],
		] );
	} );
	it( 'lets the rate lists own their selection in the editor only', () => {
		const selectShippingRate = vi.fn();
		( useShippingData as Mock ).mockReturnValue( {
			hasSelectedLocalPickup: false,
			selectedRates: {},
			selectShippingRate,
		} );
		render(
			<ShippingRatesControl
				{ ...defaultProps }
				shippingRates={ [ createShippingPackage( 0, 'flat_rate:1' ) ] }
			/>
		);
		expect( mockShippingRatesControlPackage ).toHaveBeenCalledWith(
			expect.objectContaining( {
				manageSelectionLocally: false,
			} )
		);

		// The cart store never changes in the editor, because the selectShippingRate
		// thunk returns early there, so the rate list has to render its own state or
		// clicking a rate in the block preview would do nothing.
		mockShippingRatesControlPackage.mockClear();
		selectShippingRate.mockClear();
		( useEditorContext as Mock ).mockReturnValue( {
			isEditor: true,
		} );
		try {
			render(
				<ShippingRatesControl
					{ ...defaultProps }
					shippingRates={ [
						createShippingPackage( 0, 'flat_rate:1' ),
					] }
				/>
			);
			expect( mockShippingRatesControlPackage ).toHaveBeenCalledWith(
				expect.objectContaining( {
					manageSelectionLocally: true,
				} )
			);
			// Selecting from here as well would fire set-selected-shipping-rate
			// twice per package for anything listening in the editor.
			expect( selectShippingRate ).not.toHaveBeenCalled();
		} finally {
			( useEditorContext as Mock ).mockReturnValue( {
				isEditor: false,
			} );
		}
	} );
} );
