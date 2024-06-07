/**
 * External dependencies
 */

import { Tag, __experimentalTooltip as Tooltip } from '@woocommerce/components';
import { CurrencyContext } from '@woocommerce/currency';
import { PartialProductVariation, ProductVariation } from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import { Button, CheckboxControl, Spinner } from '@wordpress/components';
import {
	createElement,
	Fragment,
	useContext,
	useMemo,
} from '@wordpress/element';
import { plus, info, Icon } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';
import { MediaUpload } from '@wordpress/media-utils';

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
import { SingleUpdateMenu } from '../variation-actions-menus';
import { VariationsTableRowProps } from './types';
import { mapUploadImageToImage } from '../../../utils/map-upload-image-to-image';

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
	variableAttributes,
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

	const { matchesAny, tags } = useMemo(
		function getAnyWhenVariationOptionIsNotPresentInProductAttributes() {
			let matches = false;

			const tagItems = variableAttributes.map( ( attribute ) => {
				const variationOption = variation.attributes.find(
					( option ) => option.id === attribute.id
				);

				if ( variationOption ) {
					return {
						id: variationOption.id,
						label: variationOption.option,
					};
				}

				matches = true;

				return {
					id: attribute.id,
					label: sprintf(
						// translators: %s is the attribute's name
						__( 'Any %s', 'woocommerce' ),
						attribute.name
					),
				};
			} );

			return {
				matchesAny: matches,
				tags: tagItems,
			};
		},
		[ variableAttributes, variation ]
	);

	function handleChange(
		values: PartialProductVariation[],
		showSuccess: boolean
	) {
		onChange( values[ 0 ], showSuccess );
	}

	function handleDelete( values: PartialProductVariation[] ) {
		onDelete( values[ 0 ] );
	}

	return (
		<>
			<div
				className="woocommerce-product-variations__selection"
				role="cell"
			>
				{ matchesAny && (
					<Tooltip
						text={ __(
							"'Any' variations are no longer fully supported. Use regular variations instead",
							'woocommerce'
						) }
						helperText={ __( 'View helper text', 'woocommerce' ) }
						position="middle right"
					>
						<Icon icon={ info } size={ 24 } />
					</Tooltip>
				) }

				{ isUpdating ? (
					<Spinner />
				) : (
					<CheckboxControl
						value={ variation.id }
						checked={ isSelected }
						onChange={ onSelect }
						disabled={ isSelectionDisabled }
						aria-label={
							isSelected
								? __( 'Unselect variation', 'woocommerce' )
								: __( 'Select variation', 'woocommerce' )
						}
					/>
				) }
			</div>
			<div
				className="woocommerce-product-variations__attributes-cell"
				role="cell"
			>
				<MediaUpload
					value={ variation.id }
					onSelect={ ( image ) =>
						handleChange(
							[
								{
									id: variation.id,
									image:
										mapUploadImageToImage( image ) ||
										undefined,
								},
							],
							false
						)
					}
					allowedTypes={ [ 'image' ] }
					multiple={ false }
					render={ ( { open } ) => (
						<Button
							className={ classNames(
								variation.image
									? 'woocommerce-product-variations__image-button'
									: 'woocommerce-product-variations__add-image-button'
							) }
							icon={ variation.image ? undefined : plus }
							iconSize={ variation.image ? undefined : 16 }
							// eslint-disable-next-line @typescript-eslint/ban-ts-comment
							// @ts-ignore this exists in the props but is not typed
							size="compact"
							onClick={ open }
						>
							{ variation.image && (
								<img
									className="woocommerce-product-variations__image"
									src={ variation.image.src }
									alt={ variation.image.alt }
								/>
							) }
						</Button>
					) }
				/>

				<div className="woocommerce-product-variations__attributes">
					{ tags.map( ( tagInfo ) => {
						const tag = (
							<Tag
								id={ tagInfo.id }
								className="woocommerce-product-variations__attribute"
								key={ tagInfo.id }
								label={ truncate(
									tagInfo.label,
									PRODUCT_VARIATION_TITLE_LIMIT
								) }
								screenReaderLabel={ tagInfo.label }
							/>
						);

						return tags.length <= PRODUCT_VARIATION_TITLE_LIMIT ? (
							tag
						) : (
							<Tooltip
								key={ tagInfo.id }
								text={ tagInfo.label }
								position="top center"
							>
								<span>{ tag }</span>
							</Tooltip>
						);
					} ) }
				</div>
			</div>
			<div
				className={ classNames(
					'woocommerce-product-variations__price',
					{
						'woocommerce-product-variations__price--fade':
							variation.status === 'private',
					}
				) }
				role="cell"
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
				role="cell"
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
			<div
				className="woocommerce-product-variations__actions"
				role="cell"
			>
				{ ( variation.status === 'private' ||
					! variation.regular_price ) && (
					<Tooltip
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

						<SingleUpdateMenu
							selection={ [ variation ] }
							onChange={ handleChange }
							onDelete={ handleDelete }
						/>
					</>
				) }
			</div>
		</>
	);
}
