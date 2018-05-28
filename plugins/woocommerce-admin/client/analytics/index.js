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
import DatePicker from 'components/date-picker';

export default class extends Component {
	render() {
		const { query, path } = this.props;
		return (
			<Fragment>
				<Header sections={ [ __( 'Analytics', 'woo-dash' ) ] } />
				<DatePicker query={ query } path={ path } />
			</Fragment>
		);
	}
}
