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
