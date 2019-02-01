/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import { uniqueId } from 'lodash';

/**
 * Internal dependencies
 */
import './setting.scss';

class Setting extends Component {
	renderInput = () => {
		const { handleChange, name, inputType, options, value } = this.props;
		const id = uniqueId( name );

		switch ( inputType ) {
			case 'checkboxGroup':
				return options.map(
					optionGroup =>
						optionGroup.options.length > 0 && (
							<div
								className="woocommerce-setting__options-group"
								key={ optionGroup.key }
								aria-labelledby={ name + '-label' }
							>
								{ optionGroup.label && (
									<span className="woocommerce-setting__options-group-label">
										{ optionGroup.label }
									</span>
								) }
								{ this.renderCheckboxOptions( optionGroup.options ) }
							</div>
						)
				);
			case 'checkbox':
				return this.renderCheckboxOptions( options );
			case 'text':
			default:
				return (
					<input id={ id } type="text" name={ name } onChange={ handleChange } value={ value } />
				);
		}
	};

	renderCheckboxOptions( options ) {
		const { handleChange, name, value } = this.props;

		return options.map( option => {
			const id = uniqueId( name + '-' + option.value );
			return (
				<label htmlFor={ id } key={ option.value }>
					<input
						id={ id }
						type="checkbox"
						name={ name }
						onChange={ handleChange }
						aria-label={ option.description }
						checked={ value && value.includes( option.value ) }
						value={ option.value }
					/>
					{ option.label }
				</label>
			);
		} );
	}

	render() {
		const { helpText, label, name } = this.props;

		return (
			<div className="woocommerce-setting">
				<div className="woocommerce-setting__label" id={ name + '-label' }>
					{ label }
				</div>
				<div className="woocommerce-setting__options">
					{ this.renderInput() }
					{ helpText && <span className="woocommerce-setting__help">{ helpText }</span> }
				</div>
			</div>
		);
	}
}

Setting.propTypes = {
	/**
	 * Function assigned to the onChange of all inputs.
	 */
	handleChange: PropTypes.func.isRequired,
	/**
	 * Optional help text displayed underneath the setting.
	 */
	helpText: PropTypes.oneOfType( [ PropTypes.string, PropTypes.array ] ),
	/**
	 * Type of input to use; defaults to a text input.
	 */
	inputType: PropTypes.oneOf( [ 'checkbox', 'checkboxGroup', 'text' ] ),
	/**
	 * Label used for describing the setting.
	 */
	label: PropTypes.string.isRequired,
	/**
	 * Setting slug applied to input names.
	 */
	name: PropTypes.string.isRequired,
	/**
	 * Array of options used for when the `inputType` allows multiple selections.
	 */
	options: PropTypes.arrayOf(
		PropTypes.shape( {
			/**
			 * Input value for this option.
			 */
			value: PropTypes.string,
			/**
			 * Label for this option or above a group for a group `inputType`.
			 */
			label: PropTypes.string,
			/**
			 * Description used for screen readers.
			 */
			description: PropTypes.string,
			/**
			 * Key used for a group `inputType`.
			 */
			key: PropTypes.string,
			/**
			 * Nested options for a group `inputType`.
			 */
			options: PropTypes.array,
		} )
	),
	/**
	 * The string value used for the input or array of items if the input allows multiselection.
	 */
	value: PropTypes.oneOfType( [ PropTypes.string, PropTypes.array ] ),
};

export default Setting;
