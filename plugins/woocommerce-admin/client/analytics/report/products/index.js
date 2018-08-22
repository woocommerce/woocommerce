/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { filters } from './constants';
import { ReportFilters } from '@woocommerce/components';
import './style.scss';

export default class extends Component {
	render() {
		const { query, path } = this.props;

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } filters={ filters } />
			</Fragment>
		);
	}
}
