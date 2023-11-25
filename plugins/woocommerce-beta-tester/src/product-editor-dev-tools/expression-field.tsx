/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { evaluate } from '@woocommerce/expression-evaluation';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { check, close, edit } from '@wordpress/icons';
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
					onChange={ handleOnChange }
					value={ editedExpression }
				/>
				{ editedExpression !== '' && (
					<div className="woocommerce-product-editor-dev-tools-expression-field__result">
						{ result }
					</div>
				) }
			</div>
			<div className="woocommerce-product-editor-dev-tools-expression-field__actions">
				{ mode === 'edit' ? (
					<>
						<Button
							icon={ check }
							label={ __( 'Add', 'woocommerce' ) }
							onClick={ handleOnUpdate }
						/>
						<>
							{ onCancel && (
								<Button
									icon={ close }
									label={ __( 'Cancel', 'woocommerce' ) }
									onClick={ handleOnCancel }
								/>
							) }
						</>
					</>
				) : (
					<>
						{ onEnterEdit && (
							<Button
								icon={ edit }
								label={ __( 'Edit', 'woocommerce' ) }
								onClick={ onEnterEdit }
							/>
						) }
					</>
				) }
			</div>
		</div>
	);
}
