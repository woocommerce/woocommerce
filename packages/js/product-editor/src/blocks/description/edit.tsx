/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useState } from '@wordpress/element';
import { parse, serialize } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { IframeEditor } from '../../components/iframe-editor';
import { ContentPreview } from '../../components/content-preview';

/**
 * Internal dependencies
 */

export function Edit() {
	const blockProps = useBlockProps();
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ description, setDescription ] = useEntityProp< string >(
		'postType',
		'product',
		'description'
	);

	return (
		<div { ...blockProps }>
			<Button
				variant="secondary"
				onClick={ () => setIsModalOpen( true ) }
			>
				{ description.length
					? __( 'Edit description', 'woocommerce' )
					: __( 'Add description', 'woocommerce' ) }
			</Button>
			{ isModalOpen && (
				<IframeEditor
					initialBlocks={ parse( description ) }
					onChange={ ( blocks ) => {
						const html = serialize( blocks );
						setDescription( html );
					} }
					onClose={ () => setIsModalOpen( false ) }
				/>
			) }
			{ !! description.length && (
				<ContentPreview content={ description } />
			) }
		</div>
	);
}
