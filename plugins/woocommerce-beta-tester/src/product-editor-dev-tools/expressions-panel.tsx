/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { evaluate } from '@woocommerce/expression-evaluation';
import { Button } from '@wordpress/components';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { ExpressionField } from './expression-field';

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

	const addExpression = ( expression: string ) => {
		setExpressions( [ ...expressions, expression ] );
		setExpressionToAdd( '' );
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
							<ExpressionField
								expression={ expression }
								evaluationContext={ evaluationContext }
							/>
						</li>
					) ) }
				</ul>
			) }
			<div className="woocommerce-product-editor-dev-tools-expression-editor">
				<ExpressionField
					expression={ expressionToAdd }
					evaluationContext={ evaluationContext }
					mode="edit"
					onUpdate={ addExpression }
				/>
			</div>
		</div>
	);
}
