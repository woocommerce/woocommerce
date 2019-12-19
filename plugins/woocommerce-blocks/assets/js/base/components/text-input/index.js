/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Label from '../label';
import './style.scss';

const TextInput = ( {
	className,
	id,
	type = 'text',
	ariaLabel,
	label,
	screenReaderLabel,
	disabled,
	help,
	value,
	onChange,
} ) => {
	const [ isActive, setIsActive ] = useState( false );
	const onChangeValue = ( event ) => onChange( event.target.value );
	return (
		<div
			className={ classnames( 'wc-blocks-text-input', className, {
				'is-active': isActive || value,
			} ) }
		>
			<Label
				label={ label }
				screenReaderLabel={ screenReaderLabel || label }
				wrapperElement="label"
				wrapperProps={ {
					htmlFor: id,
				} }
				htmlFor={ id }
			/>
			<input
				type={ type }
				id={ id }
				value={ value }
				onChange={ onChangeValue }
				onFocus={ () => setIsActive( true ) }
				onBlur={ () => setIsActive( false ) }
				aria-label={ ariaLabel || label }
				disabled={ disabled }
				aria-describedby={ !! help ? id + '__help' : undefined }
			/>
			{ !! help && (
				<p id={ id + '__help' } className="wc-blocks-text-input__help">
					{ help }
				</p>
			) }
		</div>
	);
};

TextInput.propTypes = {
	id: PropTypes.string,
	value: PropTypes.string,
	onChangeValue: PropTypes.func,
	ariaLabel: PropTypes.string,
	label: PropTypes.string,
	screenReaderLabel: PropTypes.string,
	disabled: PropTypes.bool,
	help: PropTypes.string,
};

export default TextInput;
