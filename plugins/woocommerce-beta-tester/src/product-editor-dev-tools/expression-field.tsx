/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { check, close, edit } from '@wordpress/icons';
import { evaluate } from '@woocommerce/expression-evaluation';

/**
 * Internal dependencies
 */
import { ExpressionTextArea } from './expression-text-area';

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
	const isDirty = editedExpression !== expression;

	useEffect( () => setEditedExpression( expression ), [ expression ] );

	const { result, error } = evaluateExpression(
		editedExpression,
		evaluationContext
	);

	const errorString = error && isDirty ? String( error ) : '';

	const resultString = error
		? errorString
		: JSON.stringify( result, null, 4 );

	function handleOnClickCancel() {
		setEditedExpression( expression );
		onCancel?.();
	}

	return (
		<div
			className="woocommerce-product-editor-dev-tools-expression-field"
			data-mode={ mode }
		>
			<ExpressionTextArea
				readOnly={ mode === 'view' }
				expression={ editedExpression }
				onChange={ setEditedExpression }
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
								onClick={ handleOnClickCancel }
							/>
						) }
					</>
				) }
			</div>
		</div>
	);
}
