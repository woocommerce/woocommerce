/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { check, closeSmall, edit, trash } from '@wordpress/icons';
import { evaluate } from '@woocommerce/expression-evaluation';

/**
 * Internal dependencies
 */
import { ExpressionResult } from './expression-result';
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
	onRemove?: () => void;
	updateLabel?: string;
};

export function ExpressionField( {
	expression = '',
	evaluationContext,
	mode = 'view',
	onEnterEdit,
	onUpdate,
	onCancel,
	onRemove,
	updateLabel = __( 'Update', 'woocommerce' ),
}: ExpressionFieldProps ) {
	const textAreaRef = useRef< HTMLTextAreaElement >( null );

	const [ editedExpression, setEditedExpression ] = useState( expression );

	useEffect( () => setEditedExpression( expression ), [ expression ] );

	const { result, error } = evaluateExpression(
		editedExpression,
		evaluationContext
	);

	function handleOnClickEdit() {
		const textArea = textAreaRef.current;

		if ( textArea ) {
			textArea.focus();
			textArea.setSelectionRange(
				textArea.value.length,
				textArea.value.length
			);
		}

		onEnterEdit?.();
	}

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
				ref={ textAreaRef }
				readOnly={ mode === 'view' }
				expression={ editedExpression }
				onChange={ setEditedExpression }
				onClick={ onEnterEdit }
			/>
			<ExpressionResult
				result={ result }
				error={ error }
				showIfError={ editedExpression.length > 0 }
			/>
			<div className="woocommerce-product-editor-dev-tools-expression-field__actions">
				{ mode === 'view' ? (
					<>
						<Button
							icon={ edit }
							label={ __( 'Edit', 'woocommerce' ) }
							onClick={ handleOnClickEdit }
						/>
						<Button
							icon={ trash }
							label={ __( 'Remove', 'woocommerce' ) }
							onClick={ () => onRemove?.() }
						/>
					</>
				) : (
					<>
						<Button
							icon={ check }
							label={ updateLabel }
							disabled={ !! error }
							onClick={ () => onUpdate?.( editedExpression ) }
						/>
						{ onCancel && (
							<Button
								icon={ closeSmall }
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
