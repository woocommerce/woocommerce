/**
 * External dependencies
 */
import { WooHeaderItem, useAdminSidebarWidth } from '@woocommerce/admin-layout';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { createElement, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Tooltip } from '@wordpress/components';
import { chevronLeft, group, Icon } from '@wordpress/icons';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { PinnedItems } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { getHeaderTitle } from '../../utils';
import { MoreMenu } from './more-menu';
import { PreviewButton } from './preview-button';
import { SaveDraftButton } from './save-draft-button';
import { PublishButton } from './publish-button';
import { Tabs } from '../tabs';
import { HEADER_PINNED_ITEMS_SCOPE, TRACKS_SOURCE } from '../../constants';

export type HeaderProps = {
	onTabSelect: ( tabId: string | null ) => void;
	productType?: string;
};

const RETURN_TO_MAIN_PRODUCT = __(
	'Return to the main product',
	'woocommerce'
);

export function Header( {
	onTabSelect,
	productType = 'product',
}: HeaderProps ) {
	const [ productId ] = useEntityProp< number >(
		'postType',
		productType,
		'id'
	);

	const lastPersistedProduct = useSelect(
		( select ) => {
			const { getEntityRecord } = select( 'core' );
			return getEntityRecord( 'postType', productType, productId );
		},
		[ productId ]
	);

	const [ editedProductName ] = useEntityProp< string >(
		'postType',
		productType,
		'name'
	);

	const sidebarWidth = useAdminSidebarWidth();

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

	if ( ! productId ) {
		return null;
	}

	const isVariation = lastPersistedProduct?.parent_id > 0;

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

				<h1 className="woocommerce-product-header__title">
					{ isVariation ? (
						<div className="woocommerce-product-header__variable-product-title">
							<Icon icon={ group } />
							<span className="woocommerce-product-header__variable-product-name">
								{ lastPersistedProduct?.name }
							</span>
							<span className="woocommerce-product-header__variable-product-id">
								# { lastPersistedProduct?.id }
							</span>
						</div>
					) : (
						getHeaderTitle(
							editedProductName,
							lastPersistedProduct?.name
						)
					) }
				</h1>

				<div className="woocommerce-product-header__actions">
					{ ! isVariation && (
						<SaveDraftButton
							productType={ productType }
							productStatus={ lastPersistedProduct?.status }
						/>
					) }

					<PreviewButton
						productType={ productType }
						productStatus={ lastPersistedProduct?.status }
					/>

					<PublishButton
						productType={ productType }
						productStatus={ lastPersistedProduct?.status }
					/>

					<WooHeaderItem.Slot name="product" />
					<PinnedItems.Slot scope={ HEADER_PINNED_ITEMS_SCOPE } />
					<MoreMenu />
				</div>
			</div>
			<Tabs onChange={ onTabSelect } />
		</div>
	);
}
