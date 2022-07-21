/**
 * External dependencies
 */
import { Icon, check } from '@wordpress/icons';
import { createElement } from '@wordpress/element';
/**
 * @typedef {import('./index').Option} Option
 */

/**
 * Renders a custom Checkbox
 *
 * @param {Object}  props           Component properties
 * @param {Option}  props.option    Option for the checkbox
 * @param {string}  props.className The className for the component
 * @param {boolean} props.checked   Defines if the checkbox is checked
 * @return {JSX.Element|null} The Checkbox component
 */
const Checkbox = ( { option, checked, className, ...props } ) => {
	return (
		<div className={ className }>
			<div className="components-base-control__field">
				<span className="components-checkbox-control__input-container">
					<input
						id={ `inspector-checkbox-control-${
							option.key ?? option.value
						}` }
						className="components-checkbox-control__input"
						type="checkbox"
						tabIndex="-1"
						value={ option.value }
						checked={ checked }
						{ ...props }
					/>
					{ checked && (
						<Icon
							icon={ check }
							role="presentation"
							className="components-checkbox-control__checked"
						/>
					) }
				</span>
				<label
					className="components-checkbox-control__label"
					htmlFor={ `inspector-checkbox-control-${
						option.key ?? option.value
					}` }
				>
					{ option.label }
				</label>
			</div>
		</div>
	);
};

export default Checkbox;
