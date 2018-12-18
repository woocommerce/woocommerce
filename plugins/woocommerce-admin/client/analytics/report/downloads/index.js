/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { ReportFilters } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { filters, advancedFilters } from './config';

export default class DownloadsReport extends Component {
	render() {
		const { query, path } = this.props;

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					showDatePicker={ false }
					advancedFilters={ advancedFilters }
				/>
			</Fragment>
		);
	}
}

DownloadsReport.propTypes = {
	query: PropTypes.object.isRequired,
};
