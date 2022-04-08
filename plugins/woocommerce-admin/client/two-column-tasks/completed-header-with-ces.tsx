/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState } from '@wordpress/element';
import { EllipsisMenu } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME, WEEK } from '@woocommerce/data';
import { Button, Card, CardHeader } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import {
	CustomerFeedbackModal,
	CustomerFeedbackSimple,
} from '@woocommerce/customer-effort-score';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './completed-header-with-ces.scss';
import HeaderImage from './completed-celebration-header.svg';

type TaskListCompletedHeaderProps = {
	hideTasks: () => void;
	keepTasks: () => void;
	allowTracking: boolean;
};

const ADMIN_INSTALL_TIMESTAMP_OPTION_NAME =
	'woocommerce_admin_install_timestamp';
const SHOWN_FOR_ACTIONS_OPTION_NAME = 'woocommerce_ces_shown_for_actions';
const CES_ACTION = 'store_setup';

function getStoreAgeInWeeks( adminInstallTimestamp: number ) {
	if ( adminInstallTimestamp === 0 ) {
		return null;
	}

	// Date.now() is ms since Unix epoch, adminInstallTimestamp is in
	// seconds since Unix epoch.
	const storeAgeInMs = Date.now() - adminInstallTimestamp * 1000;
	const storeAgeInWeeks = Math.round( storeAgeInMs / WEEK );

	return storeAgeInWeeks;
}

export const TaskListCompletedHeaderWithCES: React.FC< TaskListCompletedHeaderProps > = ( {
	hideTasks,
	keepTasks,
	allowTracking,
} ) => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const [ showCesModal, setShowCesModal ] = useState( false );
	const [ score, setScore ] = useState( NaN );
	const [ showFeedback, setShowFeedback ] = useState( false );
	const { storeAgeInWeeks, cesShownForActions } = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );

		const adminInstallTimestamp: number =
			getOption( ADMIN_INSTALL_TIMESTAMP_OPTION_NAME ) || 0;
		return {
			storeAgeInWeeks: getStoreAgeInWeeks( adminInstallTimestamp ),
			cesShownForActions:
				getOption( SHOWN_FOR_ACTIONS_OPTION_NAME ) || [],
		};
	} );

	const recordScore = ( score: number ) => {
		if ( score > 2 ) {
			recordEvent( 'ces_feedback', {
				action: CES_ACTION,
				score,
				store_age: storeAgeInWeeks,
			} );
			updateOptions( {
				[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
					CES_ACTION,
					...cesShownForActions,
				],
			} );
		} else {
			setScore( score );
			setShowCesModal( true );
		}
	};

	const recordModalScore = ( score: number, comments: string ) => {
		setShowCesModal( false );
		recordEvent( 'ces_feedback', {
			action: 'store_setup',
			score,
			comments: comments || '',
			store_age: storeAgeInWeeks,
		} );
		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				CES_ACTION,
				...cesShownForActions,
			],
		} );
		setShowFeedback( true );
	};

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
							recordScoreCallback={ recordScore }
							showFeedback={ showFeedback }
						/>
					) }
				</Card>
			</div>
			{ showCesModal ? (
				<CustomerFeedbackModal
					label={ __( 'How was your experience?', 'woocommerce' ) }
					defaultScore={ score }
					recordScoreCallback={ recordModalScore }
				/>
			) : null }
		</>
	);
};
