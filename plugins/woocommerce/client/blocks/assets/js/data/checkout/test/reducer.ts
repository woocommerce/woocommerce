/**
 * External dependencies
 */
import type { CheckoutResponseSuccess } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import reducer from '../reducers';
import { defaultState } from '../default-state';
import { STATUS } from '../constants';
import * as actions from '../actions';

const makeCheckoutData = (
	overrides: Partial< CheckoutResponseSuccess > = {}
): CheckoutResponseSuccess =>
	( {
		order_id: 0,
		customer_id: 0,
		customer_note: '',
		billing_address: {},
		shipping_address: {},
		additional_fields: {},
		...overrides,
	} ) as unknown as CheckoutResponseSuccess;

describe( 'Checkout Store Reducer', () => {
	it( 'should return the initial state', () => {
		expect( reducer( undefined, {} ) ).toEqual( defaultState );
	} );

	it( 'should handle SET_IDLE', () => {
		const expectedState = {
			...defaultState,
			status: STATUS.IDLE,
		};

		expect( reducer( defaultState, actions.__internalSetIdle() ) ).toEqual(
			expectedState
		);
	} );

	it( 'should handle SET_REDIRECT_URL', () => {
		const expectedState = {
			...defaultState,
			redirectUrl: 'https://example.com',
		};

		expect(
			reducer(
				defaultState,
				actions.__internalSetRedirectUrl( 'https://example.com' )
			)
		).toEqual( expectedState );
	} );

	it( 'should handle SET_COMPLETE', () => {
		const expectedState = {
			...defaultState,
			status: STATUS.COMPLETE,
			redirectUrl: 'https://example.com',
		};

		expect(
			reducer(
				defaultState,
				actions.__internalSetComplete( {
					redirectUrl: 'https://example.com',
				} )
			)
		).toEqual( expectedState );
	} );

	it( 'should handle SET_PROCESSING', () => {
		const expectedState = {
			...defaultState,
			status: STATUS.PROCESSING,
		};

		expect(
			reducer( defaultState, actions.__internalSetProcessing() )
		).toEqual( expectedState );
	} );

	it( 'should handle SET_HAS_ERROR when status is PROCESSING', () => {
		const initialState = { ...defaultState, status: STATUS.PROCESSING };

		const expectedState = {
			...defaultState,
			hasError: true,
			status: STATUS.IDLE,
		};

		expect(
			reducer( initialState, actions.__internalSetHasError( true ) )
		).toEqual( expectedState );
	} );

	it( 'should handle SET_HAS_ERROR when status is BEFORE_PROCESSING', () => {
		const initialState = {
			...defaultState,
			status: STATUS.BEFORE_PROCESSING,
		};

		const expectedState = {
			...defaultState,
			hasError: true,
			status: STATUS.IDLE,
		};

		expect(
			reducer( initialState, actions.__internalSetHasError( true ) )
		).toEqual( expectedState );
	} );

	it( 'should handle SET_HAS_ERROR when status is anything else', () => {
		const initialState = {
			...defaultState,
			status: STATUS.AFTER_PROCESSING,
		};

		const expectedState = {
			...defaultState,
			hasError: false,
			status: STATUS.AFTER_PROCESSING,
		};

		expect(
			reducer( initialState, actions.__internalSetHasError( false ) )
		).toEqual( expectedState );
	} );

	it( 'should handle SET_BEFORE_PROCESSING', () => {
		const expectedState = {
			...defaultState,
			status: STATUS.BEFORE_PROCESSING,
		};

		expect(
			reducer( defaultState, actions.__internalSetBeforeProcessing() )
		).toEqual( expectedState );
	} );

	it( 'should handle SET_AFTER_PROCESSING', () => {
		const expectedState = {
			...defaultState,
			status: STATUS.AFTER_PROCESSING,
		};

		expect(
			reducer( defaultState, actions.__internalSetAfterProcessing() )
		).toEqual( expectedState );
	} );

	it( 'should handle INCREMENT_CALCULATING', () => {
		const expectedState = {
			...defaultState,
			calculatingCount: 1,
		};

		expect(
			reducer( defaultState, actions.__internalStartCalculation() )
		).toEqual( expectedState );
	} );

	it( 'should handle DECREMENT_CALCULATING', () => {
		const initialState = {
			...defaultState,
			calculatingCount: 1,
		};

		const expectedState = {
			...defaultState,
			calculatingCount: 0,
		};

		expect(
			reducer( initialState, actions.__internalFinishCalculation() )
		).toEqual( expectedState );
	} );

	it( 'should handle INCREMENT_CALCULATING using deprecated action', () => {
		const expectedState = {
			...defaultState,
			calculatingCount: 1,
		};

		expect(
			reducer( defaultState, actions.__internalIncrementCalculating() )
		).toEqual( expectedState );
		expect( console ).toHaveWarnedWith(
			'__internalIncrementCalculating is deprecated and will be removed from WooCommerce in version 9.9.0. Please use disableCheckoutFor instead.'
		);
	} );

	it( 'should handle DECREMENT_CALCULATING using deprecated action', () => {
		const initialState = {
			...defaultState,
			calculatingCount: 1,
		};

		const expectedState = {
			...defaultState,
			calculatingCount: 0,
		};

		expect(
			reducer( initialState, actions.__internalDecrementCalculating() )
		).toEqual( expectedState );
		expect( console ).toHaveWarnedWith(
			'__internalDecrementCalculating is deprecated and will be removed from WooCommerce in version 9.9.0. Please use disableCheckoutFor instead.'
		);
	} );

	it( 'should handle SET_CUSTOMER_ID', () => {
		const expectedState = {
			...defaultState,
			customerId: 1,
		};

		expect(
			reducer( defaultState, actions.__internalSetCustomerId( 1 ) )
		).toEqual( expectedState );
	} );

	it( 'should handle SET_USE_SHIPPING_AS_BILLING', () => {
		const expectedState = {
			...defaultState,
			useShippingAsBilling: false,
		};

		expect(
			reducer(
				defaultState,
				actions.__internalSetUseShippingAsBilling( false )
			)
		).toEqual( expectedState );
	} );

	it( 'should handle SET_SHOULD_CREATE_ACCOUNT', () => {
		const expectedState = {
			...defaultState,
			shouldCreateAccount: true,
		};

		expect(
			reducer(
				defaultState,
				actions.__internalSetShouldCreateAccount( true )
			)
		).toEqual( expectedState );
	} );

	it( 'should handle SET_ORDER_NOTES', () => {
		const expectedState = {
			...defaultState,
			orderNotes: 'test',
		};

		expect(
			reducer( defaultState, actions.__internalSetOrderNotes( 'test' ) )
		).toEqual( expectedState );
	} );

	describe( 'should handle SET_EXTENSION_DATA', () => {
		it( 'should set data under a namespace', () => {
			const mockExtensionData = {
				extensionNamespace: {
					testKey: 'test-value',
					testKey2: 'test-value-2',
				},
			};
			const expectedState = {
				...defaultState,
				extensionData: mockExtensionData,
			};
			expect(
				reducer(
					defaultState,
					actions.setExtensionData(
						'extensionNamespace',
						mockExtensionData.extensionNamespace
					)
				)
			).toEqual( expectedState );
		} );
		it( 'should append data under a namespace', () => {
			const mockExtensionData = {
				extensionNamespace: {
					testKey: 'test-value',
					testKey2: 'test-value-2',
				},
			};
			const expectedState = {
				...defaultState,
				extensionData: mockExtensionData,
			};
			const firstState = reducer(
				defaultState,
				actions.setExtensionData( 'extensionNamespace', {
					testKey: 'test-value',
				} )
			);
			const secondState = reducer(
				firstState,
				actions.setExtensionData( 'extensionNamespace', {
					testKey2: 'test-value-2',
				} )
			);
			expect( secondState ).toEqual( expectedState );
		} );
		it( 'support replacing data under a namespace', () => {
			const mockExtensionData = {
				extensionNamespace: {
					testKey: 'test-value',
				},
			};
			const expectedState = {
				...defaultState,
				extensionData: mockExtensionData,
			};
			const firstState = reducer(
				defaultState,
				actions.setExtensionData( 'extensionNamespace', {
					testKeyOld: 'test-value',
				} )
			);
			const secondState = reducer(
				firstState,
				actions.setExtensionData(
					'extensionNamespace',
					{ testKey: 'test-value' },
					true
				)
			);
			expect( secondState ).toEqual( expectedState );
		} );
		it( 'should work with deprecated __internalSetExtensionData and show deprecation warning', () => {
			const mockExtensionData = {
				extensionNamespace: {
					testKey: 'test-value',
				},
			};
			const expectedState = {
				...defaultState,
				extensionData: mockExtensionData,
			};

			const state = reducer(
				defaultState,
				actions.__internalSetExtensionData(
					'extensionNamespace',
					mockExtensionData.extensionNamespace
				)
			);

			expect( state ).toEqual( expectedState );
			expect( console ).toHaveWarnedWith(
				'__internalSetExtensionData is deprecated and will be removed from WooCommerce in version 9.9.0. Please use setExtensionData instead.'
			);
		} );
	} );

	describe( 'should handle ADD_ADDRESS_AUTOCOMPLETE_PROVIDER', () => {
		it( 'should add a new provider to empty list', () => {
			const expectedState = {
				...defaultState,
				addressAutocompleteProviders: [ 'google-places' ],
			};

			expect(
				reducer(
					defaultState,
					actions.addAddressAutocompleteProvider( 'google-places' )
				)
			).toEqual( expectedState );
		} );

		it( 'should add a new provider to existing list', () => {
			const initialState = {
				...defaultState,
				addressAutocompleteProviders: [ 'google-places' ],
			};

			const expectedState = {
				...defaultState,
				addressAutocompleteProviders: [ 'google-places', 'mapbox' ],
			};

			expect(
				reducer(
					initialState,
					actions.addAddressAutocompleteProvider( 'mapbox' )
				)
			).toEqual( expectedState );
		} );

		it( 'should not add duplicate providers', () => {
			const initialState = {
				...defaultState,
				addressAutocompleteProviders: [ 'google-places' ],
			};

			const expectedState = {
				...defaultState,
				addressAutocompleteProviders: [ 'google-places' ],
			};

			expect(
				reducer(
					initialState,
					actions.addAddressAutocompleteProvider( 'google-places' )
				)
			).toEqual( expectedState );
		} );

		it( 'should not add provider if providerId is not a string', () => {
			const expectedState = defaultState;

			expect(
				reducer(
					defaultState,
					// @ts-expect-error Testing invalid input
					actions.addAddressAutocompleteProvider( null )
				)
			).toEqual( expectedState );

			expect(
				reducer(
					defaultState,
					// @ts-expect-error Testing invalid input
					actions.addAddressAutocompleteProvider( 123 )
				)
			).toEqual( expectedState );
		} );
	} );

	describe( 'should handle SET_ACTIVE_ADDRESS_AUTOCOMPLETE_PROVIDER', () => {
		it( 'should set active provider for billing address', () => {
			const expectedState = {
				...defaultState,
				activeAddressAutocompleteProvider: {
					billing: 'google-places',
					shipping: '',
				},
			};

			expect(
				reducer(
					defaultState,
					actions.setActiveAddressAutocompleteProvider(
						'google-places',
						'billing'
					)
				)
			).toEqual( expectedState );
		} );

		it( 'should set active provider for shipping address', () => {
			const expectedState = {
				...defaultState,
				activeAddressAutocompleteProvider: {
					billing: '',
					shipping: 'mapbox',
				},
			};

			expect(
				reducer(
					defaultState,
					actions.setActiveAddressAutocompleteProvider(
						'mapbox',
						'shipping'
					)
				)
			).toEqual( expectedState );
		} );

		it( 'should update existing provider for an address type', () => {
			const initialState = {
				...defaultState,
				activeAddressAutocompleteProvider: {
					billing: 'google-places',
					shipping: 'google-places',
				},
			};

			const expectedState = {
				...defaultState,
				activeAddressAutocompleteProvider: {
					billing: 'mapbox',
					shipping: 'google-places',
				},
			};

			expect(
				reducer(
					initialState,
					actions.setActiveAddressAutocompleteProvider(
						'mapbox',
						'billing'
					)
				)
			).toEqual( expectedState );
		} );

		it( 'should not update if same provider is already active', () => {
			const initialState = {
				...defaultState,
				activeAddressAutocompleteProvider: {
					billing: 'google-places',
					shipping: '',
				},
			};

			const expectedState = initialState;

			expect(
				reducer(
					initialState,
					actions.setActiveAddressAutocompleteProvider(
						'google-places',
						'billing'
					)
				)
			).toEqual( expectedState );
		} );

		it( 'should not update for invalid address type', () => {
			const expectedState = defaultState;

			expect(
				reducer(
					defaultState,
					// @ts-expect-error Testing invalid input
					actions.setActiveAddressAutocompleteProvider(
						'google-places',
						'invalid'
					)
				)
			).toEqual( expectedState );
		} );

		it( 'should not update if providerId is not a string', () => {
			const expectedState = defaultState;

			expect(
				reducer(
					defaultState,
					// @ts-expect-error Testing invalid input
					actions.setActiveAddressAutocompleteProvider(
						null,
						'billing'
					)
				)
			).toEqual( expectedState );

			expect(
				reducer(
					defaultState,
					// @ts-expect-error Testing invalid input
					actions.setActiveAddressAutocompleteProvider(
						123,
						'shipping'
					)
				)
			).toEqual( expectedState );
		} );

		it( 'should preserve other address type when updating one', () => {
			const initialState = {
				...defaultState,
				activeAddressAutocompleteProvider: {
					billing: 'google-places',
					shipping: 'mapbox',
				},
			};

			const expectedState = {
				...defaultState,
				activeAddressAutocompleteProvider: {
					billing: 'another-provider',
					shipping: 'mapbox',
				},
			};

			expect(
				reducer(
					initialState,
					actions.setActiveAddressAutocompleteProvider(
						'another-provider',
						'billing'
					)
				)
			).toEqual( expectedState );
		} );
	} );

	describe( 'should handle RECEIVE_CHECKOUT_DATA', () => {
		it( 'should populate identifiers from the payload', () => {
			const state = reducer(
				defaultState,
				actions.receiveCheckoutData(
					makeCheckoutData( {
						order_id: 42,
						customer_id: 7,
						customer_note: 'Leave at door',
					} )
				)
			);

			expect( state.orderId ).toBe( 42 );
			expect( state.customerId ).toBe( 7 );
			expect( state.orderNotes ).toBe( 'Leave at door' );
		} );

		it( 'should not clobber existing identifiers with empty values', () => {
			const initialState = {
				...defaultState,
				orderId: 99,
				customerId: 5,
				orderNotes: 'Existing note',
			};

			const state = reducer(
				initialState,
				actions.receiveCheckoutData( makeCheckoutData() )
			);

			expect( state.orderId ).toBe( 99 );
			expect( state.customerId ).toBe( 5 );
			expect( state.orderNotes ).toBe( 'Existing note' );
		} );

		it( 'should not enter edit mode when addresses are absent', () => {
			const state = reducer(
				defaultState,
				actions.receiveCheckoutData( makeCheckoutData() )
			);

			expect( state.editingBillingAddress ).toBe(
				defaultState.editingBillingAddress
			);
			expect( state.editingShippingAddress ).toBe(
				defaultState.editingShippingAddress
			);
			expect( state.useShippingAsBilling ).toBe(
				defaultState.useShippingAsBilling
			);
		} );

		it( 'should leave edit mode when a complete address is present', () => {
			const initialState = {
				...defaultState,
				editingBillingAddress: true,
				editingShippingAddress: true,
			};

			const address = {
				first_name: 'Jane',
				last_name: 'Doe',
				address_1: '123 Main St',
			};

			const state = reducer(
				initialState,
				actions.receiveCheckoutData(
					makeCheckoutData( {
						billing_address:
							address as CheckoutResponseSuccess[ 'billing_address' ],
						shipping_address:
							address as CheckoutResponseSuccess[ 'shipping_address' ],
					} )
				)
			);

			expect( state.editingBillingAddress ).toBe( false );
			expect( state.editingShippingAddress ).toBe( false );
			expect( state.useShippingAsBilling ).toBe( true );
		} );

		it( 'should not throw and should preserve useShippingAsBilling when addresses are undefined', () => {
			const initialState = {
				...defaultState,
				useShippingAsBilling: false,
			};

			const payload = makeCheckoutData();
			// Simulate a payload missing address objects entirely.
			delete ( payload as Record< string, unknown > ).billing_address;
			delete ( payload as Record< string, unknown > ).shipping_address;

			let state;
			expect( () => {
				state = reducer(
					initialState,
					actions.receiveCheckoutData( payload )
				);
			} ).not.toThrow();

			expect( state ).toMatchObject( { useShippingAsBilling: false } );
		} );
	} );
} );
