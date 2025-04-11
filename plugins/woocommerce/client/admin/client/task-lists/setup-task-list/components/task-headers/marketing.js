/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '../../../../utils/admin-settings';

const MarketingHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Marketing illustration', 'woocommerce' ) }
				src={ WC_ASSET_URL + 'images/task_list/sales-illustration.svg' }
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ __( 'Reach more customers', 'woocommerce' ) }</h1>
				<p>
					{ __(
						'Start growing your business by showcasing your products on social media and Google, boost engagement with email marketing, and more!',
						'woocommerce'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Grow your business', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default MarketingHeader;
