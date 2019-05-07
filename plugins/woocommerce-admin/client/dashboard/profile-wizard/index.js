/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';

/**
 * Internal depdencies
 */
import './style.scss';
import { H } from '@woocommerce/components';

export default class ProfileWizard extends Component {
	componentDidMount() {
		document.body.classList.add( 'woocommerce-profile-wizard__body' );
	}

	componentWillUnmount() {
		document.body.classList.remove( 'woocommerce-profile-wizard__body' );
	}

	render() {
		return (
			<div className="woocommerce-profile-wizard__container">
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'Start setting up your WooCommerce store', 'woocommerce-admin' ) }
				</H>

				<p>
					{ __(
						'Simplify and enhance the setup of your store with features and benefits offered by Jetpack ' +
							'& WooCommerce Services.',
						'woocommerce-admin'
					) }
				</p>
			</div>
		);
	}
}
