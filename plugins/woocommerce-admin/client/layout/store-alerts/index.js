/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	SelectControl,
} from '@wordpress/components';
import classnames from 'classnames';
import interpolateComponents from '@automattic/interpolate-components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import moment from 'moment';
import { Icon, chevronLeft, chevronRight } from '@wordpress/icons';
import { NOTES_STORE_NAME, QUERY_DEFAULTS } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import sanitizeHTML from '../../lib/sanitize-html';
import StoreAlertsPlaceholder from './placeholder';
import { getAdminSetting } from '~/utils/admin-settings';

import './style.scss';

export class StoreAlerts extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			currentIndex: 0,
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
		const alerts = this.getAlerts();
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

	getAlerts() {
		return ( this.props.alerts || [] ).filter(
			( note ) => note.status === 'unactioned'
		);
	}

	render() {
		const alerts = this.getAlerts();
		const preloadAlertCount = getAdminSetting( 'alertCount', 0, ( count ) =>
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
		const className = classnames( 'woocommerce-store-alerts', {
			'is-alert-error': type === 'error',
			'is-alert-update': type === 'update',
		} );

		return (
			<Card className={ className } size={ null }>
				<CardHeader isBorderless>
					<Text
						variant="title.medium"
						as="h2"
						size="24"
						lineHeight="32px"
					>
						{ alert.title }
					</Text>
					{ numberOfAlerts > 1 && (
						<div className="woocommerce-store-alerts__pagination">
							<Button
								onClick={ this.previousAlert }
								disabled={ currentIndex === 0 }
								label={ __( 'Previous Alert', 'woocommerce' ) }
							>
								<Icon
									icon={ chevronLeft }
									className="arrow-left-icon"
								/>
							</Button>
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
								label={ __( 'Next Alert', 'woocommerce' ) }
							>
								<Icon
									icon={ chevronRight }
									className="arrow-right-icon"
								/>
							</Button>
						</div>
					) }
				</CardHeader>
				<CardBody>
					<div
						className="woocommerce-store-alerts__message"
						dangerouslySetInnerHTML={ sanitizeHTML(
							alert.content
						) }
					/>
				</CardBody>
				<CardFooter isBorderless>
					{ this.renderActions( alert ) }
				</CardFooter>
			</Card>
		);
	}
}

const ALERTS_QUERY = {
	page: 1,
	per_page: QUERY_DEFAULTS.pageSize,
	type: 'error,update',
	status: 'unactioned',
};

export default compose(
	withSelect( ( select ) => {
		const { getNotes, isResolving } = select( NOTES_STORE_NAME );

		// Filter out notes that may have been marked actioned or not delayed after the initial request

		const alerts = getNotes( ALERTS_QUERY );
		const isLoading = isResolving( 'getNotes', [ ALERTS_QUERY ] );

		return {
			alerts,
			isLoading,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { triggerNoteAction, updateNote } = dispatch( NOTES_STORE_NAME );

		return {
			triggerNoteAction,
			updateNote,
		};
	} )
)( StoreAlerts );
