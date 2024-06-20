/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

type ExpressionTextAreaProps = {
	expression?: string;
	readOnly?: boolean;
	onChange?: ( expression: string ) => void;
};

export function ExpressionTextArea( {
	expression,
	readOnly = false,
	onChange,
}: ExpressionTextAreaProps ) {
	const textAreaRef = useRef< HTMLTextAreaElement >( null );

	return (
		<textarea
			ref={ textAreaRef }
			className="woocommerce-product-editor-dev-tools-expression-field__expression"
			readOnly={ readOnly }
			value={ expression }
			placeholder={ __( 'Enter an expression', 'woocommerce' ) }
			onChange={ ( event ) => onChange?.( event.target.value ) }
		/>
	);
}
