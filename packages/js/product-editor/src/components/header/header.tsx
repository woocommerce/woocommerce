/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { WooHeaderItem, useAdminSidebarWidth } from '@woocommerce/admin-layout';
import { useEntityId, useEntityRecord } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
	createElement,
	useContext,
	useEffect,
	Fragment,
	lazy,
	Suspense,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Tooltip } from '@wordpress/components';
import { box, chevronLeft, group, Icon } from '@wordpress/icons';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import classNames from 'classnames';
import { Tag } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { PinnedItems } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { EditorLoadingContext } from '../../contexts/editor-loading-context';
import { getHeaderTitle } from '../../utils';
import { MoreMenu } from './more-menu';
import { PreviewButton } from './preview-button';
import { SaveDraftButton } from './save-draft-button';
import { LoadingState } from './loading-state';
import { Tabs } from '../tabs';
import { HEADER_PINNED_ITEMS_SCOPE, TRACKS_SOURCE } from '../../constants';
import { useShowPrepublishChecks } from '../../hooks/use-show-prepublish-checks';
import { HeaderProps, Image } from './types';
import { ParsedContext, StringSchema, validator, ValidationError, ObjectSchema } from '@woocommerce/validation';

const PublishButton = lazy( () =>
	import( './publish-button' ).then( ( module ) => ( {
		default: module.PublishButton,
	} ) )
);

const RETURN_TO_MAIN_PRODUCT = __(
	'Return to the main product',
	'woocommerce'
);

export function Header( {
	onTabSelect,
	productType = 'product',
	selectedTab,
}: HeaderProps ) {
	const isEditorLoading = useContext( EditorLoadingContext );

	const productId = useEntityId( 'postType', productType );
	const [ schema, setSchema ] = useState( null );

	const { editedRecord: product } = useEntityRecord< Product >(
		'postType',
		productType,
		productId,
		{ enabled: productId !== -1 }
	);

	const lastPersistedProduct = useSelect< Product | null >(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		( select ) => {
			const { getEntityRecord } = select( 'core' );
			return productId !== -1
				? getEntityRecord( 'postType', productType, productId )
				: null;
		},
		[ productType, productId ]
	);

	const editedProductName = product?.name;
	const catalogVisibility = product?.catalog_visibility;
	const productStatus = product?.status;

	const { showPrepublishChecks } = useShowPrepublishChecks();

	const sidebarWidth = useAdminSidebarWidth();

	const myValidator = validator();

	myValidator.addFilter( ( context: ParsedContext< StringSchema, string > ) => {
		if ( context.schema.type !== 'string' || context.path !== '/name' || context.parsed !== 'bad' ) {
			return [];
		}
		const { path } = context;
		return [
			{
				code: 'MY_ERROR_CODE',
				keyword: 'custom_string_error',
				message: 'Name cannot be "bad"',
				path,
			}
		] as ValidationError[];
	} );

	useEffect( () => {
		apiFetch( {
			path: '/wc/v3/products/',
			method: 'OPTIONS',
		} ).then( ( results ) => {
			// @ts-ignore
			setSchema( results.schema );
		} );
	}, [] );

	useEffect( () => {
		if ( schema && product ) {
			const result = myValidator.parse( schema as ObjectSchema, product );
			console.log(result);
		}
	}, [schema, product] );

	useEffect( () => {
		document
			.querySelectorAll( '.interface-interface-skeleton__header' )
			.forEach( ( el ) => {
				if ( ( el as HTMLElement ).style ) {
					( el as HTMLElement ).style.width =
						'calc(100% - ' + sidebarWidth + 'px)';
					( el as HTMLElement ).style.left = sidebarWidth + 'px';
				}
			} );
	}, [ sidebarWidth ] );

	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	const isVariation = lastPersistedProduct?.parent_id > 0;

	const selectedImage = isVariation ? product?.image : product?.images;

	if ( isEditorLoading ) {
		return <LoadingState />;
	}

	const isHeaderImageVisible =
		( ! isVariation &&
			Array.isArray( selectedImage ) &&
			selectedImage.length > 0 ) ||
		( isVariation && selectedImage );

	function getImagePropertyValue(
		image: Image | Image[],
		prop: 'alt' | 'src'
	): string {
		if ( Array.isArray( image ) ) {
			return image[ 0 ][ prop ] || '';
		}
		return image[ prop ] || '';
	}

	function getVisibilityTags() {
		const tags = [];
		if ( productStatus === 'draft' ) {
			tags.push(
				<Tag
					key={ 'draft-tag' }
					label={ __( 'Draft', 'woocommerce' ) }
				/>
			);
		}
		if ( productStatus === 'future' ) {
			tags.push(
				<Tag
					key={ 'scheduled-tag' }
					label={ __( 'Scheduled', 'woocommerce' ) }
				/>
			);
		}
		if (
			( productStatus !== 'future' && catalogVisibility === 'hidden' ) ||
			( isVariation && productStatus === 'private' )
		) {
			tags.push(
				<Tag
					key={ 'hidden-tag' }
					label={ __( 'Hidden', 'woocommerce' ) }
				/>
			);
		}
		return tags;
	}

	return (
		<div
			className="woocommerce-product-header"
			role="region"
			aria-label={ __( 'Product Editor top bar.', 'woocommerce' ) }
			tabIndex={ -1 }
		>
			<div className="woocommerce-product-header__inner">
				{ isVariation ? (
					<div className="woocommerce-product-header__back">
						<Tooltip
							// @ts-expect-error className is missing in TS, should remove this when it is included.
							className="woocommerce-product-header__back-tooltip"
							text={ RETURN_TO_MAIN_PRODUCT }
						>
							<div className="woocommerce-product-header__back-tooltip-wrapper">
								<Button
									icon={ chevronLeft }
									isTertiary={ true }
									onClick={ () => {
										recordEvent(
											'product_variation_back_to_main_product',
											{
												source: TRACKS_SOURCE,
											}
										);
										const url = getNewPath(
											{ tab: 'variations' },
											`/product/${ lastPersistedProduct?.parent_id }`
										);
										navigateTo( { url } );
									} }
								>
									{ __( 'Main product', 'woocommerce' ) }
								</Button>
							</div>
						</Tooltip>
					</div>
				) : (
					<div />
				) }

				<div
					className={ classNames(
						'woocommerce-product-header-title-bar',
						{
							'is-variation': isVariation,
						}
					) }
				>
					<div className="woocommerce-product-header-title-bar__image">
						{ isHeaderImageVisible ? (
							<img
								alt={ getImagePropertyValue(
									selectedImage,
									'alt'
								) }
								src={ getImagePropertyValue(
									selectedImage,
									'src'
								) }
								className="woocommerce-product-header-title-bar__product-image"
							/>
						) : (
							<Icon icon={ isVariation ? group : box } />
						) }
					</div>
					<h1 className="woocommerce-product-header__title">
						{ isVariation ? (
							<>
								{ lastPersistedProduct?.name }
								<span className="woocommerce-product-header__variable-product-id">
									# { lastPersistedProduct?.id }
								</span>
							</>
						) : (
							getHeaderTitle(
								editedProductName,
								// eslint-disable-next-line @typescript-eslint/ban-ts-comment
								// @ts-ignore - Arg is not typed correctly.
								lastPersistedProduct?.name
							)
						) }
						<div className="woocommerce-product-header__visibility-tags">
							{ getVisibilityTags() }
						</div>
					</h1>
				</div>

				<div className="woocommerce-product-header__actions">
					{ ! isVariation && (
						<SaveDraftButton
							productType={ productType }
							visibleTab={ selectedTab }
							// eslint-disable-next-line @typescript-eslint/ban-ts-comment
							// @ts-ignore - Prop is not typed correctly.
							productStatus={ lastPersistedProduct?.status }
						/>
					) }

					<PreviewButton
						productType={ productType }
						visibleTab={ selectedTab }
						// eslint-disable-next-line @typescript-eslint/ban-ts-comment
						// @ts-ignore - Prop is not typed correctly.
						productStatus={ lastPersistedProduct?.status }
					/>

					<Suspense fallback={ null }>
						<PublishButton
							productType={ productType }
							isPrePublishPanelVisible={ showPrepublishChecks }
							isMenuButton
							visibleTab={ selectedTab }
						/>
					</Suspense>

					<WooHeaderItem.Slot name="product" />
					<PinnedItems.Slot scope={ HEADER_PINNED_ITEMS_SCOPE } />
					<MoreMenu />
				</div>
			</div>
			<Tabs selected={ selectedTab } onChange={ onTabSelect } />
		</div>
	);
}
