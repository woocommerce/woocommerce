/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import classnames from 'classnames';
import { Label } from '@woocommerce/blocks-components';
import IntlTelInput from 'intl-tel-input/react/build/IntlTelInput.esm';
import utils from 'iti/utils';

/**
 * Internal dependencies
 */
import './style.scss';
import type { PhoneInputProps } from './props';

export const PhoneInput = ( {
	country,
	className,
	id,
	ariaLabel,
	label,
	screenReaderLabel,
	disabled,
	autoComplete = 'off',
	value = '',
	onChange,
	required = false,
	onBlur = () => {
		/* Do nothing */
	},
	feedback,
	...rest
}: PhoneInputProps ): JSX.Element => {
	const [ isActive, setIsActive ] = useState( false );

	return (
		<div
			className={ classnames(
				'wc-block-components-phone-input',
				className,
				{
					'is-active': isActive || value,
				}
			) }
		>
			<div className="field-wrapper">
				<IntlTelInput
					initialValue={ value }
					onChangeNumber={ ( newValue: string ) =>
						onChange( newValue )
					}
					initOptions={ {
						initialCountry: country,
						showSelectedDialCode: true,
						utilsScript: utils,
					} }
					onFocus={ () => setIsActive( true ) }
					onBlur={ () => {
						onBlur( value );
						setIsActive( false );
					} }
				/>
			</div>
			<Label
				label={ label }
				screenReaderLabel={ screenReaderLabel || label }
				wrapperElement="label"
				wrapperProps={ {
					htmlFor: id,
					className: 'field-label',
				} }
			/>
			{ feedback }
		</div>
	);
};

export default PhoneInput;
