/**
 * External dependencies
 */
import { createElement, Component } from '@wordpress/element';
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { partial, uniqueId } from 'lodash';

/**
 * Create a panel of styled selectable options rendering stylized checkboxes and labels
 */
class SegmentedSelection extends Component {
	render() {
		const { className, options, selected, onSelect, name, legend } =
			this.props;

		return (
			<fieldset className="woocommerce-segmented-selection">
				<legend className="screen-reader-text">{ legend }</legend>
				<div
					className={ classnames(
						className,
						'woocommerce-segmented-selection__container'
					) }
				>
					{ options.map( ( { value, label } ) => {
						if ( ! value || ! label ) {
							return null;
						}
						const id = uniqueId( `${ value }_` );
						return (
							<div
								className="woocommerce-segmented-selection__item"
								key={ value }
							>
								{ /* eslint-disable jsx-a11y/label-has-for */ }
								<input
									className="woocommerce-segmented-selection__input"
									type="radio"
									name={ name }
									id={ id }
									checked={ selected === value }
									onChange={ partial( onSelect, {
										[ name ]: value,
									} ) }
								/>
								<label htmlFor={ id }>
									<span className="woocommerce-segmented-selection__label">
										{ label }
									</span>
								</label>
								{ /* eslint-enable jsx-a11y/label-has-for */ }
							</div>
						);
					} ) }
				</div>
			</fieldset>
		);
	}
}

SegmentedSelection.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * An Array of options to render. The array needs to be composed of objects with properties `label` and `value`.
	 */
	options: PropTypes.arrayOf(
		PropTypes.shape( {
			value: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
		} )
	).isRequired,
	/**
	 * Value of selected item.
	 */
	selected: PropTypes.string,
	/**
	 * Callback to be executed after selection
	 */
	onSelect: PropTypes.func.isRequired,
	/**
	 * This will be the key in the key and value arguments supplied to `onSelect`.
	 */
	name: PropTypes.string.isRequired,
	/**
	 * Create a legend visible to screen readers.
	 */
	legend: PropTypes.string.isRequired,
};

export default SegmentedSelection;
