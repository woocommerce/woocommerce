/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * Internal dependencies
 */

type TasksPlaceholderProps = {
	numTasks?: number;
	twoColumns?: boolean;
	query: {
		task?: string;
	};
};

export const TaskListPlaceholder: React.FC< TasksPlaceholderProps > = (
	props
) => {
	const { numTasks = 5 } = props;

	return (
		<div
			className={ clsx(
				'woocommerce-task-dashboard__container setup-task-list'
			) }
		>
			<div className="components-card is-size-large woocommerce-task-card woocommerce-homescreen-card is-loading">
				<div className="components-card__header is-size-medium">
					<div className="woocommerce-task-card__header">
						<div className="is-placeholder"> </div>
					</div>
				</div>
				<ul className="woocommerce-experimental-list">
					{ Array.from( new Array( numTasks ) ).map( ( v, i ) => (
						<li
							tabIndex={ i }
							key={ i }
							className="woocommerce-experimental-list__item woocommerce-task-list__item"
						>
							<div className="woocommerce-task-list__item-before">
								<div className="is-placeholder"></div>
							</div>
							<div className="woocommerce-task-list__item-text">
								<div className="components-truncate components-text is-placeholder"></div>
							</div>
						</li>
					) ) }
				</ul>
			</div>
		</div>
	);
};

export default TaskListPlaceholder;
