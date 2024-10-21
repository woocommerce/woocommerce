/**
 * Internal dependencies
 */
import './placeholder.scss';

export type TasksPlaceholderProps = {
	numTasks?: number;
	query: {
		task?: string;
	};
};

export const TasksPlaceholder: React.FC< TasksPlaceholderProps > = ( {
	numTasks = 5,
	query,
} ) => {
	const isSingleTask = Boolean( query.task );

	if ( isSingleTask ) {
		return null;
	}

	return (
		<div className="woocommerce-task-dashboard__container">
			<div
				className="woocommerce-card woocommerce-task-card is-loading"
				aria-hidden
			>
				<div className="woocommerce-card__header">
					<div className="woocommerce-card__title-wrapper">
						<div className="woocommerce-card__title woocommerce-card__header-item">
							<span className="is-placeholder" />
						</div>
					</div>
				</div>
				<div className="woocommerce-card__body">
					<div className="woocommerce-list">
						{ Array.from( new Array( numTasks ) ).map( ( v, i ) => (
							<div
								key={ i }
								className="woocommerce-list__item has-action"
							>
								<div className="woocommerce-list__item-inner">
									<div className="woocommerce-list__item-before">
										<span className="is-placeholder" />
									</div>
									<div className="woocommerce-list__item-text">
										<div className="woocommerce-list__item-title">
											<span className="is-placeholder" />
										</div>
									</div>
									<div className="woocommerce-list__item-after">
										<span className="is-placeholder" />
									</div>
								</div>
							</div>
						) ) }
					</div>
				</div>
			</div>
		</div>
	);
};

export default TasksPlaceholder;
