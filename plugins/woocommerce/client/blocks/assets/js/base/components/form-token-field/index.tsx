/**
 * External dependencies
 */
import { FormTokenField as WPFormTokenField } from 'wc-bundled-wordpress-components';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './style.scss';

export interface Props {
	className?: string;
	disabled?: boolean;
	displayTransform?: ( value: string ) => string;
	label?: string;
	messages?: NonNullable<
		React.ComponentProps< typeof WPFormTokenField >[ 'messages' ]
	>;
	multiple?: boolean;
	onChange: ( value: string[] ) => void;
	placeholder?: string;
	saveTransform?: ( value: string ) => string;
	style?: React.CSSProperties;
	suggestions: string[];
	validateInput?: ( token: string ) => boolean;
	value: string[];
}

const FormTokenField = ( {
	className,
	style,
	suggestions,
	multiple = true,
	saveTransform = ( incompleteToken ) =>
		incompleteToken.trim().replace( /\s/g, '-' ),
	validateInput = ( token: string ) => suggestions.includes( token ),
	label = '',
	onChange,
	...props
}: Props ) => {
	return (
		<div
			className={ clsx(
				'wc-blocks-components-form-token-field-wrapper',
				className,
				{
					'single-selection': ! multiple,
				}
			) }
			style={ style }
		>
			<WPFormTokenField
				label={ label }
				__experimentalExpandOnFocus={ true }
				__experimentalShowHowTo={ false }
				__experimentalValidateInput={ validateInput }
				saveTransform={ saveTransform }
				{ ...( multiple ? {} : { maxLength: 1 } ) }
				suggestions={ suggestions }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				onChange={ ( tokens ) =>
					onChange(
						tokens.map( ( token ) =>
							typeof token === 'string' ? token : token.value
						)
					)
				}
				{ ...props }
			/>
		</div>
	);
};

export default FormTokenField;
