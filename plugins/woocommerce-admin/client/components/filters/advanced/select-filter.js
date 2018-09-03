/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { partial } from 'lodash';
import PropTypes from 'prop-types';

const SelectFilter = ( { filter, config, onFilterChange } ) => {
	const { key, rule, value } = filter;
	return (
		<Fragment>
			<div className="woocommerce-filters-advanced__fieldset-legend">{ config.label }</div>
			{ rule && (
				<SelectControl
					className="woocommerce-filters-advanced__list-specifier"
					options={ config.rules }
					value={ rule }
					onChange={ partial( onFilterChange, key, 'rule' ) }
					aria-label={ sprintf( __( 'Select a %s filter match', 'wc-admin' ), config.addLabel ) }
				/>
			) }
			<div className="woocommerce-filters-advanced__list-selector">
				<SelectControl
					className="woocommerce-filters-advanced__list-select"
					options={ config.input.options }
					value={ value }
					onChange={ partial( onFilterChange, filter.key, 'value' ) }
					aria-label={ sprintf( __( 'Select %s', 'wc-admin' ), config.label ) }
				/>
			</div>
		</Fragment>
	);
};

SelectFilter.propTypes = {
	/**
	 * The configuration object for the single filter to be rendered.
	 */
	config: PropTypes.shape( {
		label: PropTypes.string,
		addLabel: PropTypes.string,
		rules: PropTypes.arrayOf( PropTypes.object ),
		input: PropTypes.object,
	} ).isRequired,
	/**
	 * The activeFilter handed down by AdvancedFilters.
	 */
	filter: PropTypes.shape( {
		key: PropTypes.string,
		rule: PropTypes.string,
		value: PropTypes.string,
	} ).isRequired,
	/**
	 * Function to be called on update.
	 */
	onFilterChange: PropTypes.func.isRequired,
};

export default SelectFilter;
