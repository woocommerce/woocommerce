/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { check, close, edit } from '@wordpress/icons';
import { evaluate } from '@woocommerce/expression-evaluation';

function evaluateExpression(
	expression: string,
	evaluationContext: object | undefined
) {
	let result;
	let error;

	try {
		result = evaluate( expression, evaluationContext );
	} catch ( e ) {
		error = e;
	}

	return {
		result,
		error,
	};
}

type ExpressionFieldProps = {
	expression?: string;
	evaluationContext?: object;
	mode?: 'view' | 'edit';
	onEnterEdit?: () => void;
	onUpdate?: ( expression: string ) => void;
	onCancel?: () => void;
	updateLabel?: string;
};

export function ExpressionField( {
	expression = '',
	evaluationContext,
	mode = 'view',
	onEnterEdit,
	onUpdate,
	onCancel,
	updateLabel = __( 'Update', 'woocommerce' ),
}: ExpressionFieldProps ) {
	const [ editedExpression, setEditedExpression ] = useState( expression );

	useEffect( () => setEditedExpression( expression ), [ expression ] );

	const { result, error } = evaluateExpression(
		editedExpression,
		evaluationContext
	);

	const resultString = error ? String( error ) : String( result );

	const expressionTextAreaRef = useRef< HTMLTextAreaElement >( null );

	function handleOnChange( event: React.ChangeEvent< HTMLTextAreaElement > ) {
		setEditedExpression( event.target.value );
	}

	return (
		<div className="woocommerce-product-editor-dev-tools-expression-field">
			<textarea
				ref={ expressionTextAreaRef }
				className="woocommerce-product-editor-dev-tools-expression-field__expression"
				readOnly={ mode === 'view' }
				value={ editedExpression }
				onChange={ handleOnChange }
			/>
			<div className="woocommerce-product-editor-dev-tools-expression-field__result">
				{ resultString }
			</div>
			<div className="woocommerce-product-editor-dev-tools-expression-field__actions">
				{ mode === 'view' ? (
					<Button
						icon={ edit }
						label={ __( 'Edit', 'woocommerce' ) }
						onClick={ () => onEnterEdit?.() }
					/>
				) : (
					<>
						<Button
							icon={ check }
							label={ updateLabel }
							onClick={ () => onUpdate?.( editedExpression ) }
						/>
						{ onCancel && (
							<Button
								icon={ close }
								label={ __( 'Cancel', 'woocommerce' ) }
								onClick={ () => onCancel?.() }
							/>
						) }
					</>
				) }
			</div>
		</div>
	);
}
