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
		setExpressionToAdd( expression );
		setExpressions( [ ...expressions, expression ] );
		// There has to be a better way to do this, but I'm not sure what it is.
		// Need to wait for the old expression to be set on the ExpressionField before clearing it.
		setTimeout( () => {
			setExpressionToAdd( '' );
		}, 100 );
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
