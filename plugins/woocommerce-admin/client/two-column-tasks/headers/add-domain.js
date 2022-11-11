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
				alt={ __( 'Add a domain illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL +
					'images/task_list/store-details-illustration.png'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>
					{ __( 'Claim your free domain', 'woocommerce' ) }
				</h1>
				<p>
					{ __(
						"You have a free one-year domain registration or transfer included with your plan.",
						'woocommerce'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Add a domain', 'woocommerce' ) }
				</Button>
				<p className="woocommerce-task-header__timer">
					<img src={ TimerImage } alt="Timer" />{ ' ' }
					<span>{ task.time }</span>
				</p>
			</div>
		</div>
	);
};

export default StoreDetailsHeader;
