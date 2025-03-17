/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { snakeCaseKeys } from '@woocommerce/base-utils';
import type {
	OrderFormValues,
	AddressFormValues,
	FormType,
} from '@woocommerce/settings';
import fastDeepEqual from 'fast-deep-equal/es6';
import {
	cartStore,
	checkoutStore,
	paymentStore,
} from '@woocommerce/block-data';
import type Ajv from 'ajv';

const useDocumentObject = < T extends FormType | 'global' >(
	formType: T
): DocumentObject< T > => {
	const currentResults = useRef< DocumentObject< T > >( {
		cart: {},
		checkout: {},
		customer: {},
	} );

	const data: DocumentObject< T > = useSelect(
		( select ) => {
			const cartDataStore = select( cartStore );
			const checkoutDataStore = select( checkoutStore );
			const paymentDataStore = select( paymentStore );
			const cartData = cartDataStore.getCartData();

			const {
				coupons,
				shippingRates,
				shippingAddress,
				billingAddress,
				items,
				itemsCount,
				itemsWeight,
				needsShipping,
				totals,
			} = cartData;
			const documentObject = {
				cart: {
					coupons: coupons.map( ( coupon ) => coupon.code ),
					shippingRates: [
						...new Set(
							shippingRates
								.map(
									( shippingPackage ) =>
										shippingPackage.shipping_rates.find(
											( rate ) => rate.selected
										)?.rate_id
								)
								.filter( Boolean )
						),
					],
					items: items
						.map( ( item ) =>
							Array( item.quantity ).fill( item.id )
						)
						.flat(),
					itemsType: [
						...new Set( items.map( ( item ) => item.type ) ),
					],
					itemsCount,
					itemsWeight,
					needsShipping,
					prefersCollection:
						typeof checkoutDataStore.prefersCollection() ===
						'boolean'
							? checkoutDataStore.prefersCollection()
							: false,
					totals: {
						totalPrice: Number( totals.total_price ),
						totalTax: Number( totals.total_tax ),
					},
					extensions: cartData.extensions,
				},
				checkout: {
					createAccount: checkoutDataStore.getShouldCreateAccount(),
					customerNote: checkoutDataStore.getOrderNotes(),
					additionalFields: checkoutDataStore.getAdditionalFields(),
					paymentMethod: paymentDataStore.getActivePaymentMethod(),
				},
				customer: {
					id: checkoutDataStore.getCustomerId(),
					billingAddress,
					shippingAddress,
					...( formType === 'billing' || formType === 'shipping'
						? {
								address:
									formType === 'billing'
										? billingAddress
										: shippingAddress,
						  }
						: {} ),
				},
			};

			return {
				cart: snakeCaseKeys( documentObject.cart ) as DocumentObject<
					typeof formType
				>[ 'cart' ],
				checkout: snakeCaseKeys(
					documentObject.checkout
				) as DocumentObject< typeof formType >[ 'checkout' ],
				customer: snakeCaseKeys(
					documentObject.customer
				) as DocumentObject< typeof formType >[ 'customer' ],
			};
		},
		[ formType ]
	);

	if (
		! currentResults.current ||
		! fastDeepEqual( currentResults.current, data )
	) {
		currentResults.current = data;
	}

	return currentResults.current;
};

export const useSchemaParser = < T extends FormType | 'global' >(
	formType: T
): {
	parser: Ajv | null;
	data: DocumentObject< T > | null;
} => {
	const data = useDocumentObject< T >( formType );
	if ( window.schemaParser ) {
		return {
			parser: window.schemaParser,
			data,
		};
	}
	return {
		parser: null,
		data: null,
	};
};

export interface DocumentObject< T extends FormType | 'global' > {
	cart:
		| {
				coupons: string[];
				shipping_rates: string[];
				prefers_collection: boolean;
				items: string[];
				items_type: string[];
				items_count: number;
				items_weight: number;
				needs_shipping: boolean;
				totals: {
					total_price: number;
					total_tax: number;
				};
				extensions: Record< string, object | object[] >;
		  }
		| Record< string, never >;
	checkout:
		| {
				create_account: boolean;
				customer_note: string;
				payment_method: string;
				additional_fields: OrderFormValues;
		  }
		| Record< string, never >;
	customer:
		| {
				id: number;
				billing_address: AddressFormValues;
				shipping_address: AddressFormValues;
				address: T extends 'billing'
					? AddressFormValues
					: T extends 'shipping'
					? AddressFormValues
					: null;
		  }
		| Record< string, never >;
}
