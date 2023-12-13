/**
 * External dependencies
 */
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
	Fragment,
	createElement,
	createInterpolateElement,
	lazy,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronRight } from '@wordpress/icons';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ProductEditorSettings } from '../../../components';
import { ProductTemplate } from '../../../components/editor';
import { AUTO_DRAFT_NAME } from '../../../utils';
import { ProductEditorBlockEditProps } from '../../../types';
import { ProductDetailsSectionDescriptionBlockAttributes } from './types';

export function ProductDetailsSectionDescriptionBlockEdit( {
	attributes,
	clientId,
}: ProductEditorBlockEditProps< ProductDetailsSectionDescriptionBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const [ productType ] = useEntityProp( 'postType', 'product', 'type' );

	const { productTemplates } = useSelect( ( select ) => {
		const { getEditorSettings } = select( 'core/editor' );
		return getEditorSettings() as ProductEditorSettings;
	} );

	const [ supportedProductTemplates, unsupportedProductTemplates ] =
		productTemplates.reduce< [ ProductTemplate[], ProductTemplate[] ] >(
			( [ supported, unsupported ], productTemplate ) => {
				if ( productTemplate.layout_template_id ) {
					supported.push( productTemplate );
				} else {
					unsupported.push( productTemplate );
				}
				return [ supported, unsupported ];
			},
			[ [], [] ]
		);

	const productId = useEntityId( 'postType', 'product' );

	const hasEdits = useSelect(
		( select ) => {
			// @ts-expect-error There are no types for this.
			const { hasEditsForEntityRecord } = select( 'core' );
			return hasEditsForEntityRecord( 'postType', 'product', productId );
		},
		[ productId ]
	);

	const { saveEntityRecord } = useDispatch( 'core' );
	const { createErrorNotice } = useDispatch( 'core/notices' );

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
			if ( hasEdits ) {
				createErrorNotice(
					__(
						'The current product must be saved before continue.',
						'woocommerce'
					)
				);
				return;
			}

			const newProduct = await ( saveEntityRecord(
				'postType',
				'product',
				{
					title: AUTO_DRAFT_NAME,
					status: 'auto-draft',
					...productTemplate.product_data,
				}
			) as never as Promise< Product > );

			const url = getNewPath( {}, `/product/${ newProduct.id }` );
			navigateTo( { url } );

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
			return (
				<MenuItem
					key={ productTemplate.id }
					info={ productTemplate.description ?? undefined }
					isSelected={
						productType === productTemplate.product_data.type
					}
					icon={ resolveIcon( productTemplate ) }
					iconPosition="left"
					onClick={ menuItemClickHandler( productTemplate, onClose ) }
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
						/* translators: <ProductType />: the product type. */
						__(
							'This is a <ProductType /> product.',
							'woocommerce'
						),
						{
							ProductType: <Fragment>{ productType }</Fragment>,
						}
					) }
				</p>

				<Dropdown
					focusOnMount={ false }
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
