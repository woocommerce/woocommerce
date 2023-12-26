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

const MarketingHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Marketing illustration', 'woocommerce' ) }
				src={ WC_ASSET_URL + 'images/task_list/sales-illustration.png' }
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ __( 'Get more sales', 'woocommerce' ) }</h1>
				<p>
					{ __(
						'Give your business a boost by enabling social channels such as newsletter, Facebook, Google, in-person selling, and more.',
						'woocommerce'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Add sales channels', 'woocommerce' ) }
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
