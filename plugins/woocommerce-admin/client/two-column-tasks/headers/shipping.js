/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TimerImage from './timer.svg';
import Shipping from './illustrations/shipping.js';

const ShippingHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<Shipping class="svg-background" />
			<div className="woocommerce-task-header__contents">
				<h1>
					{ __(
						'Set up shipping for your store',
						'woocommerce-admin'
					) }
				</h1>
				<p>
					{ __(
						'Configure shipping zones and rates',
						'woocommerce-admin'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Add shipping zones', 'woocommerce-admin' ) }
				</Button>
				<p className="woocommerce-task-header__timer">
					<img src={ TimerImage } alt="Timer" />{ ' ' }
					<span>{ task.time }</span>
				</p>
			</div>
		</div>
	);
};

export default ShippingHeader;
