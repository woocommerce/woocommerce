// This logic is copied from: https://github.com/WordPress/gutenberg/blob/29c620c79a4c3cfa4c1300cd3c9eeeb06709d3e0/packages/block-editor/src/components/block-toolbar/shuffle.js

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

export default function Delete( { clientId }: { clientId: string } ) {
	// @ts-expect-error missing type
	const { removeBlock } = useDispatch( blockEditorStore );

	return (
		<ToolbarGroup>
			<ToolbarButton>
				<Button
					label={ __( 'Remove', 'woocommerce' ) }
					icon={ trash }
					onClick={ () => {
						removeBlock( clientId );
					} }
				/>
			</ToolbarButton>
		</ToolbarGroup>
	);
}
