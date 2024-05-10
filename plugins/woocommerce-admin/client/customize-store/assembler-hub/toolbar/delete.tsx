/**
 * External dependencies
 */
import { trash } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
	store as blockEditorStore,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/block-editor';

export const Delete = ( { clientId }: { clientId: string } ) => {
	// @ts-expect-error missing type
	const { removeBlocks } = useDispatch( blockEditorStore );

	return (
		<Button
			icon={ trash }
			label={ __( 'Delete', 'woocommerce' ) }
			onClick={ () => {
				removeBlocks( clientId );
			} }
		/>
	);
};
