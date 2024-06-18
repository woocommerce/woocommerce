/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
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
	expression: string;
	evaluationContext?: object;
	mode?: 'view' | 'edit';
	onEnterEdit?: () => void;
	onUpdate?: ( expression: string ) => void;
	onCancel?: () => void;
};

export function ExpressionField( {
	expression,
	evaluationContext,
	mode = 'view',
	onEnterEdit,
	onUpdate,
	onCancel,
}: ExpressionFieldProps ) {
	const { result, error } = evaluateExpression(
		expression,
		evaluationContext
	);

	const resultString = error ? String( error ) : String( result );

	return (
		<div className="woocommerce-product-editor-dev-tools-expression-field">
			<div className="woocommerce-product-editor-dev-tools-expression-field__expression">
				{ expression }
			</div>
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
							label={ __( 'Update', 'woocommerce' ) }
							onClick={ () => onUpdate?.( expression ) }
						/>
						<Button
							icon={ close }
							label={ __( 'Cancel', 'woocommerce' ) }
							onClick={ () => onCancel?.() }
						/>
					</>
				) }
			</div>
		</div>
	);
}
