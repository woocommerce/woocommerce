/**
 * External dependencies
 */
import { synchronizeBlocksWithTemplate, Template } from '@wordpress/blocks';
import { createElement, useMemo, useLayoutEffect } from '@wordpress/element';
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
} from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useConfirmUnsavedProductChanges } from '../../hooks/use-confirm-unsaved-product-changes';
import { ProductEditorContext } from '../../types';

type BlockEditorProps = {
	context: Partial< ProductEditorContext >;
	productType: string;
	productId: number;
	settings:
		| ( Partial< EditorSettings & EditorBlockListSettings > & {
				template?: Template[];
				templates: Record< string, Template[] >;
		  } )
		| undefined;
};

export function BlockEditor( {
	context,
	settings: _settings,
	productType,
	productId,
}: BlockEditorProps ) {
	useConfirmUnsavedProductChanges();

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
		productType,
		{ id: productId }
	);

	useLayoutEffect( () => {
		onChange(
			synchronizeBlocksWithTemplate(
				[],
				_settings?.templates[ productType ]
			),
			{}
		);
	}, [ productId ] );

	const editedProduct: Product = useSelect( ( select ) =>
		select( 'core' ).getEditedEntityRecord(
			'postType',
			productType,
			productId
		)
	);

	if ( ! blocks ) {
		return null;
	}

	return (
		<div className="woocommerce-product-block-editor">
			<BlockContextProvider value={ { ...context, editedProduct } }>
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
			</BlockContextProvider>
		</div>
	);
}
