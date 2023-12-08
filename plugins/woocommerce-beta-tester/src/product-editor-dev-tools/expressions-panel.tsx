/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { evaluate } from '@woocommerce/expression-evaluation';
import { Button } from '@wordpress/components';
import { useState } from 'react';

export function ExpressionsPanel( {
	evaluationContext,
}: {
	evaluationContext: {
		postType: string;
		editedProduct: Product;
	};
} ) {
	const [ expressions, setExpressions ] = useState< Array< string > >( [] );
	const [ expressionToAdd, setExpressionToAdd ] = useState< string >( '' );

	const handleExpressionToAddChange = (
		event: React.ChangeEvent< HTMLTextAreaElement >
	) => {
		setExpressionToAdd( event.target.value );
	};

	const addExpression = () => {
		setExpressions( [ ...expressions, expressionToAdd ] );
		setExpressionToAdd( '' );
	};

	const evaluateExpression = ( expression: string ) => {
		let result;

		try {
			result = evaluate( expression, evaluationContext );
		} catch ( error ) {
			result = error;
		}

		return String( result );
	};

	return (
		<div className="woocommerce-product-editor-dev-tools-expressions">
			{ expressions.length === 0 && (
				<div className="woocommerce-product-editor-dev-tools-expressions-list empty">
					Enter an expression to evaluate below.
				</div>
			) }
			{ expressions.length > 0 && (
				<ul className="woocommerce-product-editor-dev-tools-expressions-list">
					{ expressions.map( ( expression, index ) => (
						<li key={ index }>
							<div>
								<span className="woocommerce-product-editor-dev-tools-expressions-list-prompt">
									&gt;
								</span>{ ' ' }
								{ expression }
							</div>
							<div>
								<span className="woocommerce-product-editor-dev-tools-expressions-list-prompt">
									&lt;
								</span>{ ' ' }
								{ evaluateExpression( expression ) }
							</div>
						</li>
					) ) }
				</ul>
			) }
			<div className="woocommerce-product-editor-dev-tools-expression-editor">
				<textarea
					value={ expressionToAdd }
					onChange={ handleExpressionToAddChange }
				/>
				<Button onClick={ addExpression }>Add</Button>
			</div>
		</div>
	);
}
