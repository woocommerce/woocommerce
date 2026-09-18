/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import {
	defaultFields as storeDefaultFields,
	type FormFields,
} from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { useCheckoutAddress } from '../use-checkout-address';
import { useEditorContext } from '../../providers/editor-context';

// The checkout store is only used as a key here, so the hook's selectors and
// actions can be stubbed without registering it.
jest.mock( '@woocommerce/block-data', () => ( {
	checkoutStore: 'wc/store/checkout',
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: () => ( {
		useShippingAsBilling: false,
		prefersCollection: false,
		editingBillingAddress: false,
		editingShippingAddress: false,
	} ),
	useDispatch: () => ( {
		__internalSetUseShippingAsBilling: jest.fn(),
		setEditingBillingAddress: jest.fn(),
		setEditingShippingAddress: jest.fn(),
	} ),
} ) );

jest.mock( '../../providers/editor-context', () => ( {
	useEditorContext: jest.fn(),
} ) );

jest.mock( '../use-customer-data', () => {
	const address = {
		first_name: 'Jane',
		last_name: 'Doe',
		company: '',
		address_1: '123 Main Street',
		address_2: '',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94110',
		country: 'US',
		phone: '',
	};

	return {
		useCustomerData: jest.fn( () => ( {
			isInitialized: true,
			billingAddress: { ...address, email: 'jane@example.com' },
			shippingAddress: address,
			setBillingAddress: jest.fn(),
			setShippingAddress: jest.fn(),
		} ) ),
	};
} );

jest.mock( '../shipping/use-shipping-data', () => ( {
	useShippingData: jest.fn( () => ( { needsShipping: true } ) ),
} ) );

const mockUseEditorContext = useEditorContext as jest.MockedFunction<
	typeof useEditorContext
>;

// The editor writes the inspector's field settings into the preview data, which
// is what the canvas then reads back.
const previewDefaultFields = {
	address_2: {
		label: 'Apartment, suite, etc.',
		optionalLabel: 'Apartment, suite, etc. (optional)',
		required: true,
		hidden: false,
		autocomplete: 'address-line2',
		index: 50,
	},
	phone: {
		label: 'Phone',
		optionalLabel: 'Phone (optional)',
		required: true,
		hidden: false,
		autocomplete: 'tel',
		index: 100,
	},
} as unknown as FormFields;

const previewData = {
	defaultFields: previewDefaultFields as unknown as Record< string, unknown >,
};

// Keyed like the editor provider: a name the preview data carries resolves to
// it, anything else falls back to what the caller passed.
const getPreviewData = jest.fn(
	(
		name: string,
		fallback: Record< string, unknown > = {}
	): Record< string, unknown > =>
		name in previewData
			? previewData[ name as keyof typeof previewData ]
			: fallback
);

const setEditorContext = ( isEditor: boolean ) => {
	mockUseEditorContext.mockReturnValue( {
		isEditor,
		currentPostId: 0,
		currentView: '',
		previewData,
		getPreviewData,
	} );
};

describe( 'useCheckoutAddress default fields', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'returns the editor preview fields in the editor', () => {
		setEditorContext( true );

		const { result } = renderHook( () => useCheckoutAddress() );

		expect( result.current.defaultFields.address_2 ).toMatchObject( {
			required: true,
			hidden: false,
		} );
		expect( result.current.defaultFields.phone ).toMatchObject( {
			required: true,
			hidden: false,
		} );
		expect( result.current.defaultFields ).toBe( previewDefaultFields );
		expect( getPreviewData ).toHaveBeenCalledWith(
			'defaultFields',
			storeDefaultFields
		);
	} );

	it( 'returns the store fields outside the editor', () => {
		setEditorContext( false );

		const { result } = renderHook( () => useCheckoutAddress() );

		expect( result.current.defaultFields.address_2 ).toMatchObject( {
			required: false,
			hidden: false,
		} );
		expect( result.current.defaultFields.phone ).toMatchObject( {
			required: false,
			hidden: true,
		} );
		expect( result.current.defaultFields ).toBe( storeDefaultFields );
		expect( getPreviewData ).not.toHaveBeenCalled();
	} );
} );
