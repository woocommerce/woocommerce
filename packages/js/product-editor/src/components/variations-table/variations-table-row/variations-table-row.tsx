/**
 * External dependencies
 */

import { Tag } from '@woocommerce/components';
import { CurrencyContext } from '@woocommerce/currency';
import { ProductVariation } from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import {
	Button,
	CheckboxControl,
	Spinner,
	Tooltip,
} from '@wordpress/components';
import { createElement, Fragment, useContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { PRODUCT_VARIATION_TITLE_LIMIT } from '../../../constants';
import HiddenIcon from '../../../icons/hidden-icon';
import {
	getProductStockStatus,
	getProductStockStatusClass,
	truncate,
} from '../../../utils';
import { VariationActionsMenu } from '../variation-actions-menu';
import { VariationsTableRowProps } from './types';

const NOT_VISIBLE_TEXT = __( 'Not visible to customers', 'woocommerce' );

function getEditVariationLink( variation: ProductVariation ) {
	return getNewPath(
		{},
		`/product/${ variation.parent_id }/variation/${ variation.id }`,
		{}
	);
}

export function VariationsTableRow( {
	variation,
	isUpdating,
	isSelected,
	isSelectionDisabled,
	hideActionButtons,
	onChange,
	onDelete,
	onEdit,
	onSelect,
}: VariationsTableRowProps ) {
	const { formatAmount } = useContext( CurrencyContext );

	function handleChange( value: Partial< ProductVariation > ) {
		onChange( {
			...variation,
			...value,
		} );
	}

	return (
		<>
			<div className="woocommerce-product-variations__selection">
				{ isUpdating ? (
					<Spinner />
				) : (
					<CheckboxControl
						value={ variation.id }
						checked={ isSelected }
						onChange={ onSelect }
						disabled={ isSelectionDisabled }
					/>
				) }
			</div>
			<div className="woocommerce-product-variations__attributes">
				{ variation.attributes.map( ( attribute ) => {
					const tag = (
						<Tag
							id={ attribute.id }
							className="woocommerce-product-variations__attribute"
							key={ attribute.id }
							label={ truncate(
								attribute.option,
								PRODUCT_VARIATION_TITLE_LIMIT
							) }
							screenReaderLabel={ attribute.option }
						/>
					);

					return attribute.option.length <=
						PRODUCT_VARIATION_TITLE_LIMIT ? (
						tag
					) : (
						<Tooltip
							key={ attribute.id }
							text={ attribute.option }
							position="top center"
						>
							<span>{ tag }</span>
						</Tooltip>
					);
				} ) }
			</div>
			<div
				className={ classNames(
					'woocommerce-product-variations__price',
					{
						'woocommerce-product-variations__price--fade':
							variation.status === 'private',
					}
				) }
			>
				{ variation.on_sale && (
					<span className="woocommerce-product-variations__sale-price">
						{ formatAmount( variation.sale_price ) }
					</span>
				) }
				<span
					className={ classNames(
						'woocommerce-product-variations__regular-price',
						{
							'woocommerce-product-variations__regular-price--on-sale':
								variation.on_sale,
						}
					) }
				>
					{ formatAmount( variation.regular_price ) }
				</span>
			</div>
			<div
				className={ classNames(
					'woocommerce-product-variations__quantity',
					{
						'woocommerce-product-variations__quantity--fade':
							variation.status === 'private',
					}
				) }
			>
				{ variation.regular_price && (
					<>
						<span
							className={ classNames(
								'woocommerce-product-variations__status-dot',
								getProductStockStatusClass( variation )
							) }
						>
							●
						</span>
						{ getProductStockStatus( variation ) }
					</>
				) }
			</div>
			<div className="woocommerce-product-variations__actions">
				{ ( variation.status === 'private' ||
					! variation.regular_price ) && (
					<Tooltip
						// @ts-expect-error className is missing in TS, should remove this when it is included.
						className="woocommerce-attribute-list-item__actions-tooltip"
						position="top center"
						text={ NOT_VISIBLE_TEXT }
					>
						<div className="woocommerce-attribute-list-item__actions-icon-wrapper">
							<HiddenIcon className="woocommerce-attribute-list-item__actions-icon-wrapper-icon" />
						</div>
					</Tooltip>
				) }

				{ hideActionButtons && (
					<>
						<Button
							href={ getEditVariationLink( variation ) }
							onClick={ onEdit }
						>
							{ __( 'Edit', 'woocommerce' ) }
						</Button>

						<VariationActionsMenu
							selection={ variation }
							onChange={ handleChange }
							onDelete={ onDelete }
						/>
					</>
				) }
			</div>
		</>
	);
}
