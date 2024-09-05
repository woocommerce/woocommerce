/**
 * External dependencies
 */
import { Tag, __experimentalTooltip as Tooltip } from '@woocommerce/components';
import { CurrencyContext } from '@woocommerce/currency';
import { PartialProductVariation, ProductVariation } from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import {
	Button,
	CheckboxControl,
	Dropdown,
	Spinner,
} from '@wordpress/components';
import {
	createElement,
	Fragment,
	useContext,
	useMemo,
} from '@wordpress/element';
import { plus, info, Icon } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import {
	PRODUCT_VARIATION_TITLE_LIMIT,
	TRACKS_SOURCE,
} from '../../../constants';
import HiddenIcon from '../../../icons/hidden-icon';
import {
	getProductStockStatus,
	getProductStockStatusClass,
	truncate,
} from '../../../utils';
import { SingleUpdateMenu } from '../variation-actions-menus';
import { ImageActionsMenu } from '../image-actions-menu';
import { VariationStockStatusForm } from '../variation-stock-status-form/variation-stock-status-form';
import { VariationPricingForm } from '../variation-pricing-form';
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

	function toggleHandler(
		option: string,
		isOpen: boolean,
		onToggle: () => void
	) {
		return function handleToggle() {
			if ( ! isOpen ) {
				recordEvent( 'product_variations_inline_select', {
					source: TRACKS_SOURCE,
					product_id: variation.parent_id,
					variation_id: variation.id,
					selected_option: option,
				} );
			}
			onToggle();
		};
	}

	function renderImageActionsMenu() {
		return (
			<ImageActionsMenu
				selection={ [ variation ] }
				onChange={ handleChange }
				onDelete={ handleDelete }
				renderToggle={ ( { isOpen, onToggle, isBusy } ) =>
					isBusy ? (
						<div className="woocommerce-product-variations__add-image-button">
							<Spinner
								aria-label={ __(
									'Loading image',
									'woocommerce'
								) }
							/>
						</div>
					) : (
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
							onClick={ toggleHandler(
								'image',
								isOpen,
								onToggle
							) }
						>
							{ variation.image && (
								<div
									className="woocommerce-product-variations__image"
									style={ {
										backgroundImage: `url('${ variation.image.src }')`,
									} }
								/>
							) }
						</Button>
					)
				}
			/>
		);
	}

	function renderPrices() {
		return (
			<>
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
			</>
		);
	}

	function renderPriceForm( onClose: () => void ) {
		return (
			<VariationPricingForm
				initialValue={ variation }
				onSubmit={ ( editedVariation ) => {
					onChange( { ...editedVariation, id: variation.id }, true );
					onClose();
				} }
				onCancel={ onClose }
			/>
		);
	}

	function renderPriceCellContent() {
		if ( ! variation.regular_price ) return null;
		return (
			<Dropdown
				contentClassName="woocommerce-product-variations__pricing-actions-menu"
				// @ts-expect-error missing prop in types.
				popoverProps={ {
					placement: 'bottom',
				} }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						onClick={ toggleHandler( 'price', isOpen, onToggle ) }
					>
						{ renderPrices() }
					</Button>
				) }
				renderContent={ ( { onClose } ) => renderPriceForm( onClose ) }
			/>
		);
	}

	function renderStockStatus() {
		return (
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
		);
	}

	function renderStockStatusForm( onClose: () => void ) {
		return (
			<VariationStockStatusForm
				initialValue={ variation }
				onSubmit={ ( editedVariation ) => {
					onChange( { ...editedVariation, id: variation.id }, true );
					onClose();
				} }
				onCancel={ onClose }
			/>
		);
	}

	function renderStockCellContent() {
		if ( ! variation.regular_price ) return null;

		return (
			<Dropdown
				contentClassName="woocommerce-product-variations__stock-status-actions-menu"
				// @ts-expect-error missing prop in types.
				popoverProps={ {
					placement: 'bottom',
				} }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						onClick={ toggleHandler( 'stock', isOpen, onToggle ) }
						variant="tertiary"
					>
						{ renderStockStatus() }
					</Button>
				) }
				renderContent={ ( { onClose } ) =>
					renderStockStatusForm( onClose )
				}
			/>
		);
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
				{ renderImageActionsMenu() }

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
				{ renderPriceCellContent() }
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
				{ renderStockCellContent() }
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
