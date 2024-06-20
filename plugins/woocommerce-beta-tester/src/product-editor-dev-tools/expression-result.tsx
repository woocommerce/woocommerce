type ExpressionResultProps = {
	result: any;
	error: any;
	isDirty: boolean;
};

export function ExpressionResult( {
	result,
	error,
	isDirty,
}: ExpressionResultProps ) {
	const errorString = error && isDirty ? String( error ) : '';

	const resultString = error
		? errorString
		: JSON.stringify( result, null, 4 );

	return (
		<div className="woocommerce-product-editor-dev-tools-expression-field__result">
			{ resultString }
		</div>
	);
}
