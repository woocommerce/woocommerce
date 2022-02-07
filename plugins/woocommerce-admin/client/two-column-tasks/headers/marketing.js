/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TimerImage from './timer.svg';
import GetMoreSales from './illustrations/get-more-sales.js';

const MarketingHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<GetMoreSales className="svg-background" />
			<div className="woocommerce-task-header__contents">
				<h1>{ __( 'Get more sales', 'woocommerce-admin' ) }</h1>
				<p>
					{ __(
						'Add tools to grow your store such as email, social, and in-person selling',
						'woocommerce-admin'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Add selling tools', 'woocommerce-admin' ) }
				</Button>
				<p className="woocommerce-task-header__timer">
					<img src={ TimerImage } alt="Timer" />{ ' ' }
					<span>{ task.time }</span>
				</p>
			</div>
		</div>
	);
};

export default MarketingHeader;
