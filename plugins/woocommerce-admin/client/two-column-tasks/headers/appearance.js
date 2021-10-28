/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TimerImage from './timer.svg';
import PersonalizeMyStore from './illustrations/personalize-my-store.js';

const AppearanceHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<PersonalizeMyStore class="svg-background" />
			<div className="woocommerce-task-header__contents">
				<h1>{ __( 'Personalize my store', 'woocommerce-admin' ) }</h1>
				<p>
					{ __(
						'Add your logo, create a homepage, and start designing your store',
						'woocommerce-admin'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ task.isComplete
						? __( 'Modify choices', 'woocommerce-admin' )
						: __( 'Personalize', 'woocommerce-admin' ) }
				</Button>
				<p className="woocommerce-task-header__timer">
					<img src={ TimerImage } alt="Timer" />{ ' ' }
					<span>{ task.time }</span>
				</p>
			</div>
		</div>
	);
};

export default AppearanceHeader;
