/**
 * External dependencies
 */
import {
	createElement,
	StrictMode,
	Fragment,
	useState,
} from '@wordpress/element';
import { PluginArea } from '@wordpress/plugins';
import {
	LayoutContextProvider,
	useExtendLayout,
} from '@woocommerce/admin-layout';
import {
	EditorSettings,
	EditorBlockListSettings,
} from '@wordpress/block-editor';
import { Template } from '@wordpress/blocks';
import { Popover, SlotFillProvider } from '@wordpress/components';
import { Product } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { EntityProvider } from '@wordpress/core-data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { FullscreenMode, InterfaceSkeleton } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { Footer } from '../footer';
import { Header } from '../header';
import { BlockEditor } from '../block-editor';
import { ValidationProvider } from '../../contexts/validation-context';

export type ProductEditorSettings = Partial<
	EditorSettings & EditorBlockListSettings
> & {
	templates: Record< string, Template[] >;
};

type EditorProps = {
	product: Pick< Product, 'id' | 'type' >;
	productType?: string;
	settings: ProductEditorSettings | undefined;
};

export function Editor( {
	product,
	productType = 'product',
	settings,
}: EditorProps ) {
	const [ selectedTab, setSelectedTab ] = useState< string | null >( null );

	const updatedLayoutContext = useExtendLayout( 'product-block-editor' );

	return (
		<LayoutContextProvider value={ updatedLayoutContext }>
			<StrictMode>
				<EntityProvider
					kind="postType"
					type={ productType }
					id={ product.id }
				>
					<ShortcutProvider>
						<FullscreenMode isActive={ false } />
						<SlotFillProvider>
							<ValidationProvider initialValue={ product }>
								<InterfaceSkeleton
									header={
										<Header
											onTabSelect={ setSelectedTab }
											productType={ productType }
										/>
									}
									content={
										<>
											<BlockEditor
												settings={ settings }
												productType={ productType }
												productId={ product.id }
												context={ {
													selectedTab,
													postType: productType,
													postId: product.id,
												} }
											/>
											{ /* @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated. */ }
											<PluginArea scope="woocommerce-product-block-editor" />
										</>
									}
								/>
								<Popover.Slot />
							</ValidationProvider>
						</SlotFillProvider>
					</ShortcutProvider>
					{ /* We put Footer here instead of in InterfaceSkeleton because Footer uses
					WooFooterItem to actually render in the WooFooterItem.Slot defined by
					WooCommerce Admin. And, we need to put it outside of the SlotFillProvider
					we create in this component. */ }
					<Footer
						productId={ product.id }
						productType={ product.type }
					/>
				</EntityProvider>
			</StrictMode>
		</LayoutContextProvider>
	);
}
