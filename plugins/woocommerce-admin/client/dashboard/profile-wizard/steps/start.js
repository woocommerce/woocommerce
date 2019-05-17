/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal depdencies
 */
import { H } from '@woocommerce/components';
import ProfileWizardHeader from '../header';

export default class Start extends Component {
	render() {
		return (
			<Fragment>
				<ProfileWizardHeader />
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
			</Fragment>
		);
	}
}
