/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Link } from 'react-router-dom';

/**
 * Internal dependencies
 */
import Header from 'components/header';

export default class extends Component {
	render() {
		return (
			<Fragment>
				<Header
					sections={ [
						<Link to="/analytics">{ __( 'Analytics', 'woo-dash' ) }</Link>,
						__( 'Report Title', 'woo-dash' ),
					] }
				/>
				<div>Report: { this.props.params.report }</div>
			</Fragment>
		);
	}
}
