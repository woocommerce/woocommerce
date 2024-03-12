/**
 * External dependencies
 */
import { Icon, moreVertical } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';

export const LaunchYourStoreStatus = () => {
	return (
		<div className="woocommerce-lys-status">
			<div className="woocommerce-lys-status-pill-wrapper">
				<div className="woocommerce-lys-status-pill">
					<span>Coming soon</span>
					<Icon icon={ moreVertical } size={ 18 } />
				</div>
			</div>
		</div>
	);
};
