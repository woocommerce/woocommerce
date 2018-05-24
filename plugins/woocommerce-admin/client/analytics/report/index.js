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

export default class extends Component {
	render() {
		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics', __( 'Analytics', 'woo-dash' ) ],
						__( 'Report Title', 'woo-dash' ),
					] }
				/>
				<div>Report: { this.props.params.report }</div>
			</Fragment>
		);
	}
}
