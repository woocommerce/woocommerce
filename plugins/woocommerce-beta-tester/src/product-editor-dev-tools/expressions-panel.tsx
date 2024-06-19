/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { ExpressionField } from './expression-field';

type ExpressionItem = {
	expression: string;
	mode: 'view' | 'edit';
};

export function ExpressionsPanel( {
	evaluationContext,
}: {
	evaluationContext?: object;
} ) {
	const [ expressionItems, setExpressionItems ] = useState<
		ExpressionItem[]
	>( [] );
	const [ expressionToAdd, setExpressionToAdd ] = useState< string >( '' );

	const handleExpressionToAddChange = (
		event: React.ChangeEvent< HTMLTextAreaElement >
	) => {
		setExpressionToAdd( event.target.value );
	};

	const addExpression = ( expression: string ) => {
		setExpressionItems( [
			...expressionItems,
			{ expression, mode: 'view' },
		] );
		setExpressionToAdd( '' );
	};

	function enterEditMode( index: number ) {
		const newItems = [ ...expressionItems ];
		newItems[ index ].mode = 'edit';
		setExpressionItems( newItems );
	}

	function cancelEdit( index: number ) {
		const newItems = [ ...expressionItems ];
		newItems[ index ].mode = 'view';
		setExpressionItems( newItems );
	}

	function updateExpression( index: number, expression: string ) {
		const newItems = [ ...expressionItems ];
		newItems[ index ].expression = expression;
		newItems[ index ].mode = 'view';
		setExpressionItems( newItems );
	}

	return (
		<div className="woocommerce-product-editor-dev-tools-expressions">
			{ expressionItems.length === 0 && (
				<div className="woocommerce-product-editor-dev-tools-expressions-list empty">
					Enter an expression to evaluate below.
				</div>
			) }
			{ expressionItems.length > 0 && (
				<ul className="woocommerce-product-editor-dev-tools-expressions-list">
					{ expressionItems.map( ( expressionItem, index ) => (
						<li key={ index }>
							<ExpressionField
								expression={ expressionItem.expression }
								evaluationContext={ evaluationContext }
								mode={ expressionItem.mode }
								onEnterEdit={ () => enterEditMode( index ) }
								onCancel={ () => cancelEdit( index ) }
								onUpdate={ ( expression ) =>
									updateExpression( index, expression )
								}
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
				<Button onClick={ () => addExpression( expressionToAdd ) }>
					Add
				</Button>
			</div>
		</div>
	);
}
