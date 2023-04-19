/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useState } from '@wordpress/element';
import { serialize } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { IframeEditor } from '../../components/iframe-editor';

/**
 * Internal dependencies
 */

export function Edit() {
	const blockProps = useBlockProps();
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ , setDescription ] = useEntityProp< string >(
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
				{ __( 'Add description', 'woocommerce' ) }
			</Button>
			{ isModalOpen && (
				<IframeEditor
					onChange={ ( blocks ) => {
						const html = serialize( blocks );
						setDescription( html );
					} }
					onClose={ () => setIsModalOpen( false ) }
				/>
			) }
		</div>
	);
}
