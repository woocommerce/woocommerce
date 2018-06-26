/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { TabPanel } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ComparePeriods from './compare-periods';
import { H, Section } from 'layout/section';
import PresetPeriods from './preset-periods';
import Link from 'components/link';

class DatePickerContent extends Component {
	render() {
		const { period, compare, onSelect, onClose, getUpdatePath } = this.props;
		return (
			<Fragment>
				<H className="screen-reader-text" tabIndex="0">
					{ __( 'Select date range and comparison', 'woo-dash' ) }
				</H>
				<Section component={ false }>
					<H className="woocommerce-date-picker__text">
						{ __( 'select a date range', 'woo-dash' ) }
					</H>
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
								<H className="woocommerce-date-picker__text">{ __( 'compare to', 'woo-dash' ) }</H>
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
				</Section>
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
