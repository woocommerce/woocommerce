/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { dispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { parse, rawHandler } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { store } from '../../../../store/product-editor-ui';
import { getContentFromFreeform } from '../edit';
import { getGutenbergVersion } from '../../../../utils/get-gutenberg-version';

// There is a bug in Gutenberg 17.9 that causes a crash in the full editor.
// This should be fixed in Gutenberg 18.0 (see https://github.com/WordPress/gutenberg/pull/59800).
// Once we only support Gutenberg 18.0 and above, we can remove this check.
function isGutenbergVersionWithCrashInFullEditor() {
	const gutenbergVersion = getGutenbergVersion();
	return gutenbergVersion >= 17.9 && gutenbergVersion < 18.0;
}

function shouldForceFullEditor() {
	return (
		localStorage
			.getItem(
				'__unsupported_force_product_editor_description_full_editor'
			)
			?.trim()
			.toLowerCase() === 'true'
	);
}

export default function FullEditorToolbarButton( {
	label = __( 'Edit Product description', 'woocommerce' ),
	text = __( 'Full editor', 'woocommerce' ),
} ) {
	const { openModalEditor, setModalEditorBlocks } = dispatch( store );
	const [ description ] = useEntityProp< string >(
		'postType',
		'product',
		'description'
	);

	return (
		<ToolbarButton
			label={ label }
			onClick={ () => {
				if ( isGutenbergVersionWithCrashInFullEditor() ) {
					if ( shouldForceFullEditor() ) {
						// eslint-disable-next-line no-alert
						alert(
							__(
								'The version of the Gutenberg plugin installed causes a crash in the full editor. You are proceeding at your own risk and may experience crashes.',
								'woocommerce'
							)
						);
					} else {
						// eslint-disable-next-line no-alert
						alert(
							__(
								'The version of the Gutenberg plugin installed causes a crash in the full editor. To prevent this, the full editor has been disabled.',
								'woocommerce'
							)
						);
						return;
					}
				}

				let parsedBlocks = parse( description );
				const freeformContent = getContentFromFreeform( parsedBlocks );

				// replace the freeform block with a paragraph block
				if ( freeformContent ) {
					parsedBlocks = rawHandler( { HTML: freeformContent } );
				}

				setModalEditorBlocks( parsedBlocks );
				recordEvent( 'product_add_description_click' );
				openModalEditor();
			} }
		>
			{ text }
		</ToolbarButton>
	);
}
