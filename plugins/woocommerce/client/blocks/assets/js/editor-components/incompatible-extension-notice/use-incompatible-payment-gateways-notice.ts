/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { paymentStore } from '@woocommerce/block-data';

export const useIncompatiblePaymentGatewaysNotice = (): [
	{ [ k: string ]: string },
	string[],
	number
] => {
	const { incompatiblePaymentMethods } = useSelect( ( select ) => {
		const { getIncompatiblePaymentMethods } = select( paymentStore );
		return {
			incompatiblePaymentMethods: getIncompatiblePaymentMethods(),
		};
	}, [] );

	const incompatiblePaymentMethodSlugs = Object.keys(
		incompatiblePaymentMethods
	);

	const incompatiblePaymentMethodCount =
		incompatiblePaymentMethodSlugs.length;

	return [
		incompatiblePaymentMethods,
		incompatiblePaymentMethodSlugs,
		incompatiblePaymentMethodCount,
	];
};
