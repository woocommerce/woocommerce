/**
 * External dependencies
 */
import clsx from 'clsx';
import { Button, Card, CardHeader } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import HeaderImage from '../assets/task-list-completed.svg';

export const TaskListCompleted = ( {
	hideTasks,
	keepTasks,
}: {
	hideTasks: () => void;
	keepTasks: () => void;
} ) => {
	return (
		<>
			<div
				className={ clsx(
					'woocommerce-task-dashboard__container setup-task-list'
				) }
			>
				<Card
					size="large"
					className="woocommerce-task-card woocommerce-homescreen-card completed"
				>
					<CardHeader size="medium">
						<div className="wooocommerce-task-card__header">
							<img src={ HeaderImage } alt="Completed" />
							<h2>
								{ __(
									'You’ve completed store setup',
									'woocommerce'
								) }
							</h2>
							<Button isSecondary onClick={ keepTasks }>
								{ __( 'Keep list', 'woocommerce' ) }
							</Button>
							<Button isPrimary onClick={ hideTasks }>
								{ __( 'Hide this list', 'woocommerce' ) }
							</Button>
						</div>
					</CardHeader>
				</Card>
			</div>
		</>
	);
};

export default TaskListCompleted;
