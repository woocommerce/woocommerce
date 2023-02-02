/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, ButtonGroup } from '@wordpress/components';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	ProductVariation,
} from '@woocommerce/data';
import { registerPlugin } from '@wordpress/plugins';
import { useDispatch } from '@wordpress/data';
import { useFormContext2 } from '@woocommerce/components';
import { useParams } from 'react-router-dom';
import { useState } from '@wordpress/element';
import { useWatch } from 'react-hook-form';

/**
 * Internal dependencies
 */
import { preventLeavingProductForm } from './utils/prevent-leaving-product-form';
import usePreventLeavingPage from '~/hooks/usePreventLeavingPage';
import { WooHeaderItem } from '~/header/utils';
import './product-form-actions.scss';

export const ProductVariationFormActions: React.FC = () => {
	const { productId, variationId } = useParams();
	const { formState, control, getValues } =
		useFormContext2< ProductVariation >();
	const isDirty = formState.isDirty;
	const isValidForm = formState.isValid;

	const [ manage_stock, permalink ] = useWatch( {
		name: [ 'manage_stock', 'permalink' ],
		control,
	} );

	const values = getValues();

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
					manage_stock === 'parent' ? undefined : manage_stock,
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
						href={ permalink + '?preview=true' }
						disabled={ ! isValidForm || ! permalink }
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
