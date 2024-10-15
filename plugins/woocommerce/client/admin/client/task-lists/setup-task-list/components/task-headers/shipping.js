/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '../../../../utils/admin-settings';

const ShippingHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Shipping illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL + 'images/task_list/shipping-illustration.svg'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ __( 'Get your products shipped', 'woocommerce' ) }</h1>
				<p>
					{ __(
						'Choose where and how you’d like to ship your products, along with any fixed or calculated rates.',
						'woocommerce'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Start shipping', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default ShippingHeader;
