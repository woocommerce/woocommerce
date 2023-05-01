/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { ListItem } from '@woocommerce/components';

type NewAttributeListItemProps = {
	label?: string;
	onClick?: () => void;
};

export const NewAttributeListItem: React.FC< NewAttributeListItemProps > = ( {
	label = __( 'Add attribute', 'woocommerce' ),
	onClick,
} ) => {
	return (
		<ListItem className="woocommerce-add-attribute-list-item">
			<Button
				variant="secondary"
				className="woocommerce-add-attribute-list-item__add-button"
				onClick={ onClick }
			>
				{ label }
			</Button>
		</ListItem>
	);
};
