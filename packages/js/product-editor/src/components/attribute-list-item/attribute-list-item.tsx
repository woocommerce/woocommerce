/**
 * External dependencies
 */
import { DragEventHandler } from 'react';
import { ListItem } from '@woocommerce/components';
import { ProductAttribute } from '@woocommerce/data';
import { sprintf, __ } from '@wordpress/i18n';
import { Button, Tooltip } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import HiddenIcon from '../variations-table/hidden-icon';
import HelpIcon from './help-icon';
import NotFilterableIcon from './not-filterable-icon';

type AttributeListItemProps = {
	attribute: ProductAttribute;
	editLabel?: string;
	removeLabel?: string;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onEditClick?: ( attribute: ProductAttribute ) => void;
	onRemoveClick?: ( attribute: ProductAttribute ) => void;
};

const NOT_VISIBLE_TEXT = __( 'Not visible', 'woocommerce' );
const NOT_FILTERABLE_CUSTOM_ATTR_TEXT = __(
	'Custom attribute. Customers can’t filter or search by it to find this product',
	'woocommerce'
);

export const AttributeListItem: React.FC< AttributeListItemProps > = ( {
	attribute,
	editLabel = __( 'Edit', 'woocommerce' ),
	removeLabel = __( 'Remove attribute', 'woocommerce' ),
	onDragStart,
	onDragEnd,
	onEditClick,
	onRemoveClick,
} ) => {
	return (
		<ListItem
			className="woocommerce-attribute-list-item"
			onDragStart={ onDragStart }
			onDragEnd={ onDragEnd }
		>
			<div>{ attribute.name }</div>
			<div className="woocommerce-attribute-list-item__options">
				{ attribute.options.slice( 0, 2 ).map( ( option, index ) => (
					<div
						className="woocommerce-attribute-list-item__option-chip"
						key={ index }
					>
						{ option }
					</div>
				) ) }
				{ attribute.options.length > 2 && (
					<div className="woocommerce-attribute-list-item__option-chip">
						{ sprintf(
							__( '+ %i more', 'woocommerce' ),
							attribute.options.length - 2
						) }
					</div>
				) }
			</div>
			<div className="woocommerce-attribute-list-item__actions">
				{ attribute.id === 0 && (
					<Tooltip
						// @ts-expect-error className is missing in TS, should remove this when it is included.
						className="woocommerce-attribute-list-item__actions-tooltip"
						position="top center"
						text={ NOT_FILTERABLE_CUSTOM_ATTR_TEXT }
					>
						<div className="woocommerce-attribute-list-item__actions-icon-wrapper">
							<NotFilterableIcon className="woocommerce-attribute-list-item__actions-icon-wrapper-icon" />
							<HelpIcon className="woocommerce-attribute-list-item__actions-icon-wrapper-help-icon" />
						</div>
					</Tooltip>
				) }
				{ ! attribute.visible && (
					<Tooltip
						// @ts-expect-error className is missing in TS, should remove this when it is included.
						className="woocommerce-attribute-list-item__actions-tooltip"
						position="top center"
						text={ NOT_VISIBLE_TEXT }
					>
						<div className="woocommerce-attribute-list-item__actions-icon-wrapper">
							<HiddenIcon className="woocommerce-attribute-list-item__actions-icon-wrapper-icon" />
							<HelpIcon className="woocommerce-attribute-list-item__actions-icon-wrapper-help-icon" />
						</div>
					</Tooltip>
				) }
				{ typeof onEditClick === 'function' && (
					<Button
						variant="tertiary"
						onClick={ () => onEditClick( attribute ) }
					>
						{ editLabel }
					</Button>
				) }
				{ typeof onRemoveClick === 'function' && (
					<Button
						icon={ closeSmall }
						label={ removeLabel }
						onClick={ () => onRemoveClick( attribute ) }
					></Button>
				) }
			</div>
		</ListItem>
	);
};
