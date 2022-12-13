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

const LaunchSiteHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Launch your store illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL +
					'images/task_list/store-launch-illustration.png'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>
					{ __( 'Your store is ready for launch!', 'woocommerce' ) }
				</h1>
				<p>
					{ __(
						"It's time to celebrate – you're ready to launch your store! Woo!",
						'woocommerce'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Launch your store', 'woocommerce' ) }
				</Button>
				<p className="woocommerce-task-header__timer">
					<img src={ TimerImage } alt="Timer" />{ ' ' }
					<span>{ task.time }</span>
				</p>
			</div>
		</div>
	);
};

export default LaunchSiteHeader;
