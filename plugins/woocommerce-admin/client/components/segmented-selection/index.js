/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { partial, uniqueId } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';

class SegmentedSelection extends Component {
	render() {
		const { className, options, selected, onSelect, name, legend } = this.props;
		return (
			<fieldset>
				<legend className="screen-reader-text">{ legend }</legend>
				<div className={ classnames( className, 'woo-dash__segmented-selection' ) }>
					{ options.map( ( { value, label } ) => {
						if ( ! value || ! label ) {
							return null;
						}
						const id = uniqueId( `${ value }_` );
						return (
							<Fragment key={ value }>
								<input
									className="woo-dash__segmented-selection-input"
									type="radio"
									name={ name }
									id={ id }
									checked={ selected === value }
									onChange={ partial( onSelect, name, value ) }
								/>
								<label htmlFor={ id }>
									<span className="woo-dash__segmented-selection-label">{ label }</span>
								</label>
							</Fragment>
						);
					} ) }
				</div>
			</fieldset>
		);
	}
}

SegmentedSelection.propTypes = {
	className: PropTypes.string,
	options: PropTypes.arrayOf(
		PropTypes.shape( {
			value: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
		} )
	).isRequired,
	selected: PropTypes.string,
	onSelect: PropTypes.func.isRequired,
	name: PropTypes.string.isRequired,
	legend: PropTypes.string.isRequired,
};

export default SegmentedSelection;
