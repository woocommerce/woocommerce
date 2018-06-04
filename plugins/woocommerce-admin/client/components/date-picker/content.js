/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { TabPanel } from '@wordpress/components';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';

/**
 * Internal dependencies
 */
import PresetPeriods from './preset-periods';
import ComparePeriods from './compare-periods';

class DatePickerContent extends Component {
	render() {
		const { period, compare, onSelect, onClose, getUpdatePath } = this.props;
		return (
			<Fragment>
				<h3 className="woocommerce-date-picker__text">{ __( 'select a date range', 'woo-dash' ) }</h3>
				<TabPanel
					tabs={ [
						{
							name: 'period',
							title: __( 'Presets', 'woo-dash' ),
							className: 'woocommerce-date-picker__tab',
						},
						{
							name: 'custom',
							title: __( 'Custom', 'woo-dash' ),
							className: 'woocommerce-date-picker__tab',
						},
					] }
					className="woocommerce-date-picker__tabs"
					activeClass="is-active"
				>
					{ selectedTab => (
						<Fragment>
							{ selectedTab === 'period' && (
								<PresetPeriods onSelect={ onSelect } period={ period } />
							) }
							{ selectedTab === 'custom' && <div>Custom Date Picker Goes here</div> }
							<h3 className="woocommerce-date-picker__text">{ __( 'compare to', 'woo-dash' ) }</h3>
							<ComparePeriods onSelect={ onSelect } compare={ compare } />
							<Link
								className="woocommerce-date-picker__update-btn"
								to={ getUpdatePath( selectedTab ) }
								onClick={ onClose }
							>
								{ __( 'Update', 'woo-dash' ) }
							</Link>
						</Fragment>
					) }
				</TabPanel>
			</Fragment>
		);
	}
}

DatePickerContent.propTypes = {
	period: PropTypes.string.isRequired,
	compare: PropTypes.string.isRequired,
	onSelect: PropTypes.func.isRequired,
	onClose: PropTypes.func.isRequired,
	getUpdatePath: PropTypes.func.isRequired,
};

export default DatePickerContent;
