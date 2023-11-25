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
				evaluate( editedExpression, evaluationContext ),
				null,
				4
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
			<div className="woocommerce-product-editor-dev-tools-expression-field__expression_and_result">
				<textarea
					className="woocommerce-product-editor-dev-tools-expression-field__expression"
					placeholder={ __(
						'Enter an expression to evaluate',
						'woocommerce'
					) }
					readOnly={ mode !== 'edit' }
					value={ editedExpression }
					onChange={ handleOnChange }
				/>
				<div className="woocommerce-product-editor-dev-tools-expression-field__result">
					{ editedExpression !== '' && result }
				</div>
			</div>
			<div className="woocommerce-product-editor-dev-tools-expression-field__actions">
				{ mode === 'edit' ? (
					<>
						<Button onClick={ handleOnUpdate }>
							{ __( 'Add', 'woocommerce' ) }
						</Button>
						<>
							{ onCancel && (
								<Button onClick={ handleOnCancel }>
									{ __( 'Cancel', 'woocommerce' ) }
								</Button>
							) }
						</>
					</>
				) : (
					<>
						{ onEnterEdit && (
							<Button onClick={ onEnterEdit }>
								{ __( 'Edit', 'woocommerce' ) }
							</Button>
						) }
					</>
				) }
			</div>
		</div>
	);
}
