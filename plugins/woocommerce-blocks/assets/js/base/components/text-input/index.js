/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useState } from '@wordpress/element';
import withComponentId from '@woocommerce/base-hocs/with-component-id';

/**
 * Internal dependencies
 */
import Label from '../label';
import './style.scss';

const TextInput = ( {
	className,
	componentId,
	id,
	type = 'text',
	ariaLabel,
	label,
	screenReaderLabel,
	disabled,
	help,
	value = '',
	onChange,
} ) => {
	const [ isActive, setIsActive ] = useState( false );
	const onChangeValue = ( event ) => onChange( event.target.value );
	const textInputId = id || componentId;

	return (
		<div
			className={ classnames( 'wc-block-text-input', className, {
				'is-active': isActive || value,
			} ) }
		>
			<Label
				label={ label }
				screenReaderLabel={ screenReaderLabel || label }
				wrapperElement="label"
				wrapperProps={ {
					htmlFor: textInputId,
				} }
				htmlFor={ textInputId }
			/>
			<input
				type={ type }
				id={ textInputId }
				value={ value }
				onChange={ onChangeValue }
				onFocus={ () => setIsActive( true ) }
				onBlur={ () => setIsActive( false ) }
				aria-label={ ariaLabel || label }
				disabled={ disabled }
				aria-describedby={
					!! help ? textInputId + '__help' : undefined
				}
			/>
			{ !! help && (
				<p
					id={ textInputId + '__help' }
					className="wc-block-text-input__help"
				>
					{ help }
				</p>
			) }
		</div>
	);
};

TextInput.propTypes = {
	onChange: PropTypes.func.isRequired,
	id: PropTypes.string,
	value: PropTypes.string,
	ariaLabel: PropTypes.string,
	label: PropTypes.string,
	screenReaderLabel: PropTypes.string,
	disabled: PropTypes.bool,
	help: PropTypes.string,
};

export default withComponentId( TextInput );
