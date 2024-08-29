/**
 * External dependencies
 */
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { trash } from '@wordpress/icons';
import {
	store as blockEditorStore,
	// @ts-expect-error missing type
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { trackEvent } from '~/customize-store/tracking';

export default function Delete( {
	clientId,
	currentBlockName,
	nextBlockClientId,
}: {
	clientId: string;
	currentBlockName: string | undefined;
	nextBlockClientId: string | undefined;
} ) {
	// @ts-expect-error missing type
	const { removeBlock, selectBlock } = useDispatch( blockEditorStore );

	return (
		<ToolbarGroup>
			<ToolbarButton
				showTooltip={ true }
				label={ __( 'Delete', 'woocommerce' ) }
				icon={ trash }
				onClick={ () => {
					removeBlock( clientId );
					if ( nextBlockClientId ) {
						selectBlock( nextBlockClientId );
					}
					trackEvent(
						'customize_your_store_assembler_pattern_delete_click',
						{ pattern: currentBlockName }
					);
				} }
			/>
		</ToolbarGroup>
	);
}
