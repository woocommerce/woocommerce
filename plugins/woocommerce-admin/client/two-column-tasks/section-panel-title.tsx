/**
 * External dependencies
 */
import { Badge } from '@woocommerce/components';
import { TaskListSection, TaskType } from '@woocommerce/data';
import { Icon, check } from '@wordpress/icons';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import SectionHeader from './headers/section-header';

type SectionPanelTitleProps = {
	section: TaskListSection;
	active: boolean;
	tasks: TaskType[];
};

export const SectionPanelTitle: React.FC< SectionPanelTitleProps > = ( {
	section,
	active,
	tasks,
} ) => {
	if ( active ) {
		return (
			<div className="wooocommerce-task-card__header-container">
				<div className="wooocommerce-task-card__header">
					<SectionHeader { ...section } />
				</div>
			</div>
		);
	}

	const uncompletedTasksCount = tasks.filter(
		( task ) => ! task.isComplete && section.tasks.includes( task.id )
	).length;
	const isComplete = section.isComplete || uncompletedTasksCount === 0;

	return (
		<>
			<Text
				className="woocommerce-task-header-collapsed"
				variant="title.small"
				size="20"
				lineHeight="28px"
			>
				{ section.title }
			</Text>
			{ ! isComplete && <Badge count={ uncompletedTasksCount } /> }
			{ isComplete && (
				<div className="woocommerce-task__icon">
					<Icon icon={ check } />
				</div>
			) }
		</>
	);
};
