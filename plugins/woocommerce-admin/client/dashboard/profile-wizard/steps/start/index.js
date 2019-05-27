/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, CheckboxControl, FormToggle } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import interpolateComponents from 'interpolate-components';

/**
 * Internal depdencies
 */
import { Card, H, Link } from '@woocommerce/components';
import SecurityIcon from './images/security';
import SalesTaxIcon from './images/local_atm';
import SpeedIcon from './images/flash_on';
import MobileAppIcon from './images/phone_android';
import './style.scss';

const benefits = [
	{
		title: __( 'Security', 'woocommerce-admin' ),
		icon: <SecurityIcon />,
		description: __(
			'Jetpack automatically blocks brute force attacks to protect your store from unauthorized access.',
			'woocommerce-admin'
		),
	},
	{
		title: __( 'Sales Tax', 'woocommerce-admin' ),
		icon: <SalesTaxIcon />,
		description: __(
			'With WooCommerce Services we ensure that the correct rate of tax is charged on all of your orders.',
			'woocommerce-admin'
		),
	},
	{
		title: __( 'Speed', 'woocommerce-admin' ),
		icon: <SpeedIcon />,
		description: __(
			'Cache your images and static files on our own powerful global network of servers and speed up your site.',
			'woocommerce-admin'
		),
	},
	{
		title: __( 'Mobile App', 'woocommerce-admin' ),
		icon: <MobileAppIcon />,
		description: __(
			'Your store in your pocket. Manage orders, receive sales notifications, and more. Only with a Jetpack connection.',
			'woocommerce-admin'
		),
	},
];

export default class Start extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			trackingChecked: true,
		};

		this.onTrackingChange = this.onTrackingChange.bind( this );
	}

	skipWizard() {
		// @todo This should close the wizard and set the `skipped` property to true via the API.
	}

	startWizard() {
		// @todo This should go to the next step.
	}

	onTrackingChange() {
		this.setState( {
			trackingChecked: ! this.state.trackingChecked,
		} );
	}

	renderBenefit( benefit ) {
		const { description, icon, title } = benefit;

		return (
			<div className="woocommerce-profile-wizard__benefit" key={ title }>
				{ icon }
				<div className="woocommerce-profile-wizard__benefit-content">
					<H className="woocommerce-profile-wizard__benefit-title">{ title }</H>
					<p>{ description }</p>
				</div>
			</div>
		);
	}

	render() {
		const { trackingChecked } = this.state;

		const trackingLabel = interpolateComponents( {
			mixedString: __(
				'Help improve WooCommerce with usage tracking. {{link}}Learn More.{{/link}}',
				'woocommerce-admin'
			),
			components: {
				link: (
					<Link href="https://woocommerce.com/usage-tracking" target="_blank" type="external" />
				),
			},
		} );

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'Start setting up your WooCommerce store', 'woocommerce-admin' ) }
				</H>

				<p>
					{ interpolateComponents( {
						mixedString: __(
							'Simplify and enhance the setup of your store with features and benefits offered by ' +
								'{{strong}}Jetpack & WooCommerce Services{{/strong}}.',
							'woocommerce-admin'
						),
						components: {
							strong: <strong />,
						},
					} ) }
				</p>

				<div className="woocommerce-profile-wizard__tracking">
					<CheckboxControl
						className="woocommerce-profile-wizard__tracking-checkbox"
						checked={ trackingChecked }
						label={ __( trackingLabel, 'woocommerce-admin' ) }
						onChange={ this.onTrackingChange }
					/>

					<FormToggle
						aria-hidden="true"
						checked={ trackingChecked }
						onChange={ this.onTrackingChange }
						onClick={ e => e.stopPropagation() }
						tabIndex="-1"
					/>
				</div>

				<Card>
					<div className="woocommerce-profile-wizard__benefits">
						{ benefits.map( benefit => this.renderBenefit( benefit ) ) }
					</div>

					<Button isPrimary onClick={ this.startWizard }>
						{ __( 'Get started', 'woocommerce-admin' ) }
					</Button>
				</Card>

				<p>
					<Link href="#" onClick={ this.skipWizard }>
						{ __( 'Proceed without Jetpack or WooCommerce Services', 'woocommerce-admin' ) }
					</Link>
				</p>
			</Fragment>
		);
	}
}
