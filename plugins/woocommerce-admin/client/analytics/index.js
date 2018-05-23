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
		return (
			<Fragment>
				<Header sections={ [ __( 'Analytics', 'woo-dash' ) ] } />
				<DatePicker period="last_week" compare="previous_year" />
			</Fragment>
		);
	}
}
