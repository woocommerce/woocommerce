/**
 * External dependencies
 */
import {
	createElement,
	useMemo,
	useLayoutEffect,
	useCallback,
	useEffect,
	useState,
} from '@wordpress/element';
import { useDispatch, useSelect, select as WPSelect } from '@wordpress/data';
import { uploadMedia } from '@wordpress/media-utils';
import { __ } from '@wordpress/i18n';
import { useLayoutTemplate } from '@woocommerce/block-templates';
import { store as keyboardShortcutsStore } from '@wordpress/keyboard-shortcuts';
import { Product } from '@woocommerce/data';
// It doesn't seem to notice the External dependency block whn @ts-ignore is added.
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore store should be included.
	useEntityBlockEditor,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore store should be included.
	useEntityRecord,
} from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useConfirmUnsavedProductChanges } from '../../hooks/use-confirm-unsaved-product-changes';
import { useProductTemplate } from '../../hooks/use-product-template';
import { store as productEditorUiStore } from '../../store/product-editor-ui';
import { ModalEditor } from '../modal-editor';
import { ProductEditorSettings } from '../editor';
import { BlockEditorProps } from './types';
import { ProductTemplate } from '../../types';
import { getRenderedBlockView } from '../../utils/get-rendered-block-view';

function getLayoutTemplateId(
	productTemplate: ProductTemplate | undefined,
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

export function BlockEditor( {
	context,
	postType,
	productId,
	setIsEditorLoading,
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

	// @ts-expect-error Type definitions are missing
	const { registerShortcut } = useDispatch( keyboardShortcutsStore );

	useEffect( () => {
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
	}, [ registerShortcut ] );

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
	}, [ settingsGlobal, canUserCreateMedia ] );

	const { editedRecord: product } = useEntityRecord< Product >(
		'postType',
		postType,
		productId,
		// Only perform the query when the productId is valid.
		{ enabled: productId !== -1 }
	);

	const productTemplateId = product?.meta_data?.find(
		( metaEntry: { key: string } ) =>
			metaEntry.key === '_product_template_id'
	)?.value;

	const { productTemplate } = useProductTemplate(
		productTemplateId,
		product
	);

	const { layoutTemplate } = useLayoutTemplate(
		getLayoutTemplateId( productTemplate, postType )
	);

	const { updateEditorSettings } = useDispatch( 'core/editor' );

	const isEditorLoading =
		! settings ||
		! layoutTemplate ||
		// variations don't have a product template
		( postType !== 'product_variation' && ! productTemplate ) ||
		productId === -1;

	useLayoutEffect( () => {
		if ( isEditorLoading ) {
			return;
		}

		updateEditorSettings( {
			...settings,
			productTemplate,
		} as Partial< ProductEditorSettings > );

		setIsEditorLoading( isEditorLoading );

		// We don't need to include onChange or updateEditorSettings in the dependencies,
		// since we get new instances of them on every render, which would cause an infinite loop.
		//
		// We include productId in the dependencies to make sure that the effect is run when the
		// product is changed, since we need to synchronize the blocks with the template and update
		// the blocks by calling onChange.
		//
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ layoutTemplate, settings, productTemplate, productId ] );

	// Check if the Modal editor is open from the store.
	const isModalEditorOpen = useSelect( ( select ) => {
		return select( productEditorUiStore ).isModalEditorOpen();
	}, [] );

	const { closeModalEditor } = useDispatch( productEditorUiStore );

	const content =
		'<div data-block-name="woocommerce/product-tab" data-id="general" data-title="General">' +
		'<div data-block-name="woocommerce/product-section" data-id="basic-details" data-title="Basic Details" data-description="">' +
		'<div data-block-name="woocommerce/product-name-field"></div>' +
		'<div data-block-name="woocommerce/product-regular-price-field" data-label="Regular price"></div>' +
		'<div data-block-name="woocommerce/product-sale-price-field" data-label="Sale price"></div>' +
		'<div data-block-name="woocommerce/product-schedule-sale-fields" data-label="Sale price"></div>' +
		'<div data-block-name="woocommerce/product-radio-field" data-title="Charge sales tax on" data-property="stock_status"></div>' +
		'<div data-block-name="woocommerce/product-summary-field" data-property="description"></div>' +
		'<div data-block-name="woocommerce/product-description-field" data-property="description"></div>' +
		'<div data-block-name="woocommerce/product-images-field" data-property="description"></div>' +
		'<div data-block-name="woocommerce/product-downloads-field" data-property="description"></div>' +
		'</div>' +
		'</div>' +
		'<div data-block-name="woocommerce/product-tab" data-id="pricing" data-title="Pricing">' +
		'<div data-block-name="woocommerce/product-section" data-id="basic-details" data-title="Basic Details" data-description="">' +
		'<div data-block-name="woocommerce/product-name-field"></div>' +
		'<div data-block-name="woocommerce/product-regular-price-field" data-label="Regular price"></div>' +
		'<div data-block-name="woocommerce/product-sale-price-field" data-label="Sale price"></div>' +
		'<div data-block-name="woocommerce/product-schedule-sale-fields" data-label="Sale price"></div>' +
		'<div data-block-name="woocommerce/product-radio-field" data-title="Charge sales tax on" data-property="stock_status"></div>' +
		'<div data-block-name="woocommerce/product-summary-field" data-property="description"></div>' +
		'</div>' +
		'</div>' +
		'<div data-block-name="woocommerce/product-tab" data-id="third" data-title="Third">' +
		'<div data-block-name="woocommerce/product-section" data-id="basic-details" data-title="Basic Details" data-description="">' +
		'<div data-block-name="woocommerce/product-name-field"></div>' +
		'<div data-block-name="woocommerce/product-regular-price-field" data-label="Regular price"></div>' +
		'<div data-block-name="woocommerce/product-sale-price-field" data-label="Sale price"></div>' +
		'<div data-block-name="woocommerce/product-schedule-sale-fields" data-label="Sale price"></div>' +
		'<div data-block-name="woocommerce/product-radio-field" data-title="Charge sales tax on" data-property="stock_status"></div>' +
		'<div data-block-name="woocommerce/product-summary-field" data-property="description"></div>' +
		'</div>' +
		'</div>';

	const ProductForm = useCallback( () => {
		const container = document.createElement( 'div' );
		container.innerHTML = content;
		return (
			<div className="woocommerce-product-block-editor__product-form">
				{ getRenderedBlockView( container, context ) }
			</div>
		);
	}, [ content, context ] );

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
			<ProductForm />
		</div>
	);
}
