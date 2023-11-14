/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { noticeContexts } from '@woocommerce/base-context';
import { NoticeType } from '@woocommerce/types';
interface PaymentMethodErrorBoundaryProps {
	isEditor: boolean;
	children: React.ReactNode;
}
const PaymentMethodErrorBoundary = ( {
	isEditor,
	children,
}: PaymentMethodErrorBoundaryProps ) => {
	const [ errorMessage ] = useState( '' );
	const [ hasError ] = useState( false );
	if ( hasError ) {
		let errorText = __(
			'We are experiencing difficulties with this payment method. Please contact us for assistance.',
			'woo-gutenberg-products-block'
		);
		if ( isEditor || CURRENT_USER_IS_ADMIN ) {
			if ( errorMessage ) {
				errorText = errorMessage;
			} else {
				errorText = __(
					"There was an error with this payment method. Please verify it's configured correctly.",
					'woo-gutenberg-products-block'
				);
			}
		}
		const notices: NoticeType[] = [
			{
				id: '0',
				content: errorText,
				isDismissible: false,
				status: 'error',
			},
		];
		return (
			<StoreNoticesContainer
				additionalNotices={ notices }
				context={ noticeContexts.PAYMENTS }
			/>
		);
	}
	return <>{ children }</>;
};
export default PaymentMethodErrorBoundary;
