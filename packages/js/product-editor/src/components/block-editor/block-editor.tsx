/**
 * External dependencies
 */
import { Template } from '@wordpress/blocks';
import { createElement, useMemo } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { useSelect, select as WPSelect } from '@wordpress/data';
import { uploadMedia } from '@wordpress/media-utils';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockContextProvider,
	BlockEditorKeyboardShortcuts,
	BlockEditorProvider,
	BlockList,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockTools,
	EditorSettings,
	EditorBlockListSettings,
	ObserveTyping,
} from '@wordpress/block-editor';
// It doesn't seem to notice the External dependency block whn @ts-ignore is added.
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore store should be included.
	useEntityBlockEditor,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore store should be included.
	useEntityRecords,
} from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { BlockTemplateProvider } from '../block-template-provider';

type BlockEditorProps = {
	context: {
		[ key: string ]: unknown;
	};
	product: Partial< Product >;
	settings:
		| ( Partial< EditorSettings & EditorBlockListSettings > & {
				template?: Template[];
		  } )
		| undefined;
};

export function BlockEditor( {
	context,
	settings: _settings,
	product,
}: BlockEditorProps ) {
	const canUserCreateMedia = useSelect( ( select: typeof WPSelect ) => {
		const { canUser } = select( 'core' );
		return canUser( 'create', 'media', '' ) !== false;
	}, [] );

	const settings = useMemo( () => {
		if ( ! canUserCreateMedia ) {
			return _settings;
		}
		return {
			..._settings,
			mediaUpload( {
				onError,
				...rest
			}: {
				onError: ( message: string ) => void;
			} ) {
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore No types for this exist yet.
				uploadMedia( {
					wpAllowedMimeTypes:
						_settings?.allowedMimeTypes || undefined,
					onError: ( { message } ) => onError( message ),
					...rest,
				} );
			},
		};
	}, [ canUserCreateMedia, _settings ] );

	const [ blocks, onInput, onChange ] = useEntityBlockEditor(
		'postType',
		'product',
		{ id: product.id }
	);

	const { records: templates } = useEntityRecords(
		'postType',
		'wp_template',
		{
			post_type: 'woocommerce_product_editor_template',
			per_page: -1,
		}
	);

	if ( ! blocks || ! templates ) {
		return null;
	}

	return (
		<div className="woocommerce-product-block-editor">
			<BlockContextProvider value={ context }>
				<BlockTemplateProvider
					onChange={ onChange }
					initialTemplate={
						'woocommerce/woocommerce//product-editor_simple'
					}
					templates={ templates }
				>
					<BlockEditorProvider
						value={ blocks }
						onInput={ onInput }
						onChange={ onChange }
						settings={ settings }
					>
						{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
						{ /* @ts-ignore No types for this exist yet. */ }
						<BlockEditorKeyboardShortcuts.Register />
						<BlockTools>
							<ObserveTyping>
								<BlockList className="woocommerce-product-block-editor__block-list" />
							</ObserveTyping>
						</BlockTools>
					</BlockEditorProvider>
				</BlockTemplateProvider>
			</BlockContextProvider>
		</div>
	);
}
