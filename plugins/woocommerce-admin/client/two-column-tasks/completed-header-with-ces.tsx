/**
 * External dependencies
 */
import classnames from 'classnames';
import { Button, Card, CardHeader } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { CustomerFeedbackSimple } from '@woocommerce/customer-effort-score';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './completed-header-with-ces.scss';
import HeaderImage from './completed-celebration-header.svg';
import React from 'react';
import { EllipsisMenu } from '@woocommerce/components';

type TaskListCompletedHeaderProps = {
	hideTasks: ( event: string ) => void;
	keepTasks: () => void;
	allowTracking: boolean;
};

export const TaskListCompletedHeaderWithCES: React.FC< TaskListCompletedHeaderProps > = ( {
	hideTasks,
	keepTasks,
	allowTracking,
} ) => {
	const recordScoreCallback = ( score: number ) => {};

	return (
		<>
			<div
				className={ classnames(
					'woocommerce-task-dashboard__container two-column-experiment'
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
									"You've completed store setup",
									'woocommerce'
								) }
							</h2>
							<Text
								variant="subtitle.small"
								as="p"
								size="13"
								lineHeight="16px"
								className="wooocommerce-task-card__header-subtitle"
							>
								{ __(
									'Congratulations! Take a moment to celebrate and look out for the first sale.',
									'woocommerce'
								) }
							</Text>
							<div className="woocommerce-task-card__header-menu">
								<EllipsisMenu
									label={ __(
										'Task List Options',
										'woocommerce'
									) }
									renderContent={ () => (
										<div className="woocommerce-task-card__section-controls">
											<Button
												onClick={ () => keepTasks() }
											>
												{ __(
													'Show setup task list',
													'woocommerce'
												) }
											</Button>
											<Button
												onClick={ () => hideTasks() }
											>
												{ __(
													'Hide this',
													'woocommerce'
												) }
											</Button>
										</div>
									) }
								/>
							</div>
						</div>
					</CardHeader>
					{ allowTracking && (
						<CustomerFeedbackSimple
							label={ __(
								'How was your experience?',
								'woocommerce'
							) }
							recordScoreCallback={ recordScoreCallback }
						/>
					) }
				</Card>
			</div>
		</>
	);
};
