/**
 * External dependencies
 */
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

export function ExpressionField( {
	expression,
	evaluationContext,
}: {
	expression: string;
	evaluationContext?: object;
} ) {
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
		</div>
	);
}
