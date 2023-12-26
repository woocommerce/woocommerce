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

const CustomizeStoreHeader = ( {
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
				alt={ __( 'Customize your store illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL +
					'images/task_list/customize-store-illustration.svg'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ __( 'Start customizing your store', 'woocommerce' ) }</h1>
				<p>
					{ __(
						'Use our built-in AI tools to design your store and populate it with content, or select a pre-built theme and customize it to fit your brand.',
						'woocommerce'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Start customizing', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default CustomizeStoreHeader;
