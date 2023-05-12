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

const PaymentsHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Payment illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL + 'images/task_list/payment-illustration.png'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ __( 'Add a way to get paid', 'woocommerce' ) }</h1>
				<p>
					{ __(
						'Choose from fast & secure online and offline payment methods to make it easy for your customers to pay in your store.',
						'woocommerce'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'View options', 'woocommerce' ) }
				</Button>
				<p className="woocommerce-task-header__timer">
					<img src={ TimerImage } alt="Timer" />{ ' ' }
					<span>{ task.time }</span>
				</p>
			</div>
		</div>
	);
};

export default PaymentsHeader;
