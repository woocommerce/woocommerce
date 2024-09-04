/**
 * External dependencies
 */
import {
	forwardRef,
	useImperativeHandle,
	useLayoutEffect,
	useRef,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

type ExpressionTextAreaProps = {
	expression?: string;
	readOnly?: boolean;
	onChange?: ( expression: string ) => void;
	onClick?: () => void;
};

export const ExpressionTextArea = forwardRef<
	HTMLTextAreaElement,
	ExpressionTextAreaProps
>( ( { expression, readOnly = false, onChange, onClick }, outerRef ) => {
	const textAreaRef = useRef< HTMLTextAreaElement >( null );

	// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
	useImperativeHandle( outerRef, () => textAreaRef.current!, [] );

	useLayoutEffect( () => {
		const textArea = textAreaRef.current;

		if ( ! textArea || textArea.scrollHeight < 1 ) return;

		// Have to first set the height to auto and then set it to the scrollHeight
		// to allow the textarea to shrink when lines are removed
		textArea.style.height = 'auto';
		textArea.style.height = `${ textArea.scrollHeight }px`;
	}, [ expression ] );

	return (
		<textarea
			ref={ textAreaRef }
			className="woocommerce-product-editor-dev-tools-expression-field__expression"
			rows={ 1 }
			readOnly={ readOnly }
			value={ expression }
			placeholder={ __( 'Enter an expression', 'woocommerce' ) }
			onChange={ ( event ) => onChange?.( event.target.value ) }
			onClick={ () => onClick?.() }
		/>
	);
} );
