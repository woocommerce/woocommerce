/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useContext, cloneElement } from '@wordpress/element';
import {
	RangeControl,
	ToggleControl,
	DropZone,
	Button,
	Spinner,
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useViewportMatch } from '@wordpress/compose';
import { Icon, upload, moreVertical } from '@wordpress/icons';
import { store as coreStore } from '@wordpress/core-data';
import { isBlobURL } from '@wordpress/blob';
import {
	MediaUpload,
	MediaUploadCheck,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { store as noticesStore } from '@wordpress/notices';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { LogoBlockContext } from '../logo-block-context';
import {
	useLogoAttributes,
	LogoAttributes,
} from '../hooks/use-logo-attributes';
import {
	MIN_LOGO_SIZE,
	DEFAULT_LOGO_WIDTH,
	MAX_LOGO_WIDTH,
	ALLOWED_MEDIA_TYPES,
} from './constants';
import { trackEvent } from '~/customize-store/tracking';

type Media = {
	id: string | number;
} & { [ key: string ]: string };

const useLogoEdit = ( {
	shouldSyncIcon,
	setAttributes,
}: {
	shouldSyncIcon: LogoAttributes[ 'shouldSyncIcon' ];
	setAttributes: ( newAttributes: LogoAttributes ) => void;
} ) => {
	const { siteIconId, mediaUpload } = useSelect( ( select ) => {
		const { canUser, getEditedEntityRecord } = select( coreStore );
		const _canUserEdit = canUser( 'update', 'settings' );
		const siteSettings = _canUserEdit
			? // @ts-expect-error No support for root and site
			  ( getEditedEntityRecord( 'root', 'site' ) as {
					site_icon: string | undefined;
			  } )
			: undefined;

		const _siteIconId = siteSettings?.site_icon;
		return {
			siteIconId: _siteIconId,
			mediaUpload:
				// @ts-expect-error Selector is not typed
				select( blockEditorStore ).getSettings().mediaUpload,
		};
	}, [] );

	const { editEntityRecord } = useDispatch( coreStore );

	const setIcon = ( newValue: string | undefined | null | number ) =>
		// The new value needs to be `null` to reset the Site Icon.
		// @ts-expect-error No support for root and site
		editEntityRecord( 'root', 'site', undefined, {
			site_icon: newValue ?? null,
		} );

	const setLogo = (
		newValue: string | undefined | null | number,
		shouldForceSync = false
	) => {
		// `shouldForceSync` is used to force syncing when the attribute
		// may not have updated yet.
		if ( shouldSyncIcon || shouldForceSync ) {
			setIcon( newValue );
		}

		// @ts-expect-error No support for root and site
		editEntityRecord( 'root', 'site', undefined, {
			site_logo: newValue,
		} );
	};

	const onSelectLogo = ( media: Media, shouldForceSync = false ) => {
		if ( ! media ) {
			return;
		}

		if ( ! media.id && media.url ) {
			// This is a temporary blob image.
			setLogo( undefined );
			return;
		}

		setLogo( media.id, shouldForceSync );
		setAttributes( { width: DEFAULT_LOGO_WIDTH } );
	};

	const onInitialSelectLogo = ( media: Media ) => {
		// Initialize the syncSiteIcon toggle. If we currently have no site logo and no
		// site icon, automatically sync the logo to the icon.
		if ( shouldSyncIcon === undefined ) {
			const shouldForceSync = ! siteIconId;
			setAttributes( { shouldSyncIcon: shouldForceSync } );

			// Because we cannot rely on the `shouldSyncIcon` attribute to have updated by
			// the time `setLogo` is called, pass an argument to force the syncing.
			onSelectLogo( media, shouldForceSync );
			return;
		}

		onSelectLogo( media );
	};

	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore The types for this are incorrect.
	const { createErrorNotice } = useDispatch( noticesStore );
	const onUploadError = ( message: string ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore The types for this are incorrect.
		createErrorNotice( message, { type: 'snackbar' } );
	};

	const onFilesDrop = ( filesList: File[] ) => {
		mediaUpload( {
			allowedTypes: [ 'image' ],
			filesList,
			onFileChange( [ image ]: Media[] ) {
				if ( isBlobURL( image?.url ) ) {
					return;
				}
				onInitialSelectLogo( image );
			},
			onError: onUploadError,
		} );
	};

	const onRemoveLogo = () => {
		setLogo( null );
		setAttributes( { width: undefined } );
	};

	return {
		onFilesDrop,
		onInitialSelectLogo,
		setIcon,
		siteIconId,
		onRemoveLogo,
	};
};

// Reference: https://github.com/WordPress/gutenberg/blob/83f3fbc740c97afac3474a6c37098e259191dc2c/packages/block-library/src/site-logo/edit.js#L63
const LogoSettings = ( {
	attributes: { width, isLink, shouldSyncIcon, align = '' },
	canUserEdit,
	naturalWidth,
	naturalHeight,
	setAttributes,
	setIcon,
	logoId,
}: {
	attributes: LogoAttributes;
	setAttributes: ( newAttributes: LogoAttributes ) => void;
	canUserEdit: boolean;
	naturalWidth: number;
	naturalHeight: number;
	setIcon: ( newValue: string | undefined ) => void;
	logoId: string;
} ) => {
	const isLargeViewport = useViewportMatch( 'medium' );
	const isWideAligned = [ 'wide', 'full' ].includes( align );
	const isResizable = ! isWideAligned && isLargeViewport;

	const currentWidth = width || DEFAULT_LOGO_WIDTH;
	const ratio = naturalWidth / naturalHeight;
	const minWidth =
		naturalWidth < naturalHeight
			? MIN_LOGO_SIZE
			: Math.ceil( MIN_LOGO_SIZE * ratio );

	// With the current implementation of ResizableBox, an image needs an
	// explicit pixel value for the max-width. In absence of being able to
	// set the content-width, this max-width is currently dictated by the
	// vanilla editor style. The following variable adds a buffer to this
	// vanilla style, so 3rd party themes have some wiggleroom. This does,
	// in most cases, allow you to scale the image beyond the width of the
	// main column, though not infinitely.
	// @todo It would be good to revisit this once a content-width variable
	// becomes available.
	const maxWidthBuffer = MAX_LOGO_WIDTH * 2.5;

	return (
		<div className="woocommerce-customize-store__sidebar-group">
			<div className="woocommerce-customize-store__sidebar-group-header">
				{ __( 'Settings', 'woocommerce' ) }
			</div>
			<RangeControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Image width', 'woocommerce' ) }
				onChange={ ( newWidth ) =>
					setAttributes( { width: newWidth } )
				}
				min={ minWidth }
				max={ MAX_LOGO_WIDTH }
				initialPosition={ Math.min(
					DEFAULT_LOGO_WIDTH,
					maxWidthBuffer
				) }
				value={ currentWidth }
				disabled={ ! isResizable }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Link logo to homepage', 'woocommerce' ) }
				onChange={ () => {
					setAttributes( { isLink: ! isLink } );
				} }
				checked={ isLink }
			/>
			{ canUserEdit && (
				<>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Use as site icon', 'woocommerce' ) }
						onChange={ ( value: boolean ) => {
							setAttributes( { shouldSyncIcon: value } );
							setIcon( value ? logoId : undefined );
						} }
						checked={ !! shouldSyncIcon }
						help={ __(
							'Site icons are what you see in browser tabs, bookmark bars, and within the WordPress mobile apps.',
							'woocommerce'
						) }
					/>
				</>
			) }
		</div>
	);
};

const LogoEdit = ( {
	siteLogoId,
	attributes,
	setAttributes,
	mediaItemData,
	isLoading,
	canUserEdit,
}: {
	siteLogoId: string;
	setAttributes: ( newAttributes: LogoAttributes ) => void;
	attributes: LogoAttributes;
	mediaItemData: { id: string; alt_text: string; source_url: string };
	isLoading: boolean;
	canUserEdit: boolean;
} ) => {
	const { alt_text: alt, source_url: logoUrl } = mediaItemData || {};

	const { onFilesDrop, onInitialSelectLogo, setIcon } = useLogoEdit( {
		shouldSyncIcon: attributes.shouldSyncIcon,
		setAttributes,
	} );

	const [ { naturalWidth, naturalHeight }, setNaturalSize ] = useState< {
		naturalWidth?: number;
		naturalHeight?: number;
	} >( {} );

	if ( isLoading ) {
		return (
			<span className="components-placeholder__preview">
				<Spinner />
			</span>
		);
	}

	function handleMediaUploadSelect( media: Media ) {
		onInitialSelectLogo( media );
		trackEvent( 'customize_your_store_assembler_hub_logo_select' );
	}

	if ( ! logoUrl ) {
		return (
			<MediaUploadCheck>
				<MediaUpload
					onSelect={ handleMediaUploadSelect }
					allowedTypes={ ALLOWED_MEDIA_TYPES }
					render={ ( { open }: { open: () => void } ) => (
						<Button
							variant="link"
							onClick={ () => {
								open();
								trackEvent(
									'customize_your_store_assembler_hub_logo_add_click'
								);
							} }
							className="block-library-site-logo__inspector-upload-container"
						>
							<span>
								<Icon
									icon={ upload }
									size={ 20 }
									className="icon-control"
								/>
							</span>
							<DropZone onFilesDrop={ onFilesDrop } />
						</Button>
					) }
				/>
			</MediaUploadCheck>
		);
	}

	const logoImg = (
		<div className="woocommerce-customize-store__sidebar-logo-container">
			<img
				className="woocommerce-customize-store_custom-logo"
				src={ logoUrl }
				alt={ alt }
				onLoad={ ( event ) => {
					setNaturalSize( {
						naturalWidth: ( event.target as HTMLImageElement )
							.naturalWidth,
						naturalHeight: ( event.target as HTMLImageElement )
							.naturalHeight,
					} );
				} }
			/>
		</div>
	);

	if ( ! naturalHeight || ! naturalWidth ) {
		// Load the image first to get the natural size so we can set the ratio.
		return logoImg;
	}

	return (
		<>
			<MediaUploadCheck>
				<MediaUpload
					onSelect={ handleMediaUploadSelect }
					allowedTypes={ ALLOWED_MEDIA_TYPES }
					render={ ( { open }: { open: () => void } ) =>
						cloneElement( logoImg, {
							onClick() {
								open();
								trackEvent(
									'customize_your_store_assembler_hub_logo_edit_click'
								);
							},
						} )
					}
				/>
			</MediaUploadCheck>
			{ !! logoUrl && (
				<LogoSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
					naturalWidth={ naturalWidth }
					naturalHeight={ naturalHeight }
					canUserEdit={ canUserEdit }
					setIcon={ setIcon }
					logoId={ mediaItemData?.id || siteLogoId }
				/>
			) }
		</>
	);
};

export const SidebarNavigationScreenLogo = ( {
	onNavigateBackClick,
}: {
	onNavigateBackClick: () => void;
} ) => {
	// Get the current logo block client ID and attributes. These are used for the logo settings.
	const { logoBlockIds } = useContext( LogoBlockContext );
	const { attributes, isAttributesLoading } = useLogoAttributes();

	const { siteLogoId, canUserEdit, mediaItemData, isRequestingMediaItem } =
		useSelect( ( select ) => {
			const { canUser, getEntityRecord, getEditedEntityRecord } =
				select( coreStore );

			const _canUserEdit = canUser( 'update', 'settings' );
			const siteSettings = _canUserEdit
				? // @ts-expect-error No support for root and site
				  ( getEditedEntityRecord( 'root', 'site' ) as {
						site_logo: string;
				  } )
				: undefined;
			// @ts-expect-error No support for root and site
			const siteData = getEntityRecord( 'root', '__unstableBase' ) as {
				site_logo: string;
			};
			const _siteLogoId = _canUserEdit
				? siteSettings?.site_logo
				: siteData?.site_logo;

			const mediaItem =
				_siteLogoId &&
				// @ts-expect-error No getMedia selector type definition
				select( coreStore ).getMedia( _siteLogoId, {
					context: 'view',
				} );
			const _isRequestingMediaItem =
				_siteLogoId &&
				// @ts-expect-error No hasFinishedResolution selector type definition
				! select( coreStore ).hasFinishedResolution( 'getMedia', [
					_siteLogoId,
					{ context: 'view' },
				] );

			return {
				siteLogoId: _siteLogoId ?? '',
				canUserEdit: _canUserEdit ?? false,
				mediaItemData: mediaItem,
				isRequestingMediaItem: _isRequestingMediaItem,
			};
		}, [] );

	const { updateBlockAttributes } = useDispatch( blockEditorStore );
	const setAttributes = ( newAttributes: LogoAttributes ) => {
		if ( ! logoBlockIds.length ) {
			return;
		}
		logoBlockIds.forEach( ( clientId ) =>
			updateBlockAttributes( clientId, newAttributes )
		);
	};

	const { onInitialSelectLogo, onRemoveLogo } = useLogoEdit( {
		shouldSyncIcon: attributes.shouldSyncIcon,
		setAttributes,
	} );

	const isLoading =
		siteLogoId === undefined ||
		isRequestingMediaItem ||
		isAttributesLoading;

	return (
		<SidebarNavigationScreen
			title={ __( 'Add your logo', 'woocommerce' ) }
			description={ __(
				"Ensure your store is on-brand by adding your logo. For best results, upload a SVG or PNG that's a minimum of 300px wide.",
				'woocommerce'
			) }
			onNavigateBackClick={ onNavigateBackClick }
			content={
				<div className="woocommerce-customize-store__sidebar-logo-content">
					<div className="woocommerce-customize-store__sidebar-group-header woocommerce-customize-store__logo-header-container">
						<span>{ __( 'Logo', 'woocommerce' ) }</span>
						{ Boolean( siteLogoId ) && (
							<DropdownMenu
								icon={ moreVertical }
								label={ __( 'Options', 'woocommerce' ) }
								className="woocommerce-customize-store__logo-dropdown-menu"
								popoverProps={ {
									className:
										'woocommerce-customize-store__logo-dropdown-popover',
									placement: 'bottom-end',
								} }
							>
								{ ( { onClose } ) => (
									<>
										<MenuGroup className="woocommerce-customize-store__logo-menu-group">
											<MediaUploadCheck>
												<MediaUpload
													onSelect={ (
														media: Parameters<
															typeof onInitialSelectLogo
														>[ 0 ]
													) => {
														onInitialSelectLogo(
															media
														);
														onClose();
														trackEvent(
															'customize_your_store_assembler_hub_logo_select'
														);
													} }
													allowedTypes={
														ALLOWED_MEDIA_TYPES
													}
													render={ ( {
														open,
													}: {
														open: () => void;
													} ) => (
														<MenuItem
															onClick={ () => {
																open();
																trackEvent(
																	'customize_your_store_assembler_hub_logo_replace_click'
																);
															} }
														>
															{ __(
																'Replace',
																'woocommerce'
															) }
														</MenuItem>
													) }
												/>
											</MediaUploadCheck>
										</MenuGroup>

										<MenuGroup className="woocommerce-customize-store__logo-menu-group">
											<MenuItem
												className="woocommerce-customize-store__logo-menu-item-delete"
												onClick={ () => {
													onClose();
													onRemoveLogo();
													trackEvent(
														'customize_your_store_assembler_hub_logo_remove_click'
													);
												} }
											>
												{ __(
													'Delete',
													'woocommerce'
												) }
											</MenuItem>
										</MenuGroup>
									</>
								) }
							</DropdownMenu>
						) }
					</div>
					<LogoEdit
						siteLogoId={ siteLogoId }
						attributes={ attributes }
						setAttributes={ setAttributes }
						canUserEdit={ canUserEdit }
						mediaItemData={ mediaItemData }
						isLoading={ isLoading }
					/>
					<div className="woocommerce-customize-store__fiverr-cta-group">
						<strong>
							{ __( "DON'T HAVE A LOGO YET?", 'woocommerce' ) }
						</strong>
						<p>
							{ interpolateComponents( {
								mixedString: __(
									'Build your brand by creating a memorable logo using {{link}}Fiverr{{/link}}.',
									'woocommerce'
								),
								components: {
									link: (
										<Link
											href="https://go.fiverr.com/visit/?bta=917527&brand=logomaker&landingPage=https%253A%252F%252Fwww.fiverr.com%252Flogo-maker%252Fwoo"
											target="_blank"
											type="external"
											rel="noreferrer"
											onClick={ () => {
												trackEvent(
													'customize_your_store_fiverr_logo_maker_cta_click'
												);
											} }
										/>
									),
								},
							} ) }
						</p>
					</div>
				</div>
			}
		/>
	);
};
