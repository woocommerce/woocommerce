/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Header from 'layout/header';

export default class extends Component {
	render() {
		return (
			<Fragment>
				<Header sections={ [ __( 'Analytics', 'wc-admin' ) ] } />
				<p>Overview Section</p>
			</Fragment>
		);
	}
}
