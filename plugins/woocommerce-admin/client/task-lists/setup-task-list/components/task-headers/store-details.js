/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TimerImage from './timer.svg';
import { WC_ASSET_URL } from '../../../../utils/admin-settings';

const StoreDetailsHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Store location illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL +
					'images/task_list/store-details-illustration.png'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>
					{ __( 'First, tell us about your store', 'woocommerce' ) }
				</h1>
				<p>
					{ __(
						"Get your store up and running in no time. Add your store's address to set up shipping, tax and payments faster.",
						'woocommerce'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Add details', 'woocommerce' ) }
				</Button>
				<p className="woocommerce-task-header__timer">
					<img src={ TimerImage } alt="Timer" />{ ' ' }
					<span>{ __( '2 minutes', 'woocommerce' ) }</span>
				</p>
			</div>
		</div>
	);
};

export default StoreDetailsHeader;
