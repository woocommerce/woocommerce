/**
 * External dependencies
 */
import { Button, CheckboxControl, SelectControl } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { uniqueId } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './setting.scss';

class Setting extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			disabled: false,
		};
	}

	renderInput = () => {
		const {
			handleChange,
			name,
			inputText,
			inputType,
			options,
			value,
			component,
		} = this.props;
		const { disabled } = this.state;

		switch ( inputType ) {
			case 'checkboxGroup':
				return options.map(
					( optionGroup ) =>
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
								{ this.renderCheckboxOptions(
									optionGroup.options
								) }
							</div>
						)
				);
			case 'checkbox':
				return this.renderCheckboxOptions( options );
			case 'button':
				return (
					<Button
						isSecondary
						onClick={ this.handleInputCallback }
						disabled={ disabled }
					>
						{ inputText }
					</Button>
				);
			case 'component':
				const SettingComponent = component;
				return (
					<SettingComponent
						value={ value }
						onChange={ handleChange }
						{ ...this.props }
					/>
				);
			case 'select':
				return (
					<SelectControl
						options={ options }
						value={ value }
						onChange={ ( newValue ) =>
							handleChange( {
								target: {
									name,
									type: 'select',
									value: newValue,
								},
							} )
						}
					/>
				);
			case 'text':
			default:
				const id = uniqueId( name );
				return (
					<input
						id={ id }
						type="text"
						name={ name }
						onChange={ handleChange }
						value={ value }
						placeholder={ inputText }
						disabled={ disabled }
					/>
				);
		}
	};

	handleInputCallback = () => {
		const { createNotice, callback } = this.props;

		if ( typeof callback !== 'function' ) {
			return;
		}

		return new Promise( ( resolve, reject ) => {
			this.setState( { disabled: true } );
			callback( resolve, reject, createNotice );
		} )
			.then( () => {
				this.setState( { disabled: false } );
			} )
			.catch( () => {
				this.setState( { disabled: false } );
			} );
	};

	renderCheckboxOptions( options ) {
		const { handleChange, name, value } = this.props;
		const { disabled } = this.state;

		return options.map( ( option ) => {
			return (
				<CheckboxControl
					key={ name + '-' + option.value }
					label={ option.label }
					name={ name }
					checked={ value && value.includes( option.value ) }
					onChange={ ( checked ) =>
						handleChange( {
							target: {
								checked,
								name,
								type: 'checkbox',
								value: option.value,
							},
						} )
					}
					disabled={ disabled }
				/>
			);
		} );
	}

	render() {
		const { helpText, label, name } = this.props;

		return (
			<div className="woocommerce-setting">
				<div
					className="woocommerce-setting__label"
					id={ name + '-label' }
				>
					{ label }
				</div>
				<div className="woocommerce-setting__input">
					{ this.renderInput() }
					{ helpText && (
						<span className="woocommerce-setting__help">
							{ helpText }
						</span>
					) }
				</div>
			</div>
		);
	}
}

Setting.propTypes = {
	/**
	 * A callback that is fired after actionable items, such as buttons.
	 */
	callback: PropTypes.func,
	/**
	 * Function assigned to the onChange of all inputs.
	 */
	handleChange: PropTypes.func.isRequired,
	/**
	 * Optional help text displayed underneath the setting.
	 */
	helpText: PropTypes.oneOfType( [ PropTypes.string, PropTypes.array ] ),
	/**
	 * Text used as placeholder or button text in the input area.
	 */
	inputText: PropTypes.string,
	/**
	 * Type of input to use; defaults to a text input.
	 */
	inputType: PropTypes.oneOf( [
		'button',
		'checkbox',
		'checkboxGroup',
		'text',
		'component',
		'select',
	] ),
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

export default compose(
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		return { createNotice };
	} )
)( Setting );
