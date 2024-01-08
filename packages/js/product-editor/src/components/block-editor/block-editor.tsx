/**
 * External dependencies
 */
import { synchronizeBlocksWithTemplate } from '@wordpress/blocks';
import {
	createElement,
	useMemo,
	useLayoutEffect,
	useEffect,
} from '@wordpress/element';
import { useDispatch, useSelect, select as WPSelect } from '@wordpress/data';
import { uploadMedia } from '@wordpress/media-utils';
import { PluginArea } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { Product } from '@woocommerce/data';
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
import useProductEntityProp from '../../hooks/use-product-entity-prop';
import { useConfirmUnsavedProductChanges } from '../../hooks/use-confirm-unsaved-product-changes';
import { useProductTemplate } from '../../hooks/use-product-template';
import { PostTypeContext } from '../../contexts/post-type-context';
import { store as productEditorUiStore } from '../../store/product-editor-ui';
import { ModalEditor } from '../modal-editor';
import { ProductEditorSettings } from '../editor';
import { BlockEditorProps } from './types';

export function BlockEditor( {
	context,
	settings: _settings,
	postType,
	productId,
}: BlockEditorProps ) {
	useConfirmUnsavedProductChanges( postType );

	const canUserCreateMedia = useSelect( ( select: typeof WPSelect ) => {
		const { canUser } = select( 'core' );
		return canUser( 'create', 'media', '' ) !== false;
	}, [] );

	/**
	 * Fire wp-pin-menu event once to trigger the pinning of the menu.
	 * That can be necessary since wpwrap's height wasn't being recalculated after the skeleton
	 * is switched to the real content, which is usually larger
	 */
	useEffect( () => {
		const wpPinMenuEvent = () => {
			document.dispatchEvent( new Event( 'wp-pin-menu' ) );
		};
		window.addEventListener( 'scroll', wpPinMenuEvent, { once: true } );
		return () => window.removeEventListener( 'scroll', wpPinMenuEvent );
	}, [] );

	const settings = useMemo< Partial< ProductEditorSettings > >( () => {
		const mediaSettings = canUserCreateMedia
			? {
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
			  }
			: {};

		return {
			..._settings,
			...mediaSettings,
			templateLock: 'all',
		};
	}, [ canUserCreateMedia, _settings ] );

	const [ productType ] = useProductEntityProp< Product[ 'type' ] >( 'type', {
		postType,
	} );

	const [ productTemplateId ] = useProductEntityProp< string >(
		'meta_data._product_template_id',
		{ postType }
	);

	const { productTemplate } = useProductTemplate(
		productTemplateId,
		productType
	);

	const [ blocks, onInput, onChange ] = useEntityBlockEditor(
		'postType',
		postType,
		{ id: productId }
	);

	const { updateEditorSettings } = useDispatch( 'core/editor' );

	useLayoutEffect( () => {
		const layoutTemplates = settings?.layoutTemplates ?? [];

		let layoutTemplateId = productTemplate?.layoutTemplateId;
		// A product variation is not a product type so we can not use it to
		// fallback to a default layout template. We use a post type instead.
		if ( ! layoutTemplateId && postType === 'product_variation' ) {
			layoutTemplateId = 'product-variation';
		}

		const layoutTemplate = layoutTemplates.find(
			( template ) => template.id === layoutTemplateId
		);

		if ( ! layoutTemplate ) {
			return;
		}

		const blockInstances = synchronizeBlocksWithTemplate(
			[],
			layoutTemplate.blockTemplates
		);

		onChange( blockInstances, {} );

		updateEditorSettings( {
			...settings,
			productTemplate,
		} as Partial< ProductEditorSettings > );
	}, [ settings, postType, productTemplate, productType ] );

	// Check if the Modal editor is open from the store.
	const isModalEditorOpen = useSelect( ( select ) => {
		return select( productEditorUiStore ).isModalEditorOpen();
	}, [] );

	const { closeModalEditor } = useDispatch( productEditorUiStore );

	if ( ! blocks ) {
		return null;
	}

	if ( isModalEditorOpen ) {
		return (
			<ModalEditor
				onClose={ closeModalEditor }
				title={ __( 'Edit description', 'woocommerce' ) }
			/>
		);
	}

	return (
		<div className="woocommerce-product-block-editor">
			<BlockContextProvider value={ context }>
				<BlockEditorProvider
					value={ blocks }
					onInput={ onInput }
					onChange={ onChange }
					settings={ settings }
					useSubRegistry={ false }
				>
					{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
					{ /* @ts-ignore No types for this exist yet. */ }
					<BlockEditorKeyboardShortcuts.Register />
					<BlockTools>
						<ObserveTyping>
							<BlockList className="woocommerce-product-block-editor__block-list" />
						</ObserveTyping>
					</BlockTools>
					{ /* eslint-disable-next-line @typescript-eslint/no-non-null-assertion */ }
					<PostTypeContext.Provider value={ context.postType! }>
						{ /* @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated. */ }
						<PluginArea scope="woocommerce-product-block-editor" />
					</PostTypeContext.Provider>
				</BlockEditorProvider>
			</BlockContextProvider>
		</div>
	);
}
