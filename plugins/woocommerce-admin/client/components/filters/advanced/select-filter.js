/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { SelectControl, Spinner } from '@wordpress/components';
import { partial } from 'lodash';
import PropTypes from 'prop-types';
import { withInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { getDefaultOptionValue } from './utils';

class SelectFilter extends Component {
	constructor( { filter, config, onFilterChange } ) {
		super( ...arguments );

		const options = config.input.options;
		this.state = { options };

		this.updateOptions = this.updateOptions.bind( this );

		if ( ! options && config.input.getOptions ) {
			config.input
				.getOptions()
				.then( this.updateOptions )
				.then( returnedOptions => {
					if ( ! filter.value ) {
						const value = getDefaultOptionValue( config, returnedOptions );
						onFilterChange( filter.key, 'value', value );
					}
				} );
		}
	}

	updateOptions( options ) {
		this.setState( { options } );
		return options;
	}

	render() {
		const { config, filter, instanceId, onFilterChange } = this.props;
		const { options } = this.state;
		const { key, rule, value } = filter;
		const { labels, rules } = config;
		return (
			<Fragment>
				<div
					id={ `${ key }-${ instanceId }` }
					className="woocommerce-filters-advanced__fieldset-legend"
				>
					{ labels.title }
				</div>
				{ rule && (
					<SelectControl
						className="woocommerce-filters-advanced__list-specifier"
						options={ rules }
						value={ rule }
						onChange={ partial( onFilterChange, key, 'rule' ) }
						aria-label={ labels.rule }
					/>
				) }
				<div className="woocommerce-filters-advanced__list-selector">
					{ ! options && <Spinner /> }
					{ options && (
						<SelectControl
							className="woocommerce-filters-advanced__list-select"
							options={ options }
							value={ value }
							onChange={ partial( onFilterChange, filter.key, 'value' ) }
							aria-labelledby={ `${ key }-${ instanceId }` }
						/>
					) }
				</div>
			</Fragment>
		);
	}
}

SelectFilter.propTypes = {
	/**
	 * The configuration object for the single filter to be rendered.
	 */
	config: PropTypes.shape( {
		labels: PropTypes.shape( {
			rule: PropTypes.string,
			title: PropTypes.string,
		} ),
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

export default withInstanceId( SelectFilter );
