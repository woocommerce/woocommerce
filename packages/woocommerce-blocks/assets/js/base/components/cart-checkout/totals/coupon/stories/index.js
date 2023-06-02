/**
 * External dependencies
 */
import { text, boolean } from '@storybook/addon-knobs';
import {
	useValidationContext,
	ValidationContextProvider,
} from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import TotalsCoupon from '../';

export default {
	title:
		'WooCommerce Blocks/@base-components/cart-checkout/totals/TotalsCoupon',
	component: TotalsCoupon,
};

const StoryComponent = ( { validCoupon, isLoading, invalidCouponText } ) => {
	const { setValidationErrors } = useValidationContext();
	const onSubmit = ( coupon ) => {
		if ( coupon !== validCoupon ) {
			setValidationErrors( { coupon: invalidCouponText } );
		}
	};
	return <TotalsCoupon isLoading={ isLoading } onSubmit={ onSubmit } />;
};

export const Default = () => {
	const validCoupon = text( 'A valid coupon code', 'validcoupon' );
	const invalidCouponText = text(
		'Error message for invalid code',
		'Invalid coupon code.'
	);
	const isLoading = boolean( 'Toggle isLoading state', false );
	return (
		<ValidationContextProvider>
			<StoryComponent
				validCoupon={ validCoupon }
				isLoading={ isLoading }
				invalidCouponText={ invalidCouponText }
			/>
		</ValidationContextProvider>
	);
};
