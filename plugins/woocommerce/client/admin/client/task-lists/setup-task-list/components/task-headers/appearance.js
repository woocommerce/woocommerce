/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '../../../../utils/admin-settings';
import { useAppearanceClick } from '../../../fills/appearance';

const AppearanceHeader = ( { task } ) => {
	const { onClick } = useAppearanceClick();

	const taskTitle = task.title;
	const taskDescription = task.content;
	const taskCta = task.actionLabel;

	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Appearance illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL +
					'images/task_list/expand-section-illustration.png'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ taskTitle }</h1>
				<p>{ taskDescription }</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ onClick }
				>
					{ taskCta }
				</Button>
			</div>
		</div>
	);
};

export default AppearanceHeader;
