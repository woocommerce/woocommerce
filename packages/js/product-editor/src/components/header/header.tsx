/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { WooHeaderItem } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import { getHeaderTitle } from '../../utils';
import { MoreMenu } from './more-menu';
import { PreviewButton } from './preview-button';
import { SaveDraftButton } from './save-draft-button';
import { PublishButton } from './publish-button';
import { Tabs } from '../tabs';

export type HeaderProps = {
	onTabSelect: ( tabId: string | null ) => void;
	productName: string;
};

export function Header( { onTabSelect, productName }: HeaderProps ) {
	const [ editedProductName ] = useEntityProp< string >(
		'postType',
		'product',
		'name'
	);

	return (
		<div
			className="woocommerce-product-header"
			role="region"
			aria-label={ __( 'Product Editor top bar.', 'woocommerce' ) }
			tabIndex={ -1 }
		>
			<div className="woocommerce-product-header__inner">
				<div />

				<h1 className="woocommerce-product-header__title">
					{ getHeaderTitle( editedProductName, productName ) }
				</h1>

				<div className="woocommerce-product-header__actions">
					<SaveDraftButton />

					<PreviewButton />

					<PublishButton />

					<WooHeaderItem.Slot name="product" />
					<MoreMenu />
				</div>
			</div>
			<Tabs onChange={ onTabSelect } />
		</div>
	);
}
