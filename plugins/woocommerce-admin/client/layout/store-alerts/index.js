/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { IconButton, Button, Dashicon, Dropdown, NavigableMenu } from '@wordpress/components';
import classnames from 'classnames';
import interpolateComponents from 'interpolate-components';
import { compose } from '@wordpress/compose';
import { noop } from 'lodash';
import { withDispatch } from '@wordpress/data';
import moment from 'moment';

/**
 * WooCommerce dependencies
 */
import { Card, DropdownButton } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import sanitizeHTML from 'lib/sanitize-html';
import StoreAlertsPlaceholder from './placeholder';

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
		const { updateNote } = this.props;
		const actions = alert.actions.map( action => {
			const markStatus = () => {
				updateNote( alert.id, { status: action.status } );
			};
			return (
				<Button
					key={ action.name }
					isDefault
					isPrimary={ action.primary }
					href={ action.url || undefined }
					onClick={ '' === action.status ? noop : markStatus }
				>
					{ action.label }
				</Button>
			);
		} );

		// TODO: should "next X" be the start, or exactly 1X from the current date?
		const snoozeOptions = [
			{
				newDate: moment()
					.add( 4, 'hours' )
					.unix(),
				label: __( 'Later Today', 'woocommerce-admin' ),
			},
			{
				newDate: moment()
					.add( 1, 'day' )
					.hour( 9 )
					.minute( 0 )
					.second( 0 )
					.millisecond( 0 )
					.unix(),
				label: __( 'Tomorrow', 'woocommerce-admin' ),
			},
			{
				newDate: moment()
					.add( 1, 'week' )
					.hour( 9 )
					.minute( 0 )
					.second( 0 )
					.millisecond( 0 )
					.unix(),
				label: __( 'Next Week', 'woocommerce-admin' ),
			},
			{
				newDate: moment()
					.add( 1, 'month' )
					.hour( 9 )
					.minute( 0 )
					.second( 0 )
					.millisecond( 0 )
					.unix(),
				label: __( 'Next Month', 'woocommerce-admin' ),
			},
		];

		const setReminderDate = ( newDate, onClose ) => {
			return () => {
				onClose();
				updateNote( alert.id, { status: 'snoozed', date_reminder: newDate } );
			};
		};

		const snooze = alert.is_snoozable && (
			<Dropdown
				className="woocommerce-store-alerts__snooze"
				position="bottom"
				expandOnMobile
				renderToggle={ ( { isOpen, onToggle } ) => (
					<DropdownButton
						onClick={ onToggle }
						isOpen={ isOpen }
						labels={ [ __( 'Remind Me Later', 'woocommerce-admin' ) ] }
					/>
				) }
				renderContent={ ( { onClose } ) => (
					<NavigableMenu className="components-dropdown-menu__menu">
						{ snoozeOptions.map( ( option, idx ) => (
							<Button
								className="components-dropdown-menu__menu-item"
								key={ idx }
								onClick={ setReminderDate( option.newDate, onClose ) }
							>
								{ option.label }
							</Button>
						) ) }
					</NavigableMenu>
				) }
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
		const preloadAlertCount = wcSettings.alertCount && parseInt( wcSettings.alertCount );

		if ( preloadAlertCount > 0 && this.props.isLoading ) {
			return <StoreAlertsPlaceholder hasMultipleAlerts={ preloadAlertCount > 1 } />;
		} else if ( 0 === alerts.length ) {
			return null;
		}

		const { currentIndex } = this.state;
		const numberOfAlerts = alerts.length;
		const alert = alerts[ currentIndex ];
		const type = alert.type;
		const className = classnames( 'woocommerce-store-alerts', 'woocommerce-analytics__card', {
			'is-alert-error': 'error' === type,
			'is-alert-update': 'update' === type,
		} );

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
							<IconButton
								icon="arrow-left-alt2"
								onClick={ this.previousAlert }
								disabled={ 0 === currentIndex }
								label={ __( 'Previous Alert', 'woocommerce-admin' ) }
							/>
							<span
								className="woocommerce-store-alerts__pagination-label"
								role="status"
								aria-live="polite"
							>
								{ interpolateComponents( {
									mixedString: __( '{{current /}} of {{total /}}', 'woocommerce-admin' ),
									components: {
										current: <Fragment>{ currentIndex + 1 }</Fragment>,
										total: <Fragment>{ numberOfAlerts }</Fragment>,
									},
								} ) }
							</span>
							<IconButton
								icon="arrow-right-alt2"
								onClick={ this.nextAlert }
								disabled={ numberOfAlerts - 1 === currentIndex }
								label={ __( 'Next Alert', 'woocommerce-admin' ) }
							/>
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
	withSelect( select => {
		const { getNotes, isGetNotesRequesting } = select( 'wc-api' );
		const alertsQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			type: 'error,update',
			status: 'unactioned',
		};

		// Filter out notes that may have been marked actioned or not delayed after the initial request
		const filterNotes = note => 'unactioned' === note.status;
		const alerts = getNotes( alertsQuery ).filter( filterNotes );

		const isLoading = isGetNotesRequesting( alertsQuery );

		return {
			alerts,
			isLoading,
		};
	} ),
	withDispatch( dispatch => {
		const { updateNote } = dispatch( 'wc-api' );
		return {
			updateNote,
		};
	} )
)( StoreAlerts );
