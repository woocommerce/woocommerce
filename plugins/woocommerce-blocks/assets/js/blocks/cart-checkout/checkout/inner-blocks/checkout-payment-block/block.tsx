/**
 * External dependencies
 */
import { StoreNoticesProvider } from '@woocommerce/base-context';
import { useEmitResponse } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { PaymentMethods } from '../../../payment-methods';

const Block = (): JSX.Element | null => {
	const { noticeContexts } = useEmitResponse();

	return (
		<StoreNoticesProvider context={ noticeContexts.PAYMENTS }>
			<PaymentMethods />
		</StoreNoticesProvider>
	);
};

export default Block;
