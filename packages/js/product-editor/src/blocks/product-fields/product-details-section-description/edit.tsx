/**
 * External dependencies
 */
import classNames from 'classnames';
import {
	Button,
	Dropdown,
	MenuGroup,
	MenuItem,
	Modal,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	createElement,
	createInterpolateElement,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import * as icons from '@wordpress/icons';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product } from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ProductEditorSettings } from '../../../components';
import { BlockFill } from '../../../components/block-slot-fill';
import { useValidations } from '../../../contexts/validation-context';
import { TRACKS_SOURCE } from '../../../constants';
import {
	WPError,
	getProductErrorMessage,
} from '../../../utils/get-product-error-message';
import type {
	ProductEditorBlockEditProps,
	ProductTemplate,
} from '../../../types';
import { ProductDetailsSectionDescriptionBlockAttributes } from './types';
import * as wooIcons from '../../../icons';

export function ProductDetailsSectionDescriptionBlockEdit( {
	attributes,
	clientId,
}: ProductEditorBlockEditProps< ProductDetailsSectionDescriptionBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	const { productTemplates, productTemplate: selectedProductTemplate } =
		useSelect( ( select ) => {
			const { getEditorSettings } = select( 'core/editor' );
			return getEditorSettings() as ProductEditorSettings;
		} );

	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const [ supportedProductTemplates, unsupportedProductTemplates ] =
		productTemplates.reduce< [ ProductTemplate[], ProductTemplate[] ] >(
			( [ supported, unsupported ], productTemplate ) => {
				if ( productTemplate.selectable ) {
					if ( productTemplate.layoutTemplateId ) {
						supported.push( productTemplate );
					} else {
						unsupported.push( productTemplate );
					}
				}
				return [ supported, unsupported ];
			},
			[ [], [] ]
		);

	const productId = useEntityId( 'postType', 'product' );

	const { validate } = useValidations< Product >();
	// @ts-expect-error There are no types for this.
	const { editEntityRecord, saveEditedEntityRecord, saveEntityRecord } =
		useDispatch( 'core' );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const rootClientId = useSelect(
		( select ) => {
			const { getBlockRootClientId } = select( 'core/block-editor' );
			return getBlockRootClientId( clientId );
		},
		[ clientId ]
	);

	const [ unsupportedProductTemplate, setUnsupportedProductTemplate ] =
		useState< ProductTemplate >();

	const { isSaving } = useSelect(
		( select ) => {
			// @ts-expect-error There are no types for this.
			const { isSavingEntityRecord } = select( 'core' );

			return {
				isSaving: isSavingEntityRecord< boolean >(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	if ( ! rootClientId ) return;

	function menuItemClickHandler(
		productTemplate: ProductTemplate,
		onClose: () => void
	) {
		return async function handleMenuItemClick() {
			try {
				recordEvent( 'product_template_selector_selected', {
					source: TRACKS_SOURCE,
					selected_template: productTemplate.id,
					unsupported_template: ! productTemplate.layoutTemplateId,
				} );

				if ( ! productTemplate.layoutTemplateId ) {
					setUnsupportedProductTemplate( productTemplate );
					onClose();
					return;
				}

				await validate( productTemplate.productData );

				const productMetaData =
					productTemplate.productData.meta_data ?? [];

				await editEntityRecord( 'postType', 'product', productId, {
					...productTemplate.productData,
					meta_data: [
						...productMetaData,
						{
							key: '_product_template_id',
							value: productTemplate.id,
						},
					],
				} );

				await saveEditedEntityRecord< Product >(
					'postType',
					'product',
					productId,
					{
						throwOnError: true,
					}
				);

				createSuccessNotice(
					__( 'Product type changed.', 'woocommerce' )
				);

				recordEvent( 'product_template_changed', {
					source: TRACKS_SOURCE,
					template: productTemplate.id,
				} );
			} catch ( error ) {
				const message = getProductErrorMessage( error as WPError );
				createErrorNotice( message );
			}

			onClose();
		};
	}

	function resolveIcon( iconId?: string | null, alt?: string ) {
		if ( ! iconId ) return undefined;

		const { Icon } = icons;
		let icon: JSX.Element;

		if ( /^https?:\/\//.test( iconId ) ) {
			icon = <img src={ iconId } alt={ alt } />;
		} else {
			if ( ! ( iconId in icons || iconId in wooIcons ) ) return undefined;

			icon = icons[ iconId as never ] || wooIcons[ iconId as never ];
		}

		return <Icon icon={ icon } size={ 24 } />;
	}

	function getMenuItem( onClose: () => void ) {
		return function renderMenuItem( productTemplate: ProductTemplate ) {
			const isSelected =
				selectedProductTemplate?.id === productTemplate.id;
			return (
				<MenuItem
					key={ productTemplate.id }
					info={ productTemplate.description ?? undefined }
					isSelected={ isSelected }
					icon={
						isSelected
							? resolveIcon( 'check' )
							: resolveIcon(
									productTemplate.icon,
									productTemplate.title
							  )
					}
					iconPosition="left"
					role="menuitemradio"
					onClick={ menuItemClickHandler( productTemplate, onClose ) }
					className={ classNames( {
						'components-menu-item__button--selected': isSelected,
					} ) }
				>
					{ productTemplate.title }
				</MenuItem>
			);
		};
	}

	async function handleModalChangeClick() {
		try {
			if ( isSaving ) return;

			const { id: productTemplateId, productData } =
				unsupportedProductTemplate as ProductTemplate;

			await validate( productData );

			const product = ( await saveEditedEntityRecord< Product >(
				'postType',
				'product',
				productId,
				{
					throwOnError: true,
				}
			) ) ?? { id: productId };

			const productMetaData = productData?.meta_data ?? [];

			// Avoiding to save some changes that are not supported by the current product template.
			// So in this case those changes are saved directly to the server.
			await saveEntityRecord(
				'postType',
				'product',
				{
					...product,
					...productData,
					meta_data: [
						...productMetaData,
						{
							key: '_product_template_id',
							value: productTemplateId,
						},
					],
				},
				// @ts-expect-error Expected 3 arguments, but got 4.
				{
					throwOnError: true,
				}
			);

			createSuccessNotice( __( 'Product type changed.', 'woocommerce' ) );

			recordEvent( 'product_template_changed', {
				source: TRACKS_SOURCE,
				template: productTemplateId,
			} );

			// Let the server manage the redirection when the product is not supported
			// by the product editor.
			window.location.href = getNewPath( {}, `/product/${ productId }` );
		} catch ( error ) {
			const message = getProductErrorMessage( error as WPError );
			createErrorNotice( message );
		}
	}

	function toogleButtonClickHandler( isOpen: boolean, onToggle: () => void ) {
		return function onClick() {
			onToggle();

			if ( ! isOpen ) {
				recordEvent( 'product_template_selector_open', {
					source: TRACKS_SOURCE,
					supported_templates: supportedProductTemplates.map(
						( productTemplate ) => productTemplate.id
					),
					unsupported_template: unsupportedProductTemplates.map(
						( productTemplate ) => productTemplate.id
					),
				} );
			}
		};
	}

	return (
		<BlockFill
			name="section-description"
			slotContainerBlockName="woocommerce/product-section"
		>
			<div { ...blockProps }>
				<p>
					{ createInterpolateElement(
						/* translators: <ProductTemplate />: the product template. */
						__( 'This is a <ProductTemplate />.', 'woocommerce' ),
						{
							ProductTemplate: (
								<span>
									{ selectedProductTemplate?.title?.toLowerCase() }
								</span>
							),
						}
					) }
				</p>

				<Dropdown
					// @ts-expect-error Property does exists
					focusOnMount={ true }
					popoverProps={ {
						placement: 'bottom-start',
					} }
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button
							aria-expanded={ isOpen }
							variant="link"
							onClick={ toogleButtonClickHandler(
								isOpen,
								onToggle
							) }
						>
							<span>
								{ __( 'Change product type', 'woocommerce' ) }
							</span>
						</Button>
					) }
					renderContent={ ( { onClose } ) => (
						<div className="wp-block-woocommerce-product-details-section-description__dropdown components-dropdown-menu__menu">
							<MenuGroup>
								{ supportedProductTemplates.map(
									getMenuItem( onClose )
								) }
							</MenuGroup>

							{ unsupportedProductTemplates.length > 0 && (
								<MenuGroup>
									<Dropdown
										// @ts-expect-error Property does exists
										popoverProps={ {
											placement: 'right-start',
										} }
										renderToggle={ ( {
											isOpen,
											onToggle,
										} ) => (
											<MenuItem
												aria-expanded={ isOpen }
												icon={ resolveIcon(
													'chevronRight'
												) }
												iconPosition="right"
												onClick={ onToggle }
											>
												<span>
													{ __(
														'More',
														'woocommerce'
													) }
												</span>
											</MenuItem>
										) }
										renderContent={ () => (
											<div className="wp-block-woocommerce-product-details-section-description__dropdown components-dropdown-menu__menu">
												<MenuGroup>
													{ unsupportedProductTemplates.map(
														getMenuItem( onClose )
													) }
												</MenuGroup>
											</div>
										) }
									/>
								</MenuGroup>
							) }
						</div>
					) }
				/>

				{ Boolean( unsupportedProductTemplate ) && (
					<Modal
						title={ __( 'Change product type?', 'woocommerce' ) }
						className="wp-block-woocommerce-product-details-section-description__modal"
						onRequestClose={ () => {
							setUnsupportedProductTemplate( undefined );
						} }
					>
						<p>
							<b>
								{ __(
									'This product type isn’t supported by the updated product editing experience yet.',
									'woocommerce'
								) }
							</b>
						</p>

						<p>
							{ __(
								'You’ll be taken to the classic editing screen that isn’t optimized for commerce but offers advanced functionality and supports all extensions.',
								'woocommerce'
							) }
						</p>

						<div className="wp-block-woocommerce-product-details-section-description__modal-actions">
							<Button
								variant="secondary"
								aria-disabled={ isSaving }
								onClick={ () => {
									if ( isSaving ) return;
									setUnsupportedProductTemplate( undefined );
								} }
							>
								{ __( 'Cancel', 'woocommerce' ) }
							</Button>

							<Button
								variant="primary"
								isBusy={ isSaving }
								aria-disabled={ isSaving }
								onClick={ handleModalChangeClick }
							>
								{ __( 'Change', 'woocommerce' ) }
							</Button>
						</div>
					</Modal>
				) }
			</div>
		</BlockFill>
	);
}
