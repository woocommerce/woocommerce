/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { __ } from '@wordpress/i18n';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { ExpressionField } from './expression-field';

interface ExpressionListItem {
	expression: string;
	mode: 'view' | 'edit';
}

export function ExpressionsPanel( {
	evaluationContext,
}: {
	evaluationContext: {
		postType: string;
		editedProduct: Product;
	};
} ) {
	const [ expressions, setExpressions ] = useState< ExpressionListItem[] >(
		[]
	);
	const [ expressionToAdd, setExpressionToAdd ] = useState< string >( '' );

	const addExpression = ( expression: string ) => {
		setExpressionToAdd( expression );
		setExpressions( [ ...expressions, { expression, mode: 'view' } ] );
		// There has to be a better way to do this, but I'm not sure what it is.
		// Need to wait for the old expression to be set on the ExpressionField before clearing it.
		setTimeout( () => {
			setExpressionToAdd( '' );
		}, 100 );
	};

	const enterEditMode = ( index: number ) => {
		const newExpressions = [ ...expressions ];
		newExpressions[ index ].mode = 'edit';
		setExpressions( newExpressions );
	};

	const cancelEditMode = ( index: number ) => {
		const newExpressions = [ ...expressions ];
		newExpressions[ index ].mode = 'view';
		setExpressions( newExpressions );
	};

	const updateExpression = ( index: number, expression: string ) => {
		const newExpressions = [ ...expressions ];
		newExpressions[ index ].expression = expression;
		newExpressions[ index ].mode = 'view';
		setExpressions( newExpressions );
	};

	return (
		<div className="woocommerce-product-editor-dev-tools-expressions">
			<ul className="woocommerce-product-editor-dev-tools-expressions-list">
				{ expressions.map( ( expressionListItem, index ) => (
					<li key={ index }>
						<ExpressionField
							expression={ expressionListItem.expression }
							evaluationContext={ evaluationContext }
							mode={ expressionListItem.mode }
							onEnterEdit={ () => enterEditMode( index ) }
							onCancel={ () => cancelEditMode( index ) }
							onUpdate={ ( expression ) =>
								updateExpression( index, expression )
							}
						/>
					</li>
				) ) }
				<li>
					<ExpressionField
						expression={ expressionToAdd }
						evaluationContext={ evaluationContext }
						mode="edit"
						onUpdate={ addExpression }
						updateLabel={ __( 'Add', 'woocommerce' ) }
					/>
				</li>
			</ul>
		</div>
	);
}
