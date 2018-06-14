/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ExampleReport from './example';
import RevenueReport from './revenue';

class Report extends Component {
	render() {
		const { params } = this.props;
		switch ( params.report ) {
			case 'revenue':
				return <RevenueReport { ...this.props } />;
			default:
				return <ExampleReport />;
		}
	}
}

Report.propTypes = {
	params: PropTypes.object.isRequired,
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default Report;
