/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { TaskType } from '@woocommerce/data';

interface DefaultTaskHeaderProps {
	task: TaskType;
	goToTask: () => void;
}

/**
 * Generic task header used for tasks that provide contextual image metadata
 * (imageUrl/imageAlt) but do not register a dedicated header component or a
 * WooOnboardingTaskListHeader SlotFill. This lets third-party tasks render a
 * header on My Home using the same metadata consumed by the legacy dashboard
 * setup widget.
 */
const DefaultTaskHeader = ( { task, goToTask }: DefaultTaskHeaderProps ) => {
	if ( ! task.imageUrl ) {
		return null;
	}

	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ task.imageAlt || '' }
				src={ task.imageUrl }
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ task.title }</h1>
				<p>{ task.content }</p>
				<Button
					variant={ task.isComplete ? 'secondary' : 'primary' }
					onClick={ goToTask }
				>
					{ task.actionLabel || __( "Let's go", 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default DefaultTaskHeader;
