/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Fragment, useState } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	SelectControl,
} from '@wordpress/components';
import clsx from 'clsx';
import interpolateComponents from '@automattic/interpolate-components';
import { useDispatch, useSelect } from '@wordpress/data';
import moment from 'moment';
import { Icon, chevronLeft, chevronRight, close } from '@wordpress/icons';
import {
	NOTES_STORE_NAME,
	QUERY_DEFAULTS,
	OPTIONS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	useUserPreferences,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	navigateTo,
	parseAdminUrl,
	getScreenFromPath,
	isWCAdmin,
} from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import sanitizeHTML from '../../lib/sanitize-html';
import StoreAlertsPlaceholder from './placeholder';
import { getAdminSetting } from '~/utils/admin-settings';
import { getScreenName } from '../../utils';
import { hasTwoColumnLayout } from '../../homescreen/layout';

import './style.scss';

const ALERTS_QUERY = {
	page: 1,
	per_page: QUERY_DEFAULTS.pageSize,
	type: 'error,update',
	status: 'unactioned',
};

function getUnactionedVisibleAlerts( alerts ) {
	return ( alerts || [] ).filter(
		( note ) => note.status === 'unactioned' && note.is_deleted !== true
	);
}

export const StoreAlerts = () => {
	const [ currentIndex, setCurrentIndex ] = useState( 0 );

	const {
		alerts = [],
		isLoading,
		defaultHomescreenLayout,
		taskListComplete,
		isTaskListHidden,
		isLoadingTaskLists,
	} = useSelect( ( select ) => {
		const { getNotes, hasFinishedResolution } = select( NOTES_STORE_NAME );
		const { getOption } = select( OPTIONS_STORE_NAME );
		const { getTaskList, hasFinishedResolution: taskListFinishResolution } =
			select( ONBOARDING_STORE_NAME );

		return {
			alerts: getUnactionedVisibleAlerts( getNotes( ALERTS_QUERY ) ),
			isLoading: ! hasFinishedResolution( 'getNotes', [ ALERTS_QUERY ] ),
			defaultHomescreenLayout:
				getOption( 'woocommerce_default_homepage_layout' ) ||
				'single_column',
			taskListComplete: getTaskList( 'setup' )?.isComplete,
			isTaskListHidden: getTaskList( 'setup' )?.isHidden,
			isLoadingTaskLists: ! taskListFinishResolution( 'getTaskLists' ),
		};
	} );

	const { triggerNoteAction, updateNote, removeNote } =
		useDispatch( NOTES_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );

	const userPrefs = useUserPreferences();

	function previousAlert( event ) {
		event?.stopPropagation();

		if ( currentIndex > 0 ) {
			setCurrentIndex( currentIndex - 1 );
		}
	}

	function nextAlert( event ) {
		event.stopPropagation();

		if ( currentIndex < alerts.length - 1 ) {
			setCurrentIndex( currentIndex + 1 );
		}
	}

	function renderActions( alert ) {
		const actions = alert.actions.map( ( action, idx ) => {
			const variant = idx === 0 ? 'secondary' : 'tertiary';
			return (
				<Button
					key={ action.name }
					variant={ variant }
					href={ action.url || undefined }
					onClick={ async ( event ) => {
						const url = event.currentTarget.getAttribute( 'href' );
						event.preventDefault();

						// navigate to previous alert to avoid an out of bounds error in case it's the last alert from the array
						previousAlert();
						try {
							await triggerNoteAction( alert.id, action.id );
							if (
								url &&
								url !== '#' &&
								parseAdminUrl( url ).href !==
									window.location.href
							) {
								navigateTo( { url } );
							}
						} catch ( e ) {
							createNotice(
								'error',
								__(
									`Something went wrong while triggering this note's action.`,
									'woocommerce'
								)
							);
							throw e;
						}
					} }
				>
					{ action.label }
				</Button>
			);
		} );

		// TODO: should "next X" be the start, or exactly 1X from the current date?
		const snoozeOptions = [
			{
				value: moment().add( 4, 'hours' ).unix().toString(),
				label: __( 'Later Today', 'woocommerce' ),
			},
			{
				value: moment()
					.add( 1, 'day' )
					.hour( 9 )
					.minute( 0 )
					.second( 0 )
					.millisecond( 0 )
					.unix()
					.toString(),
				label: __( 'Tomorrow', 'woocommerce' ),
			},
			{
				value: moment()
					.add( 1, 'week' )
					.hour( 9 )
					.minute( 0 )
					.second( 0 )
					.millisecond( 0 )
					.unix()
					.toString(),
				label: __( 'Next Week', 'woocommerce' ),
			},
			{
				value: moment()
					.add( 1, 'month' )
					.hour( 9 )
					.minute( 0 )
					.second( 0 )
					.millisecond( 0 )
					.unix()
					.toString(),
				label: __( 'Next Month', 'woocommerce' ),
			},
		];

		const setReminderDate = ( snoozeOption ) => {
			updateNote( alert.id, {
				status: 'snoozed',
				date_reminder: snoozeOption.value,
			} );

			const eventProps = {
				alert_name: alert.name,
				alert_title: alert.title,
				snooze_duration: snoozeOption.value,
				snooze_label: snoozeOption.label,
			};
			recordEvent( 'store_alert_snooze', eventProps );
		};

		const snooze = alert.is_snoozable && (
			<SelectControl
				className="woocommerce-store-alerts__snooze"
				options={ [
					{
						label: __( 'Remind Me Later', 'woocommerce' ),
						value: '0',
					},
					...snoozeOptions,
				] }
				onChange={ ( value ) => {
					if ( value === '0' ) {
						return;
					}

					const reminderOption = snoozeOptions.find(
						( option ) => option.value === value
					);
					const reminderDate = {
						value,
						label: reminderOption && reminderOption.label,
					};
					setReminderDate( reminderDate );
				} }
			/>
		);

		if ( actions || snooze ) {
			return (
				<div className="woocommerce-store-alerts__actions">
					{ actions }
					{ snooze }
				</div>
			);
		}
	}

	if ( isLoadingTaskLists ) {
		return null;
	}

	const preloadAlertCount = getAdminSetting( 'alertCount', 0, ( count ) =>
		parseInt( count, 10 )
	);

	const isWCAdminPage = isWCAdmin();
	const isHomescreen = isWCAdminPage && getScreenFromPath() === 'homescreen';

	const hasTwoColumns = hasTwoColumnLayout(
		userPrefs.homepage_layout,
		defaultHomescreenLayout,
		taskListComplete,
		isTaskListHidden
	);

	if ( preloadAlertCount > 0 && isLoading ) {
		return (
			<StoreAlertsPlaceholder
				className={ clsx( {
					'is-wc-admin-page': isWCAdminPage,
					'is-homescreen': isHomescreen,
					'two-columns': hasTwoColumns && isHomescreen,
				} ) }
				hasMultipleAlerts={ preloadAlertCount > 1 }
			/>
		);
	} else if ( alerts.length === 0 ) {
		return null;
	}

	const numberOfAlerts = alerts.length;
	const alert = alerts[ currentIndex ];
	const type = alert.type;
	const className = clsx( 'woocommerce-store-alerts', {
		'is-alert-error': type === 'error',
		'is-alert-update': type === 'update',
		'is-wc-admin-page': isWCAdminPage,
		'is-homescreen': isHomescreen,
		'two-columns': hasTwoColumns && isHomescreen,
	} );

	const onDismiss = async ( note ) => {
		const screen = getScreenName();

		recordEvent( 'inbox_action_dismiss', {
			note_name: note.name,
			note_title: note.title,
			note_name_dismiss_all: false,
			note_name_dismiss_confirmation: true,
			screen,
		} );

		const noteId = note.id;

		try {
			await removeNote( noteId );
			createNotice( 'success', __( 'Message dismissed', 'woocommerce' ) );
		} catch ( e ) {
			createNotice(
				'error',
				_n(
					'Message could not be dismissed',
					'Messages could not be dismissed',
					1,
					'woocommerce'
				)
			);
		}
	};

	return (
		<Card className={ className } size={ null }>
			<CardHeader
				className="woocommerce-store-alerts__header"
				isBorderless
			>
				<span className="woocommerce-store-alerts__title">
					{ alert.title }
				</span>
				{ numberOfAlerts > 1 && (
					<div className="woocommerce-store-alerts__pagination">
						<span
							className="woocommerce-store-alerts__pagination-label"
							role="status"
							aria-live="polite"
						>
							{ interpolateComponents( {
								mixedString: __(
									'{{current /}} of {{total /}}',
									'woocommerce'
								),
								components: {
									current: (
										<Fragment>
											{ currentIndex + 1 }
										</Fragment>
									),
									total: (
										<Fragment>{ numberOfAlerts }</Fragment>
									),
								},
							} ) }
						</span>
						<Button
							onClick={ previousAlert }
							disabled={ currentIndex === 0 }
							label={ __( 'Previous Alert', 'woocommerce' ) }
						>
							<Icon
								icon={ chevronLeft }
								className="arrow-left-icon"
							/>
						</Button>
						<Button
							onClick={ nextAlert }
							disabled={ numberOfAlerts - 1 === currentIndex }
							label={ __( 'Next Alert', 'woocommerce' ) }
						>
							<Icon
								icon={ chevronRight }
								className="arrow-right-icon"
							/>
						</Button>
					</div>
				) }
				<Button
					className="woocommerce-store-alerts__close"
					onClick={ () => onDismiss( alert ) }
				>
					<Icon width="18" height="18" icon={ close } />
				</Button>
			</CardHeader>
			<CardBody>
				<div
					className="woocommerce-store-alerts__message"
					dangerouslySetInnerHTML={ sanitizeHTML( alert.content ) }
				/>
			</CardBody>
			<CardFooter isBorderless>{ renderActions( alert ) }</CardFooter>
		</Card>
	);
};

export default StoreAlerts;
