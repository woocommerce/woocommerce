/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { TaskType } from '@woocommerce/data';
/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '../../../../utils/admin-settings';

const LaunchYourStoreHeader = ( {
	task,
	goToTask,
}: {
	task: TaskType;
	goToTask: React.MouseEventHandler;
} ) => {
	return (
		<div
			className={ `woocommerce-task-header__contents-container woocommerce-task-header__${ task.id }` }
		>
			<img
				alt={ __( 'Launch Your Store illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL +
					'images/task_list/launch-your-store-illustration.svg'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>
					{ __( 'Your store is ready for launch!', 'woocommerce' ) }
				</h1>
				<p>
					{ __(
						"It's time to celebrate – you're ready to launch your store! Woo! Hit the button to preview your store and make it public.",
						'woocommerce'
					) }
				</p>
				<Button
					variant={ task.isComplete ? 'secondary' : 'primary' }
					onClick={ goToTask }
				>
					{ __( 'Launch store', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default LaunchYourStoreHeader;
