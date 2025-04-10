/**
 * External dependencies
 */
import type { FormEvent } from 'react';
import { optionsStore } from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';
import { useSelect } from '@wordpress/data';
import {
	createElement,
	createInterpolateElement,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import {
	Button,
	ToggleControl,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { RadioField } from '../../radio-field';
import type { VariationStockStatusFormProps } from './types';

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
		stock_status: initialValue?.stock_status ?? '',
		stock_quantity: initialValue?.stock_quantity ?? 1,
	} );

	const [ errors, setErrors ] = useState< Partial< typeof value > >( {} );

	const { canManageStock, isLoadingManageStockOption } = useSelect(
		( select ) => {
			const { getOption, isResolving } = select( optionsStore );

			return {
				canManageStock: getOption( MANAGE_STOCK_OPTION ) === 'yes',
				isLoadingManageStockOption: isResolving( 'getOption', [
					MANAGE_STOCK_OPTION,
				] ),
			};
		},
		[]
	);

	function validateStockQuantity() {
		let error: string | undefined;

		if (
			value.manage_stock &&
			value.stock_quantity &&
			Number.parseInt( value.stock_quantity, 10 ) < 0
		) {
			error = __(
				'Stock quantity must be a positive number.',
				'woocommerce'
			);
		}

		setErrors( { stock_quantity: error } );

		return ! error;
	}

	function handleSubmit( event: FormEvent< HTMLFormElement > ) {
		event.preventDefault();

		if ( validateStockQuantity() ) {
			onSubmit?.( value );
		}
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
					// eslint-disable-next-line jsx-a11y/anchor-has-content
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

	function handleStockQuantityInputControlChange(
		stock_quantity: string | undefined
	) {
		setValue( ( current ) => ( { ...current, stock_quantity } ) );
	}

	return (
		<form
			onSubmit={ handleSubmit }
			className="woocommerce-variation-stock-status-form"
			aria-label={ __( 'Variation stock status form', 'woocommerce' ) }
			noValidate
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

			<div className="woocommerce-variation-stock-status-form__controls">
				{ value.manage_stock ? (
					<InputControl
						type="number"
						min={ 0 }
						label={ __( 'Available stock', 'woocommerce' ) }
						help={ errors.stock_quantity }
						value={ value.stock_quantity }
						onChange={ handleStockQuantityInputControlChange }
						onBlur={ validateStockQuantity }
						className={ classNames( {
							'has-error': errors.stock_quantity,
						} ) }
					/>
				) : (
					<RadioField
						title={ __( 'Stock status', 'woocommerce' ) }
						selected={ value.stock_status }
						options={ STOCK_STATUS_OPTIONS }
						onChange={ handleStockStatusRadioFieldChange }
					/>
				) }
			</div>

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
