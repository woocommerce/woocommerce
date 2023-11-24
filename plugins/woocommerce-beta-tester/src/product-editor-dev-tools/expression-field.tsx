/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { evaluate } from '@woocommerce/expression-evaluation';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';

export function ExpressionField( {
	expression,
	evaluationContext,
	mode = 'view',
	onEnterEdit,
	onUpdate = () => {},
	onCancel,
}: {
	expression: string;
	evaluationContext: {
		postType: string;
		editedProduct: Product;
	};
	mode?: 'view' | 'edit';
	onEnterEdit?: () => void;
	onUpdate?: ( expression: string ) => void;
	onCancel?: () => void;
} ) {
	const [ editedExpression, setEditedExpression ] =
		useState< string >( expression );

	useEffect( () => {
		setEditedExpression( expression );
	}, [ expression ] );

	const evaluateExpression = () => {
		let result;

		try {
			result = JSON.stringify(
				evaluate( editedExpression, evaluationContext )
			);
		} catch ( error ) {
			result = __( 'Error evaluating expression', 'woocommerce' );
		}

		return result;
	};

	const result = evaluateExpression();

	const handleOnChange = (
		event: React.ChangeEvent< HTMLTextAreaElement >
	) => {
		setEditedExpression( event.target.value );
	};

	const handleOnUpdate = () => {
		onUpdate( editedExpression );
	};

	const handleOnCancel = () => {
		if ( onCancel ) onCancel();
	};

	return (
		<div className="woocommerce-product-editor-dev-tools-expression-field">
			{ mode === 'edit' && (
				<div>
					<textarea
						placeholder={ __(
							'Enter an expression to evaluate',
							'woocommerce'
						) }
						value={ editedExpression }
						onChange={ handleOnChange }
					/>
					<Button onClick={ handleOnUpdate }>
						{ __( 'Add', 'woocommerce' ) }
					</Button>
					{ onCancel && (
						<Button onClick={ handleOnCancel }>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
					) }
				</div>
			) }
			{ mode !== 'edit' && (
				<div>
					<div>{ expression }</div>
					{ onEnterEdit && (
						<Button onClick={ onEnterEdit }>
							{ __( 'Edit', 'woocommerce' ) }
						</Button>
					) }
				</div>
			) }
			<div>{ editedExpression !== '' && result }</div>
		</div>
	);
}
