/**
 * External dependencies
 */
import clsx from 'clsx';
import { useEffect, useState } from '@wordpress/element';
import { EllipsisMenu } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME, WCDataSelector, WEEK } from '@woocommerce/data';
import { Button, Card, CardHeader } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import {
	ADMIN_INSTALL_TIMESTAMP_OPTION_NAME,
	ALLOW_TRACKING_OPTION_NAME,
	CustomerFeedbackModal,
	CustomerFeedbackSimple,
	SHOWN_FOR_ACTIONS_OPTION_NAME,
} from '@woocommerce/customer-effort-score';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './task-list-completed-header.scss';
import HeaderImage from '../assets/completed-celebration-header.svg';

type TaskListCompletedHeaderProps = {
	hideTasks: () => void;
	keepTasks: () => void;
	customerEffortScore: boolean;
};

const CUSTOMER_EFFORT_SCORE_ACTION = 'store_setup';

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

export const TaskListCompletedHeader: React.FC<
	TaskListCompletedHeaderProps
> = ( { hideTasks, keepTasks, customerEffortScore } ) => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const [ showCesModal, setShowCesModal ] = useState( false );
	const [ hasSubmittedScore, setHasSubmittedScore ] = useState( false );
	const [ score, setScore ] = useState( NaN );
	const [ hideCustomerEffortScore, setHideCustomerEffortScore ] =
		useState( false );
	const { storeAgeInWeeks, cesShownForActions, canShowCustomerEffortScore } =
		useSelect( ( select: WCDataSelector ) => {
			const { getOption, hasFinishedResolution } =
				select( OPTIONS_STORE_NAME );

			if ( customerEffortScore ) {
				const allowTracking = getOption( ALLOW_TRACKING_OPTION_NAME );
				const adminInstallTimestamp: number =
					getOption( ADMIN_INSTALL_TIMESTAMP_OPTION_NAME ) || 0;
				const cesActions = getOption< string[] >(
					SHOWN_FOR_ACTIONS_OPTION_NAME
				);
				const loadingOptions =
					! hasFinishedResolution( 'getOption', [
						SHOWN_FOR_ACTIONS_OPTION_NAME,
					] ) ||
					! hasFinishedResolution( 'getOption', [
						ADMIN_INSTALL_TIMESTAMP_OPTION_NAME,
					] );
				return {
					storeAgeInWeeks: getStoreAgeInWeeks(
						adminInstallTimestamp
					),
					cesShownForActions: cesActions,
					canShowCustomerEffortScore:
						! loadingOptions &&
						allowTracking &&
						! ( cesActions || [] ).includes( 'store_setup' ),
					loading: loadingOptions,
				};
			}
			return {};
		} );

	useEffect( () => {
		if ( hasSubmittedScore ) {
			setTimeout( () => {
				setHideCustomerEffortScore( true );
			}, 1200 );
		}
	}, [ hasSubmittedScore ] );

	const submitScore = ( {
		firstScore,
		secondScore,
		comments,
	}: {
		firstScore: number;
		secondScore?: number;
		comments?: string;
	} ) => {
		recordEvent( 'ces_feedback', {
			action: CUSTOMER_EFFORT_SCORE_ACTION,
			score: firstScore,
			score_second_question: secondScore ?? null,
			score_combined: firstScore + ( secondScore ?? 0 ),
			comments: comments || '',
			store_age: storeAgeInWeeks,
		} );
		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				CUSTOMER_EFFORT_SCORE_ACTION,
				...( cesShownForActions || [] ),
			],
		} );
		setHasSubmittedScore( true );
	};

	const recordScore = ( recordedScore: number ) => {
		if ( recordedScore > 2 ) {
			setScore( recordedScore );
			submitScore( { firstScore: recordedScore } );
		} else {
			setScore( recordedScore );
			setShowCesModal( true );
			recordEvent( 'ces_view', {
				action: CUSTOMER_EFFORT_SCORE_ACTION,
				store_age: storeAgeInWeeks,
			} );
		}
	};

	const recordModalScore = (
		firstScore: number,
		secondScore: number,
		comments: string
	) => {
		setShowCesModal( false );
		submitScore( { firstScore, secondScore, comments } );
	};

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
						<div className="woocommerce-task-card__header">
							<img
								src={ HeaderImage }
								alt="Completed"
								className="woocommerce-task-card__finished-header-image"
							/>

							<Text size="title" as="h2" lineHeight={ 1.4 }>
								{ __(
									'You’ve completed store setup',
									'woocommerce'
								) }
							</Text>
							<Text
								variant="subtitle.small"
								as="p"
								size="13"
								lineHeight="16px"
								className="woocommerce-task-card__header-subtitle"
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
					{ canShowCustomerEffortScore &&
						! hideCustomerEffortScore &&
						! hasSubmittedScore && (
							<CustomerFeedbackSimple
								label={ __(
									'How was your experience?',
									'woocommerce'
								) }
								onSelect={ recordScore }
							/>
						) }
					{ hasSubmittedScore && ! hideCustomerEffortScore && (
						<div className="woocommerce-task-card__header-ces-feedback">
							<Text
								variant="subtitle.small"
								as="p"
								size="13"
								lineHeight="16px"
							>
								🙌{ ' ' }
								{ __(
									'We appreciate your feedback!',
									'woocommerce'
								) }
							</Text>
						</div>
					) }
				</Card>
			</div>
			{ showCesModal ? (
				<CustomerFeedbackModal
					title={ __( 'How was your experience?', 'woocommerce' ) }
					firstQuestion={ __(
						'The store setup is easy to complete.',
						'woocommerce'
					) }
					secondQuestion={ __(
						'The store setup process meets my needs.',
						'woocommerce'
					) }
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
