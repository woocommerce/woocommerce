/**
 * External dependencies
 */
import type { FormEvent } from 'react';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';
import { Button, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import {
	createElement,
	createInterpolateElement,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { VariationStockStatusFormProps } from './types';
import { RadioField } from '../../radio-field';

const MANAGE_STOCK_OPTION = 'woocommerce_manage_stock';
const STOCK_STATUS_OPTIONS = [
	{
		label: __( 'In stock', 'woocommerce' ),
		value: 'instock',
	},
	{
		label: __( 'Out of stock', 'woocommerce' ),
		value: 'outofstock',
	},
	{
		label: __( 'On backorder', 'woocommerce' ),
		value: 'onbackorder',
	},
];

export function VariationStockStatusForm( {
	initialValue,
	onSubmit,
	onCancel,
}: VariationStockStatusFormProps ) {
	const [ value, setValue ] = useState( {
		manage_stock: Boolean( initialValue?.manage_stock ),
		stock_status: initialValue?.stock_status,
	} );

	const { canManageStock, isLoadingManageStockOption } = useSelect(
		( select ) => {
			const { getOption, isResolving } = select( OPTIONS_STORE_NAME );

			return {
				canManageStock: getOption( MANAGE_STOCK_OPTION ) === 'yes',
				isLoadingManageStockOption: isResolving( 'getOption', [
					MANAGE_STOCK_OPTION,
				] ),
			};
		},
		[]
	);

	function handleSubmit( event: FormEvent< HTMLFormElement > ) {
		event.preventDefault();

		onSubmit?.( value );
	}

	function handleTrackInventoryToggleChange( isChecked: boolean ) {
		setValue( ( current ) => ( { ...current, manage_stock: isChecked } ) );
	}

	function renderTrackInventoryToggleHelp() {
		if ( isLoadingManageStockOption || canManageStock ) return undefined;
		return createInterpolateElement(
			/* translators: <Link>: Learn more link opening tag. </Link>: Learn more link closing tag.*/
			__(
				'Per your <Link>store settings</Link>, inventory management is <strong>disabled</strong>.',
				'woocommerce'
			),
			{
				Link: (
					<a
						href={ getAdminLink(
							'admin.php?page=wc-settings&tab=products&section=inventory'
						) }
						target="_blank"
						rel="noreferrer"
					/>
				),
				strong: <strong />,
			}
		);
	}

	function handleStockStatusRadioFieldChange( selected: string ) {
		setValue( ( current ) => ( { ...current, stock_status: selected } ) );
	}

	return (
		<form
			onSubmit={ handleSubmit }
			className="woocommerce-variation-stock-status-form"
			aria-label={ __( 'Variation stock status form', 'woocommerce' ) }
		>
			<div className="woocommerce-variation-stock-status-form__controls">
				<ToggleControl
					label={ __( 'Track inventory', 'woocommerce' ) }
					disabled={ isLoadingManageStockOption || ! canManageStock }
					checked={ value.manage_stock }
					onChange={ handleTrackInventoryToggleChange }
					help={ renderTrackInventoryToggleHelp() }
				/>
			</div>

			{ ! value.manage_stock && (
				<div className="woocommerce-variation-stock-status-form__controls">
					<RadioField
						title={ __( 'Stock status', 'woocommerce' ) }
						selected={ value.stock_status }
						options={ STOCK_STATUS_OPTIONS }
						onChange={ handleStockStatusRadioFieldChange }
					/>
				</div>
			) }

			<div className="woocommerce-variation-stock-status-form__actions">
				<Button variant="tertiary" onClick={ onCancel }>
					Cancel
				</Button>

				<Button variant="primary" type="submit">
					Save
				</Button>
			</div>
		</form>
	);
}
