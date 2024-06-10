/**
 * External dependencies
 */
import { Button, ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { trash } from '@wordpress/icons';
import {
	store as blockEditorStore,
	// @ts-expect-error missing type
} from '@wordpress/block-editor';

export default function Delete( {
	clientId,
	nextBlockClientId,
}: {
	clientId: string;
	nextBlockClientId: string | undefined;
} ) {
	// @ts-expect-error missing type
	const { removeBlock, selectBlock } = useDispatch( blockEditorStore );

	return (
		<ToolbarGroup>
			<ToolbarButton>
				<Button
					label={ __( 'Remove', 'woocommerce' ) }
					icon={ trash }
					onClick={ () => {
						removeBlock( clientId );
						selectBlock( nextBlockClientId );
					} }
				/>
			</ToolbarButton>
		</ToolbarGroup>
	);
}
