/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Main, Sidebar } from '@woocommerce/base-components/sidebar-layout';

/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import { CartLineItemsCheckoutSkeleton } from '../cart-line-items';
import { CheckoutShippingSkeleton } from '../checkout-shipping';
import { CheckoutPaymentSkeleton } from '../checkout-payment';

const FieldGroupSkeleton = ( { fields = 3 }: { fields?: number } ) => (
	<>
		<Skeleton height="20px" maxWidth="180px" />
		{ Array.from( { length: fields } ).map( ( _, index ) => (
			<Skeleton key={ index } height="48px" />
		) ) }
	</>
);

/**
 * Placeholder shown while checkout data is fetched on the client.
 */
export const CheckoutSkeleton = () => {
	return (
		<>
			<Main className="wc-block-checkout__main">
				<div
					className="wc-block-components-skeleton wc-block-components-skeleton--checkout"
					aria-live="polite"
					aria-label={ __( 'Loading checkout…', 'woocommerce' ) }
				>
					<FieldGroupSkeleton fields={ 1 } />
					<FieldGroupSkeleton fields={ 4 } />
					<FieldGroupSkeleton fields={ 2 } />
				</div>
			</Main>
			<Sidebar className="wc-block-checkout__sidebar">
				<CartLineItemsCheckoutSkeleton />
				<CheckoutShippingSkeleton />
				<CheckoutPaymentSkeleton />
			</Sidebar>
		</>
	);
};
