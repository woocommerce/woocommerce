/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';

const CollectionChooserToolbar = ( props: {
	openCollectionSelectionModal: () => void;
} ) => {
	return (
		<ToolbarGroup>
			<ToolbarButton onClick={ props.openCollectionSelectionModal }>
				{ __( 'Choose collection', 'woocommerce' ) }
			</ToolbarButton>
		</ToolbarGroup>
	);
};

export default CollectionChooserToolbar;
