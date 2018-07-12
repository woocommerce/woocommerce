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
import ProductsReport from './products';

class Report extends Component {
	render() {
		const { params } = this.props;
		switch ( params.report ) {
			case 'revenue':
				return <RevenueReport { ...this.props } />;
			case 'products':
				return <ProductsReport { ...this.props } />;
			default:
				return <ExampleReport />;
		}
	}
}

Report.propTypes = {
	params: PropTypes.object.isRequired,
};

export default Report;
