/**
 * External dependencies
 */
import { Fill } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import {
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { SectionDescriptionBlockAttributes } from './types';

export function SectionDescriptionBlockEdit( {
	attributes,
	clientId,
}: ProductEditorBlockEditProps< SectionDescriptionBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const innerBlockProps = useInnerBlocksProps( blockProps, {
		templateLock: 'all',
	} );

	const rootClientId = useSelect(
		( select ) => {
			const { getBlockRootClientId } = select( 'core/block-editor' );
			return getBlockRootClientId( clientId );
		},
		[ clientId ]
	);

	if ( ! rootClientId ) return;

	return <Fill { ...innerBlockProps } name={ rootClientId } />;
}
