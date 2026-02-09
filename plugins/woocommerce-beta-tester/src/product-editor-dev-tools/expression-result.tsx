type ExpressionResultProps = {
	result: any;
	error: any;
	showIfError: boolean;
};

export function ExpressionResult( {
	result,
	error,
	showIfError = true,
}: ExpressionResultProps ) {
	const errorString = error && showIfError ? String( error ) : '';

	const resultString = error
		? errorString
		: JSON.stringify( result, null, 4 );

	const resultTypeLabel = error ? 'Error' : typeof result;

	return (
		<div className="woocommerce-product-editor-dev-tools-expression-field__result">
			{ showIfError && (
				<>
					<div className="woocommerce-product-editor-dev-tools-expression-field__result-type">
						{ resultTypeLabel }
					</div>
					<div className="woocommerce-product-editor-dev-tools-expression-field__result-value">
						{ resultString }
					</div>
				</>
			) }
		</div>
	);
}
