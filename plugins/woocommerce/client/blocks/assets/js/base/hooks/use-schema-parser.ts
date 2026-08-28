/**
 * External dependencies
 */
import { useRef, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { snakeCaseKeys } from '@woocommerce/base-utils';
import { defaultFields } from '@woocommerce/settings';
import type {
	OrderFormValues,
	AddressFormValues,
	FormFields,
	FormType,
	ContactFormValues,
} from '@woocommerce/settings';
import {
	ORDER_FORM_KEYS,
	CONTACT_FORM_KEYS,
} from '@woocommerce/block-settings';
import fastDeepEqual from 'fast-deep-equal/es6';
import {
	cartStore,
	checkoutStore,
	paymentStore,
} from '@woocommerce/block-data';
import type Ajv from 'ajv';

const dateFieldKeys = Object.keys( defaultFields ).filter(
	( key ) => defaultFields[ key as keyof FormFields ]?.type === 'date'
);

/**
 * Converts date field values to the YYYYMMDD integers rules are compared against.
 */
const prepareValues = < T extends object >( values: T ): T => {
	if ( ! dateFieldKeys.some( ( key ) => key in values ) ) {
		return values;
	}

	return Object.fromEntries(
		Object.entries( values ).map( ( [ key, value ] ) => [
			key,
			dateFieldKeys.includes( key )
				? Number( String( value ).replace( /-/g, '' ) ) || null
				: value,
		] )
	) as T;
};

const useDocumentObject = < T extends FormType | 'global' >(
	formType: T
): DocumentObject< T > => {
	const currentResults = useRef< DocumentObject< T > >( {
		cart: {},
		checkout: {},
		customer: {},
	} );

	const {
		cartData,
		prefersCollection,
		shouldCreateAccount,
		orderNotes,
		additionalFields,
		activePaymentMethod,
		customerId,
	} = useSelect( ( select ) => {
		const cartDataStore = select( cartStore );
		const checkoutDataStore = select( checkoutStore );
		const paymentDataStore = select( paymentStore );
		return {
			cartData: cartDataStore.getCartData(),
			prefersCollection: checkoutDataStore.prefersCollection(),
			shouldCreateAccount: checkoutDataStore.getShouldCreateAccount(),
			orderNotes: checkoutDataStore.getOrderNotes(),
			additionalFields: checkoutDataStore.getAdditionalFields(),
			activePaymentMethod: paymentDataStore.getActivePaymentMethod(),
			customerId: checkoutDataStore.getCustomerId(),
		};
	}, [] );

	const data = useMemo( () => {
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
			extensions,
		} = cartData;

		const preparedBilling = prepareValues( billingAddress );
		const preparedShipping = prepareValues( shippingAddress );
		const preparedAdditionalFields = prepareValues( additionalFields );

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
					.map(
						( item ) =>
							Array( Math.ceil( item.quantity ) ).fill( item.id ) // Rounds up to nearest integer.
					)
					.flat(),
				itemsType: [ ...new Set( items.map( ( item ) => item.type ) ) ],
				itemsCount,
				itemsWeight,
				needsShipping,
				prefersCollection:
					typeof prefersCollection === 'boolean'
						? prefersCollection
						: false,
				totals: {
					total_price: Number( totals.total_price ),
					total_tax: Number( totals.total_tax ),
				},
				extensions,
			},
			checkout: {
				createAccount: shouldCreateAccount,
				customerNote: orderNotes,
				additionalFields: Object.fromEntries(
					Object.entries( preparedAdditionalFields ).filter(
						( [ key ] ) =>
							ORDER_FORM_KEYS.includes(
								key as keyof OrderFormValues
							)
					)
				) as OrderFormValues,
				paymentMethod: activePaymentMethod,
			},
			customer: {
				id: customerId,
				billingAddress: preparedBilling,
				shippingAddress: preparedShipping,
				additionalFields: Object.fromEntries(
					Object.entries( preparedAdditionalFields ).filter(
						( [ key ] ) =>
							CONTACT_FORM_KEYS.includes(
								key as keyof ContactFormValues
							)
					)
				) as ContactFormValues,
				...( formType === 'billing' || formType === 'shipping'
					? {
							address:
								formType === 'billing'
									? preparedBilling
									: preparedShipping,
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
	}, [
		cartData,
		prefersCollection,
		shouldCreateAccount,
		orderNotes,
		additionalFields,
		activePaymentMethod,
		customerId,
		formType,
	] );

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
		data,
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
				additional_fields: ContactFormValues;
				address: T extends 'billing'
					? AddressFormValues
					: T extends 'shipping'
					? AddressFormValues
					: null;
		  }
		| Record< string, never >;
}
