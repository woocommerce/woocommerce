/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Header from 'components/header';
import { getAdminLink } from 'lib/nav-utils';

export default class extends Component {
	render() {
		return (
			<Fragment>
				<Header
					sections={ [
						<a href={ getAdminLink( '/analytics' ) }>{ __( 'Analytics', 'woo-dash' ) }</a>,
						__( 'Report Title', 'woo-dash' ),
					] }
				/>
				<div>Report: { this.props.params.report }</div>
			</Fragment>
		);
	}
}
