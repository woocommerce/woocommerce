/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useState } from '@wordpress/element';
import { withInstanceId } from 'wordpress-compose';

/**
 * Internal dependencies
 */
import Label from '../label';
import './style.scss';

const TextInput = ( {
	className,
	instanceId,
	id,
	type = 'text',
	ariaLabel,
	ariaDescribedBy,
	label,
	screenReaderLabel,
	disabled,
	help,
	autoComplete = 'off',
	value = '',
	onChange,
	required = false,
} ) => {
	const [ isActive, setIsActive ] = useState( false );
	const onChangeValue = ( event ) => onChange( event.target.value );
	const textInputId = id || 'textinput-' + instanceId;

	return (
		<div
			className={ classnames( 'wc-block-text-input', className, {
				'is-active': isActive || value,
			} ) }
		>
			<input
				type={ type }
				id={ textInputId }
				value={ value }
				autoComplete={ autoComplete }
				onChange={ onChangeValue }
				onFocus={ () => setIsActive( true ) }
				onBlur={ () => setIsActive( false ) }
				aria-label={ ariaLabel || label }
				disabled={ disabled }
				aria-describedby={
					!! help && ! ariaDescribedBy
						? textInputId + '__help'
						: ariaDescribedBy
				}
				required={ required }
			/>
			<Label
				label={ label }
				screenReaderLabel={ screenReaderLabel || label }
				wrapperElement="label"
				wrapperProps={ {
					htmlFor: textInputId,
				} }
				htmlFor={ textInputId }
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
	ariaDescribedBy: PropTypes.string,
	label: PropTypes.string,
	screenReaderLabel: PropTypes.string,
	disabled: PropTypes.bool,
	help: PropTypes.string,
	autoComplete: PropTypes.string,
	required: PropTypes.bool,
};

export default withInstanceId( TextInput );
