/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, ButtonGroup } from '@wordpress/components';
import { WooHeaderItem } from '@woocommerce/admin-layout';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	ProductVariation,
} from '@woocommerce/data';
import { preventLeavingProductForm } from '@woocommerce/product-editor';
import { registerPlugin } from '@wordpress/plugins';
import { useDispatch } from '@wordpress/data';
import { useFormContext } from '@woocommerce/components';
import { useParams } from 'react-router-dom';
import { useState } from '@wordpress/element';
import { usePreventLeavingPage } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './product-form-actions.scss';

export const ProductVariationFormActions: React.FC = () => {
	const { productId, variationId } = useParams();
	const { isDirty, isValidForm, values } =
		useFormContext< ProductVariation >();
	const { updateProductVariation } = useDispatch(
		EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
	);
	const { createNotice } = useDispatch( 'core/notices' );
	const [ isSaving, setIsSaving ] = useState( false );

	usePreventLeavingPage( isDirty, preventLeavingProductForm );

	const onSave = async () => {
		setIsSaving( true );
		updateProductVariation< Promise< ProductVariation > >(
			{ id: variationId, product_id: productId, context: 'edit' },
			{
				...values,
				manage_stock:
					values.manage_stock === 'parent'
						? undefined
						: values?.manage_stock,
			}
		)
			.then( () => {
				createNotice(
					'success',
					`🎉‎ ${ __(
						'Product variation successfully updated.',
						'woocommerce'
					) }`
				);
			} )
			.catch( () => {
				createNotice(
					'error',
					__( 'Failed to updated product variation.', 'woocommerce' )
				);
			} )
			.finally( () => {
				setIsSaving( false );
			} );
	};

	return (
		<WooHeaderItem>
			{ () => (
				<div className="woocommerce-product-form-actions is-variation">
					<Button
						// eslint-disable-next-line @typescript-eslint/ban-ts-comment
						//@ts-ignore The `href` prop works for both Buttons and MenuItem's.
						href={ values.permalink + '?preview=true' }
						disabled={ ! isValidForm || ! values.permalink }
						target="_blank"
						className="woocommerce-product-form-actions__preview"
					>
						{ __( 'Preview', 'woocommerce' ) }
					</Button>
					<ButtonGroup className="woocommerce-product-form-actions__publish-button-group">
						<Button
							onClick={ onSave }
							variant="primary"
							isBusy={ isSaving }
							disabled={ ! isValidForm || ! isDirty }
						>
							{ __( 'Save', 'woocommerce' ) }
						</Button>
					</ButtonGroup>
				</div>
			) }
		</WooHeaderItem>
	);
};

registerPlugin( 'product-variation-action-buttons-header-item', {
	render: ProductVariationFormActions,
	icon: 'admin-generic',
} );
