/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DragEventHandler } from 'react';
import { Button } from '@wordpress/components';
import { ListItem } from '@woocommerce/components';

type AddAttributeListItemProps = {
	label?: string;
	onAddClick?: () => void;
};

export const AddAttributeListItem: React.FC< AddAttributeListItemProps > = ( {
	label = __( 'Add attribute', 'woocommerce' ),
	onAddClick,
} ) => {
	return (
		<ListItem className="woocommerce-add-attribute-list-item">
			<Button
				variant="secondary"
				className="woocommerce-add-attribute-list-item__add-button"
				onClick={ onAddClick }
			>
				{ label }
			</Button>
		</ListItem>
	);
};
