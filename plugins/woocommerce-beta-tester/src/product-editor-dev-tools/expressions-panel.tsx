/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

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

	function addExpression( expression: string ) {
		setExpressionItems( [
			...expressionItems,
			{ expression, mode: 'view' },
		] );
	}

	function updateExpression( index: number, expression: string ) {
		const newItems = [ ...expressionItems ];
		newItems[ index ].expression = expression;
		newItems[ index ].mode = 'view';
		setExpressionItems( newItems );
	}

	function removeExpression( index: number ) {
		return () => {
			const newItems = expressionItems.filter(
				( item, i ) => i !== index
			);
			setExpressionItems( newItems );
		};
	}

	return (
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
						onRemove={ removeExpression( index ) }
					/>
				</li>
			) ) }
			<li key={ expressionItems.length + 1 }>
				<ExpressionField
					evaluationContext={ evaluationContext }
					mode={ 'edit' }
					onUpdate={ ( expression ) => addExpression( expression ) }
					updateLabel={ __( 'Add', 'woocommerce' ) }
				/>
			</li>
		</ul>
	);
}
