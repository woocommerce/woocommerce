/**
 * External dependencies
 */
import classNames from 'classnames';
import {
	Button,
	Dropdown,
	Fill,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	createElement,
	createInterpolateElement,
	lazy,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronRight, check } from '@wordpress/icons';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ProductEditorSettings } from '../../../components';
import { ProductTemplate } from '../../../components/editor';
import { useValidations } from '../../../contexts/validation-context';
import {
	WPError,
	getProductErrorMessage,
} from '../../../utils/get-product-error-message';
import { ProductEditorBlockEditProps } from '../../../types';
import { ProductDetailsSectionDescriptionBlockAttributes } from './types';

export function ProductDetailsSectionDescriptionBlockEdit( {
	attributes,
	clientId,
}: ProductEditorBlockEditProps< ProductDetailsSectionDescriptionBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const [ productType ] = useEntityProp( 'postType', 'product', 'type' );

	const { productTemplates, productTemplate: selectedProductTemplate } =
		useSelect(
			( select ) => {
				const { getEditorSettings } = select( 'core/editor' );
				return getEditorSettings() as ProductEditorSettings;
			},
			[ productType ]
		);

	const [ supportedProductTemplates, unsupportedProductTemplates ] =
		productTemplates.reduce< [ ProductTemplate[], ProductTemplate[] ] >(
			( [ supported, unsupported ], productTemplate ) => {
				if ( productTemplate.layoutTemplateId ) {
					supported.push( productTemplate );
				} else {
					unsupported.push( productTemplate );
				}
				return [ supported, unsupported ];
			},
			[ [], [] ]
		);

	const productId = useEntityId( 'postType', 'product' );

	const { validate } = useValidations< Product >();
	// @ts-expect-error There are no types for this.
	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const rootClientId = useSelect(
		( select ) => {
			const { getBlockRootClientId } = select( 'core/block-editor' );
			return getBlockRootClientId( clientId );
		},
		[ clientId ]
	);

	if ( ! rootClientId ) return;

	function menuItemClickHandler(
		productTemplate: ProductTemplate,
		onClose: () => void
	) {
		return async function handleMenuItemClick() {
			try {
				await validate( productTemplate.productData );

				await editEntityRecord(
					'postType',
					'product',
					productId,
					productTemplate.productData
				);

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
			} catch ( error ) {
				const message = getProductErrorMessage( error as WPError );
				createErrorNotice( message );
			}

			onClose();
		};
	}

	function resolveIcon( productTemplate: ProductTemplate ) {
		const iconName = productTemplate.icon;
		if ( ! iconName ) return undefined;

		const Icon = lazy( () =>
			import( '@wordpress/icons' ).then(
				( { Icon, [ iconName as never ]: icon } ) => ( {
					default: () => <Icon icon={ icon } size={ 24 } />,
				} )
			)
		);

		return <Icon />;
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
					icon={ isSelected ? check : resolveIcon( productTemplate ) }
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

	return (
		<Fill name={ rootClientId }>
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
					focusOnMount={ false }
					// @ts-ignore Property does exists
					popoverProps={ {
						placement: 'bottom-start',
					} }
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button
							aria-expanded={ isOpen }
							variant="link"
							onClick={ onToggle }
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
										focusOnMount={ false }
										// @ts-ignore Property does exists
										popoverProps={ {
											placement: 'right-start',
										} }
										renderToggle={ ( {
											isOpen,
											onToggle,
										} ) => (
											<MenuItem
												aria-expanded={ isOpen }
												icon={ chevronRight }
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
			</div>
		</Fill>
	);
}
