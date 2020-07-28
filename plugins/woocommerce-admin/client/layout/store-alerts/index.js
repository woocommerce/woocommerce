/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Button, Dashicon, SelectControl } from '@wordpress/components';
import classnames from 'classnames';
import interpolateComponents from 'interpolate-components';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import moment from 'moment';
import { Icon, chevronLeft, chevronRight } from '@wordpress/icons';

/**
 * WooCommerce dependencies
 */
import { Card } from '@woocommerce/components';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import sanitizeHTML from 'lib/sanitize-html';
import StoreAlertsPlaceholder from './placeholder';
import { recordEvent } from 'lib/tracks';

import './style.scss';

class StoreAlerts extends Component {
	constructor( props ) {
		super( props );
		const { alerts } = this.props;

		this.state = {
			currentIndex: alerts ? 0 : null,
		};

		this.previousAlert = this.previousAlert.bind( this );
		this.nextAlert = this.nextAlert.bind( this );
	}

	previousAlert( event ) {
		event.stopPropagation();
		const { currentIndex } = this.state;

		if ( currentIndex > 0 ) {
			this.setState( {
				currentIndex: currentIndex - 1,
			} );
		}
	}

	nextAlert( event ) {
		event.stopPropagation();
		const { alerts } = this.props;
		const { currentIndex } = this.state;

		if ( currentIndex < alerts.length - 1 ) {
			this.setState( {
				currentIndex: currentIndex + 1,
			} );
		}
	}

	renderActions( alert ) {
		const { triggerNoteAction, updateNote } = this.props;
		const actions = alert.actions.map( ( action ) => {
			return (
				<Button
					key={ action.name }
					isPrimary={ action.primary }
					isSecondary={ ! action.primary }
					href={ action.url || undefined }
					onClick={ () => triggerNoteAction( alert.id, action.id ) }
				>
					{ action.label }
				</Button>
			);
		} );

		// TODO: should "next X" be the start, or exactly 1X from the current date?
		const snoozeOptions = [
			{
				value: moment().add( 4, 'hours' ).unix().toString(),
				label: __( 'Later Today', 'woocommerce-admin' ),
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
				label: __( 'Tomorrow', 'woocommerce-admin' ),
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
				label: __( 'Next Week', 'woocommerce-admin' ),
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
				label: __( 'Next Month', 'woocommerce-admin' ),
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
						label: __( 'Remind Me Later', 'woocommerce-admin' ),
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

	render() {
		const alerts = this.props.alerts || [];
		const preloadAlertCount = getSetting( 'alertCount', 0, ( count ) =>
			parseInt( count, 10 )
		);

		if ( preloadAlertCount > 0 && this.props.isLoading ) {
			return (
				<StoreAlertsPlaceholder
					hasMultipleAlerts={ preloadAlertCount > 1 }
				/>
			);
		} else if ( alerts.length === 0 ) {
			return null;
		}

		const { currentIndex } = this.state;
		const numberOfAlerts = alerts.length;
		const alert = alerts[ currentIndex ];
		const type = alert.type;
		const className = classnames(
			'woocommerce-store-alerts',
			'woocommerce-analytics__card',
			{
				'is-alert-error': type === 'error',
				'is-alert-update': type === 'update',
			}
		);

		return (
			<Card
				title={ [
					alert.icon && <Dashicon key="icon" icon={ alert.icon } />,
					<Fragment key="title">{ alert.title }</Fragment>,
				] }
				className={ className }
				action={
					numberOfAlerts > 1 && (
						<div className="woocommerce-store-alerts__pagination">
							<Button
								onClick={ this.previousAlert }
								disabled={ currentIndex === 0 }
								label={ __(
									'Previous Alert',
									'woocommerce-admin'
								) }
							>
								<Icon icon={ chevronLeft } />
							</Button>
							<span
								className="woocommerce-store-alerts__pagination-label"
								role="status"
								aria-live="polite"
							>
								{ interpolateComponents( {
									mixedString: __(
										'{{current /}} of {{total /}}',
										'woocommerce-admin'
									),
									components: {
										current: (
											<Fragment>
												{ currentIndex + 1 }
											</Fragment>
										),
										total: (
											<Fragment>
												{ numberOfAlerts }
											</Fragment>
										),
									},
								} ) }
							</span>
							<Button
								onClick={ this.nextAlert }
								disabled={ numberOfAlerts - 1 === currentIndex }
								label={ __(
									'Next Alert',
									'woocommerce-admin'
								) }
							>
								<Icon icon={ chevronRight } />
							</Button>
						</div>
					)
				}
			>
				<div
					className="woocommerce-store-alerts__message"
					dangerouslySetInnerHTML={ sanitizeHTML( alert.content ) }
				/>
				{ this.renderActions( alert ) }
			</Card>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getNotes, isGetNotesRequesting } = select( 'wc-api' );
		const alertsQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			type: 'error,update',
			status: 'unactioned',
		};

		// Filter out notes that may have been marked actioned or not delayed after the initial request
		const filterNotes = ( note ) => note.status === 'unactioned';
		const alerts = getNotes( alertsQuery ).filter( filterNotes );

		const isLoading = isGetNotesRequesting( alertsQuery );

		return {
			alerts,
			isLoading,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { triggerNoteAction, updateNote } = dispatch( 'wc-api' );

		return {
			triggerNoteAction,
			updateNote,
		};
	} )
)( StoreAlerts );
