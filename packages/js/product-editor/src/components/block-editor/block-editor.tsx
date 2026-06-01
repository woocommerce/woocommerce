/**
 * External dependencies
 */
import {
	BlockInstance,
	synchronizeBlocksWithTemplate,
} from '@wordpress/blocks';
import {
	createElement,
	useMemo,
	useLayoutEffect,
	useEffect,
	useState,
	lazy,
	Suspense,
} from '@wordpress/element';
import { dispatch, select, useDispatch, useSelect } from '@wordpress/data';
import { uploadMedia } from '@wordpress/media-utils';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useLayoutTemplate } from '@woocommerce/block-templates';
import { store as keyboardShortcutsStore } from '@wordpress/keyboard-shortcuts';
import { Product } from '@woocommerce/data';
import { getPath, getQuery } from '@woocommerce/navigation';
import { getAdminLink, getSetting } from '@woocommerce/settings';
import {
	BlockContextProvider,
	BlockEditorKeyboardShortcuts,
	BlockEditorProvider,
	BlockList,
	// @ts-expect-error BlockTools is not exported from @wordpress/block-editor's public types.
	BlockTools,
	ObserveTyping,
} from '@wordpress/block-editor';
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityBlockEditor, useEntityRecord } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useConfirmUnsavedProductChanges } from '../../hooks/use-confirm-unsaved-product-changes';
import { useProductTemplate } from '../../hooks/use-product-template';
import { PostTypeContext } from '../../contexts/post-type-context';
import { wooProductEditorUiStore } from '../../store/product-editor-ui';
import { ProductEditorSettings } from '../editor';
import { Notice } from '../notice';
import { BlockEditorProps } from './types';
import { LoadingState } from './loading-state';
import type { ProductTemplate } from '../../types';

const PRODUCT_BLOCK_EDITOR_FEATURE_OPTION =
	'woocommerce_feature_product_block_editor_enabled';

const PluginArea = lazy( () =>
	import( '@wordpress/plugins' ).then( ( module ) => ( {
		default: module.PluginArea,
	} ) )
);

const ModalEditor = lazy( () =>
	import( '../modal-editor' ).then( ( module ) => ( {
		default: module.ModalEditor,
	} ) )
);

function getLayoutTemplateId(
	productTemplate: ProductTemplate | undefined | null,
	postType: string
) {
	if ( productTemplate?.layoutTemplateId ) {
		return productTemplate.layoutTemplateId;
	}

	if ( postType === 'product_variation' ) {
		return 'product-variation';
	}

	// Fallback to simple product if no layout template is set.
	return 'simple-product';
}

function getClassicProductEditorUrl(
	productId: number,
	postType: string,
	product: Partial< Product > | undefined
) {
	const parentProductId =
		postType === 'product_variation' &&
		product &&
		'parent_id' in product &&
		typeof product.parent_id === 'number'
			? product.parent_id
			: productId;

	if ( parentProductId > 0 ) {
		return getAdminLink( `post.php?post=${ parentProductId }&action=edit` );
	}

	return getAdminLink( 'post-new.php?post_type=product' );
}

export function BlockEditor( {
	context,
	postType,
	productId,
	setIsEditorLoading,
}: BlockEditorProps ) {
	useConfirmUnsavedProductChanges( postType );

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

	useEffect( () => {
		// @ts-expect-error @wordpress/keyboard-shortcuts store is not fully typed.
		const { registerShortcut } = dispatch( keyboardShortcutsStore );
		if ( registerShortcut ) {
			registerShortcut( {
				name: 'core/editor/save',
				category: 'global',
				description: __( 'Save your changes.', 'woocommerce' ),
				keyCombination: {
					modifier: 'primary',
					character: 's',
				},
			} );
		}
	}, [] );

	const [ settingsGlobal, setSettingsGlobal ] = useState<
		Partial< ProductEditorSettings > | undefined
	>( undefined );

	useEffect( () => {
		let timeoutId: number;

		const checkSettingsGlobal = () => {
			if ( window.productBlockEditorSettings !== undefined ) {
				setSettingsGlobal( window.productBlockEditorSettings );
			} else {
				timeoutId = setTimeout( checkSettingsGlobal, 100 );
			}
		};

		checkSettingsGlobal();

		return () => {
			clearTimeout( timeoutId );
		};
	}, [] );

	const settings = useMemo<
		Partial< ProductEditorSettings > | undefined
	>( () => {
		if ( settingsGlobal === undefined ) {
			return undefined;
		}

		const canUserCreateMedia =
			select( 'core' ).canUser( 'create', 'media', '' ) !== false;

		const mediaSettings = canUserCreateMedia
			? {
					mediaUpload( {
						onError,
						...rest
					}: {
						onError: ( message: string ) => void;
					} ) {
						// @ts-expect-error uploadMedia's upstream UploadMediaArgs type rejects undefined for wpAllowedMimeTypes, but the runtime accepts it.
						uploadMedia( {
							wpAllowedMimeTypes:
								settingsGlobal.allowedMimeTypes || undefined,
							onError: ( { message } ) => onError( message ),
							...rest,
						} );
					},
			  }
			: {};

		return {
			...settingsGlobal,
			...mediaSettings,
			templateLock: 'all',
		};
	}, [ settingsGlobal ] );

	const { editedRecord: product, hasResolved } = useEntityRecord< Product >(
		'postType',
		postType,
		productId,
		// Only perform the query when the productId is valid.
		{ enabled: productId !== -1 }
	);

	const { _feature_nonce: featureNonce = '' } = getSetting< {
		_feature_nonce?: string;
	} >( 'admin', {} );
	const classicProductEditorUrl = getClassicProductEditorUrl(
		productId,
		postType,
		product
	);
	const disableProductBlockEditorUrl = new URL( classicProductEditorUrl );
	disableProductBlockEditorUrl.searchParams.set(
		'product_block_editor',
		'0'
	);
	disableProductBlockEditorUrl.searchParams.set(
		'_feature_nonce',
		featureNonce
	);
	const [ isDisablingProductBlockEditor, setIsDisablingProductBlockEditor ] =
		useState( false );

	const disableProductBlockEditor = async (
		event: React.MouseEvent< HTMLAnchorElement >
	) => {
		event.preventDefault();

		if ( isDisablingProductBlockEditor ) {
			return;
		}

		setIsDisablingProductBlockEditor( true );

		try {
			await apiFetch( {
				path: `/wc/v3/settings/advanced/${ PRODUCT_BLOCK_EDITOR_FEATURE_OPTION }`,
				method: 'POST',
				data: {
					value: 'no',
				},
			} );

			window.location.href = classicProductEditorUrl;
		} catch {
			window.location.href = disableProductBlockEditorUrl.toString();
		}
	};

	const productTemplateId = useMemo(
		() =>
			product?.meta_data?.find(
				( metaEntry: { key: string } ) =>
					metaEntry.key === '_product_template_id'
			)?.value,
		[ product?.meta_data ]
	);

	const { productTemplate } = useProductTemplate(
		productTemplateId,
		hasResolved ? product : null
	);

	const { layoutTemplate } = useLayoutTemplate(
		hasResolved ? getLayoutTemplateId( productTemplate, postType ) : null
	);

	type BlockChangeHandler = (
		blocks: BlockInstance[],
		options?: Record< string, unknown >
	) => void;
	const [ blocks, onInput, onChange ] = useEntityBlockEditor(
		'postType',
		postType,
		// useEntityBlockEditor will not try to fetch the product if productId is falsy.
		// @ts-expect-error useEntityBlockEditor's upstream types declare id as string, but the REST API uses number.
		{ id: productId !== -1 ? productId : 0 }
	) as [ BlockInstance[], BlockChangeHandler, BlockChangeHandler ];

	const isEditorLoading =
		! settings ||
		! layoutTemplate ||
		// variations don't have a product template
		( postType !== 'product_variation' && ! productTemplate ) ||
		productId === -1 ||
		! hasResolved;

	useLayoutEffect(
		function setupEditor() {
			if ( isEditorLoading ) {
				return;
			}

			const blockInstances = synchronizeBlocksWithTemplate(
				[],
				// @ts-expect-error layoutTemplate is not typed - it's a custom entity from useEntityRecord
				layoutTemplate.blockTemplates
			);

			onChange( blockInstances, {} );

			dispatch( 'core/editor' ).updateEditorSettings( {
				...settings,
				productTemplate,
			} as Partial< ProductEditorSettings > );

			// We don't need to include onChange in the dependencies, since we get new
			// instances of it on every render, which would cause an infinite loop.
			// eslint-disable-next-line react-hooks/exhaustive-deps
		},
		[
			isEditorLoading,
			layoutTemplate,
			settings,
			productTemplate,
			productId,
		]
	);

	useEffect( () => {
		setIsEditorLoading( isEditorLoading );
	}, [ isEditorLoading, setIsEditorLoading ] );

	const { editEntityRecord } = useDispatch( 'core' );

	useEffect( function maybeSetProductTemplateFromURL() {
		const query: { template?: string } = getQuery();
		const isAddProduct = getPath().endsWith( 'add-product' );
		if ( isAddProduct && query.template ) {
			const productTemplates =
				window.productBlockEditorSettings?.productTemplates ?? [];
			const selectedProductTemplate = productTemplates.find(
				( t ) => t.id === query.template
			);
			if ( selectedProductTemplate ) {
				editEntityRecord( 'postType', postType, productId, {
					...selectedProductTemplate.productData,
					meta_data: [
						...( selectedProductTemplate.productData.meta_data ??
							[] ),
						{
							key: '_product_template_id',
							value: selectedProductTemplate.id,
						},
					],
				} );
			}
		}
	}, [] );

	// Check if the Modal editor is open from the store.
	const isModalEditorOpen = useSelect( ( selectCore ) => {
		return selectCore( wooProductEditorUiStore ).isModalEditorOpen();
	}, [] );

	if ( isEditorLoading ) {
		return (
			<div className="woocommerce-product-block-editor">
				<LoadingState />
			</div>
		);
	}

	if ( isModalEditorOpen ) {
		return (
			<Suspense fallback={ null }>
				<ModalEditor
					onClose={
						dispatch( wooProductEditorUiStore ).closeModalEditor
					}
					title={ __( 'Edit description', 'woocommerce' ) }
					name={
						! product?.name || product.name === 'AUTO-DRAFT'
							? __( '(no product name)', 'woocommerce' )
							: product.name
					}
				/>
			</Suspense>
		);
	}

	return (
		<div className="woocommerce-product-block-editor">
			<Notice
				className="woocommerce-product-block-editor__deprecation-notice"
				type="warning"
				title={ __(
					'Switch to the classic editor before WooCommerce 11.0',
					'woocommerce'
				) }
				content={ __(
					"We're removing this version of the product editor in WooCommerce 11.0. The classic editor has the same features and your products won't change, so we recommend switching now.",
					'woocommerce'
				) }
			>
				<Button
					className="woocommerce-product-block-editor__deprecation-notice-action"
					href={ disableProductBlockEditorUrl.toString() }
					isBusy={ isDisablingProductBlockEditor }
					onClick={ disableProductBlockEditor }
					variant="secondary"
				>
					{ __( 'Switch to classic editor', 'woocommerce' ) }
				</Button>
			</Notice>
			<BlockContextProvider value={ context }>
				<BlockEditorProvider
					value={ blocks }
					onInput={ onInput }
					onChange={ onChange }
					settings={ settings }
					useSubRegistry={ false }
				>
					{ /* @ts-expect-error BlockEditorKeyboardShortcuts.Register is not exposed on the component's public types. */ }
					<BlockEditorKeyboardShortcuts.Register />
					<BlockTools>
						<ObserveTyping>
							<BlockList className="woocommerce-product-block-editor__block-list" />
						</ObserveTyping>
					</BlockTools>
					{ /* eslint-disable-next-line @typescript-eslint/no-non-null-assertion */ }
					<PostTypeContext.Provider value={ context.postType! }>
						<Suspense fallback={ null }>
							<PluginArea scope="woocommerce-product-block-editor" />
						</Suspense>
					</PostTypeContext.Provider>
				</BlockEditorProvider>
			</BlockContextProvider>
		</div>
	);
}
