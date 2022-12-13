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

const AddDomainHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Add a domain illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL +
					'images/task_list/add-domain-illustration.png'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>
					{ __( 'Claim your free domain', 'woocommerce' ) }
				</h1>
				<p>
					{ __(
						"Choose a new website address for your store – free of charge for one year, or transfer one you already own.",
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

export default AddDomainHeader;
