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
>;

type EditorProps = {
	product: Product;
	settings: ProductEditorSettings | undefined;
};

export function Editor( { product, settings }: EditorProps ) {
	const [ selectedTab, setSelectedTab ] = useState< string | null >( null );

	const updatedLayoutContext = useExtendLayout( 'product-block-editor' );

	return (
		<LayoutContextProvider value={ updatedLayoutContext }>
			<StrictMode>
				<EntityProvider
					kind="postType"
					type="product"
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
										/>
									}
									content={
										<>
											<BlockEditor
												settings={ settings }
												product={ product }
												context={ {
													selectedTab,
													postType: 'product',
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
					<Footer product={ product } />
				</EntityProvider>
			</StrictMode>
		</LayoutContextProvider>
	);
}
