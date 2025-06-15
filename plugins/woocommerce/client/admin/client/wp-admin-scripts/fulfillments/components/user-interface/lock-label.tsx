/**
 * External dependencies
 */
import { Gridicon } from '@automattic/components';

export default function LockLabel( { message }: { message: string } ) {
	return (
		<div className="woocommerce-fulfillment-lock-label">
			<span className="woocommerce-fulfillment-lock-label__icon">
				<Gridicon icon={ 'lock' } size={ 14 } color="#757575" />
			</span>
			<span className="woocommerce-fulfillment-lock-label__text">
				{ message
					? message
					: 'This item is locked and cannot be edited.' }
			</span>
		</div>
	);
}
