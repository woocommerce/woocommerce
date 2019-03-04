/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { IconButton, Button, Dashicon } from '@wordpress/components';
import classnames from 'classnames';
import interpolateComponents from 'interpolate-components';
import { compose } from '@wordpress/compose';

/**
 * WooCommerce dependencies
 */
import { Card } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import sanitizeHTML from 'lib/sanitize-html';

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

	render() {
		const { alerts } = this.props;

		if ( ! alerts || alerts.length === 0 ) {
			return null;
		}

		const { currentIndex } = this.state;
		const numberOfAlerts = alerts.length;
		const alert = alerts[ currentIndex ] ? alerts[ currentIndex ] : null;
		const type = alert && alert.type ? alert.type : null;
		const className = classnames( 'woocommerce-store-alerts', {
			'is-alert-error': 'error' === type,
			'is-alert-update': 'update' === type,
		} );
		const actions =
			alert &&
			alert.actions.map( action => (
				<Button key={ action.name } isDefault href={ action.url }>
					{ action.label }
				</Button>
			) );

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
								label={ __( 'Previous Alert', 'wc-admin' ) }
							/>
							<span
								className="woocommerce-store-alerts__pagination-label"
								role="status"
								aria-live="polite"
							>
								{ interpolateComponents( {
									mixedString: __( '{{current /}} of {{total /}}', 'wc-admin' ),
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
								label={ __( 'Next Alert', 'wc-admin' ) }
							/>
						</div>
					)
				}
			>
				<div
					className="woocommerce-store-alerts__message"
					dangerouslySetInnerHTML={ sanitizeHTML( alert.content ) }
				/>
				{ actions && <div className="woocommerce-store-alerts__actions">{ actions }</div> }
			</Card>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getNotes } = select( 'wc-api' );
		const alertsQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			type: 'error,update',
		};

		const alerts = getNotes( alertsQuery );

		return { alerts };
	} )
)( StoreAlerts );
