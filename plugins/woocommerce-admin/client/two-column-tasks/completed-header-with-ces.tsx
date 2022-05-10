/**
 * External dependencies
 */
import classnames from 'classnames';
import { useEffect, useState } from '@wordpress/element';
import { EllipsisMenu } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME, WCDataSelector, WEEK } from '@woocommerce/data';
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
	showCES: boolean;
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
	showCES,
} ) => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const [ showCesModal, setShowCesModal ] = useState( false );
	const [ submittedScore, setSubmittedScore ] = useState( false );
	const [ score, setScore ] = useState( NaN );
	const [ hideCES, setHideCES ] = useState( false );
	const { storeAgeInWeeks, cesShownForActions } = useSelect(
		( select: WCDataSelector ) => {
			const { getOption } = select( OPTIONS_STORE_NAME );

			if ( showCES ) {
				const adminInstallTimestamp: number =
					getOption( ADMIN_INSTALL_TIMESTAMP_OPTION_NAME ) || 0;
				return {
					storeAgeInWeeks: getStoreAgeInWeeks(
						adminInstallTimestamp
					),
					cesShownForActions:
						getOption< string[] >(
							SHOWN_FOR_ACTIONS_OPTION_NAME
						) || [],
				};
			}
			return {};
		}
	);

	useEffect( () => {
		if ( submittedScore ) {
			setTimeout( () => {
				setHideCES( true );
			}, 1200 );
		}
	}, [ submittedScore ] );

	const submitScore = ( recordedScore: number, comments?: string ) => {
		recordEvent( 'ces_feedback', {
			action: CES_ACTION,
			score: recordedScore,
			comments: comments || '',
			store_age: storeAgeInWeeks,
		} );
		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				CES_ACTION,
				...( cesShownForActions || [] ),
			],
		} );
		setSubmittedScore( true );
	};

	const recordScore = ( recordedScore: number ) => {
		if ( recordedScore > 2 ) {
			setScore( recordedScore );
			submitScore( recordedScore );
		} else {
			setScore( recordedScore );
			setShowCesModal( true );
			recordEvent( 'ces_view', {
				action: CES_ACTION,
				store_age: storeAgeInWeeks,
			} );
		}
	};

	const recordModalScore = ( recordedScore: number, comments: string ) => {
		setShowCesModal( false );
		submitScore( recordedScore, comments );
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
							<img
								src={ HeaderImage }
								alt="Completed"
								className="wooocommerce-task-card__finished-header-image"
							/>

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
					{ showCES && ! hideCES && (
						<CustomerFeedbackSimple
							label={ __(
								'How was your experience?',
								'woocommerce'
							) }
							showFeedback={ submittedScore }
							recordScoreCallback={ recordScore }
							feedbackScore={ score }
						/>
					) }
				</Card>
			</div>
			{ showCesModal ? (
				<CustomerFeedbackModal
					label={ __( 'How was your experience?', 'woocommerce' ) }
					defaultScore={ score }
					recordScoreCallback={ recordModalScore }
					onCloseModal={ () => {
						setScore( NaN );
						setShowCesModal( false );
					} }
				/>
			) : null }
		</>
	);
};
