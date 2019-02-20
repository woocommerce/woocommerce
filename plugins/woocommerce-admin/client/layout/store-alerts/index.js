/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { IconButton, Button, Dashicon } from '@wordpress/components';
import classnames from 'classnames';
import interpolateComponents from 'interpolate-components';

/**
 * WooCommerce dependencies
 */
import { Card } from '@woocommerce/components';

import './style.scss';

const dummy = [
	{
		title: 'Lorem ipsum dolor sit amet',
		type: 'alert',
		message:
			'Pellentesque accumsan ligula in aliquam tristique. Donec elementum magna ut sapien tincidunt aliquam.',
		action: {
			label: 'Button',
			url: '#',
		},
	},
	{
		title: 'Sed bibendum non augue tincidunt mollis',
		type: 'critical',
		message: 'Quisque in efficitur nisi. In hac habitasse platea dictumst. Vivamus ut congue diam.',
		action: {
			label: 'Button',
			url: '#',
		},
	},
	{
		title: 'Duis dictum condimentum sem eu blandit',
		type: 'emergency',
		message: 'Fusce fermentum magna dolor, vitae faucibus justo ullamcorper eu.',
		action: {
			label: 'Button',
			url: '#',
		},
	},
];

const alertTypes = {
	emergency: {
		label: __( 'Emergency', 'wc-admin' ),
		icon: 'warning',
	},
	alert: {
		label: __( 'Alert', 'wc-admin' ),
		icon: 'flag',
	},
	critical: {
		label: __( 'Critical', 'wc-admin' ),
		icon: 'info',
	},
};

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
		const { currentIndex } = this.state;
		const numberOfAlerts = alerts.length;
		const alert = alerts[ currentIndex ] ? alerts[ currentIndex ] : null;
		const type = alert && alert.type ? alert.type : null;
		const icon = type && alertTypes[ type ] ? alertTypes[ type ].icon : null;

		const className = classnames( 'woocommerce-store-alerts', {
			'is-alert-emergency': 'emergency' === type,
			'is-alert-alert': 'alert' === type,
			'is-alert-critical': 'critical' === type,
		} );
		return (
			numberOfAlerts > 0 && (
				<Card
					title={ [
						icon && <Dashicon key="icon" icon={ icon } />,
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
					<div className="woocommerce-store-alerts__message">{ alert.message }</div>
					<Button isPrimary className="woocommerce-store-alerts__button" href={ alert.action.url }>
						{ alert.action.label }
					</Button>
				</Card>
			)
		);
	}
}

export default StoreAlerts;

StoreAlerts.defaultProps = {
	alerts: dummy || [],
};
