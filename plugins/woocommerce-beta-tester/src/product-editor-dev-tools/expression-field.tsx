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
	onCancel = () => {},
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
			result = evaluate( editedExpression, evaluationContext );
		} catch ( error ) {
			result = __( 'Error evaluating expression', 'woocommerce' );
		}

		return JSON.stringify( result );
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
		onCancel();
	};

	return (
		<div className="woocommerce-product-editor-dev-tools-expression-field">
			{ mode === 'edit' && (
				<div>
					<textarea
						value={ editedExpression }
						onChange={ handleOnChange }
					/>
					<Button onClick={ handleOnUpdate }>
						{ __( 'Add', 'woocommerce' ) }
					</Button>
					<Button onClick={ handleOnCancel }>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
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
			<div>{ result }</div>
		</div>
	);
}
