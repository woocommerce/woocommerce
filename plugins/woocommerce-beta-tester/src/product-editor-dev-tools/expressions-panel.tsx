/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { ExpressionField } from './expression-field';

export function ExpressionsPanel( {
	evaluationContext,
}: {
	evaluationContext?: object;
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
				<textarea
					value={ expressionToAdd }
					onChange={ handleExpressionToAddChange }
				/>
				<Button onClick={ addExpression }>Add</Button>
			</div>
		</div>
	);
}
