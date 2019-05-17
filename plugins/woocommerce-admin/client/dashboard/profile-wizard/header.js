/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import HeaderLogo from './header-logo';
export default class ProfileWizardHeader extends Component {
	render() {
		return (
			<div className="woocommerce-profile-wizard__header">
				<HeaderLogo />
			</div>
		);
	}
}
