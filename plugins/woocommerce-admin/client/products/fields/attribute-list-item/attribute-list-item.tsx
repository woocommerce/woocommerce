/**
 * External dependencies
 */
import { DragEventHandler } from 'react';
import { ListItem } from '@woocommerce/components';
import { ProductAttribute } from '@woocommerce/data';
import { sprintf, __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './attribute-list-item.scss';

type AttributeListItemProps = {
	attribute: ProductAttribute;
	editLabel?: string;
	removeLabel?: string;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onEditClick?: ( attribute: ProductAttribute ) => void;
	onRemoveClick?: ( attribute: ProductAttribute ) => void;
};

export const AttributeListItem: React.FC< AttributeListItemProps > = ( {
	attribute,
	editLabel = __( 'edit', 'woocommerce' ),
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
