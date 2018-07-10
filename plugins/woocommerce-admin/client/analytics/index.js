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
import DatePicker from 'components/date-picker';
import DropdownButton from 'components/dropdown-button';

export default class extends Component {
	render() {
		const { query, path } = this.props;
		return (
			<Fragment>
				<Header sections={ [ __( 'Analytics', 'wc-admin' ) ] } />
				<DatePicker query={ query } path={ path } />
				<p>Example single line button - default width 100% of container</p>
				<DropdownButton labels={ [ 'All Products Sold' ] } />
			</Fragment>
		);
	}
}
