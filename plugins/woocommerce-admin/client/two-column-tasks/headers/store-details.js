/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TimerImage from './timer.svg';
import { WC_ASSET_URL } from '../../utils/admin-settings';

const StoreDetailsHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Store location illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL +
					'images/task_list/sales-section-illustration.png'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ __( 'Add your store location', 'woocommerce' ) }</h1>
				<p>
					{ __(
						'Add your store location details such as address and Country to help us configure shipping, taxes, currency and more in a fully automated way.',
						'woocommerce'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Add store details', 'woocommerce' ) }
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
