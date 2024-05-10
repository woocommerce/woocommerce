/**
 * External dependencies
 */
import Button, { ButtonProps } from '@woocommerce/base-components/button';
import { RichText } from '@wordpress/block-editor';

export interface EditableButtonProps
	extends Omit< ButtonProps, 'onChange' | 'placeholder' | 'value' > {
	/**
	 * On change callback.
	 */
	onChange: ( value: string ) => void;
	/**
	 * The placeholder of the editable button.
	 */
	placeholder?: string;
	/**
	 * The current value of the editable button.
	 */
	value: string;
}

const EditableButton = ( {
	onChange,
	placeholder,
	value,
	...props
}: EditableButtonProps ) => {
	/**
	 * If the value contains a placeholder, e.g. "Place Order · <price/>", we need to change it,
	 * e.g. to "Place Order · &lt;price/>", to ensure it is displayed correctly. This reflects the
	 * default behaviour of the `RichText` component if we would type "<price/>" directly into it.
	 */
	value = value.replace( /</g, '&lt;' );

	return (
		<Button { ...props }>
			<RichText
				multiline={ false }
				allowedFormats={ [] }
				value={ value }
				placeholder={ placeholder }
				onChange={ onChange }
			/>
		</Button>
	);
};

export default EditableButton;
