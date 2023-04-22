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
import { Header } from '../header';
import { BlockEditor } from '../block-editor';
import { initBlocks } from './init-blocks';

initBlocks();

export type ProductEditorSettings = Partial<
	EditorSettings & EditorBlockListSettings
>;

type EditorProps = {
	product: Product;
	settings: ProductEditorSettings | undefined;
};

export function Editor( { product, settings }: EditorProps ) {
	const [ selectedTab, setSelectedTab ] = useState< string | null >( null );

	return (
		<StrictMode>
			<EntityProvider kind="postType" type="product" id={ product.id }>
				<ShortcutProvider>
					<FullscreenMode isActive={ false } />
					<SlotFillProvider>
						<InterfaceSkeleton
							header={
								<Header
									productName={ product.name }
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
					</SlotFillProvider>
				</ShortcutProvider>
			</EntityProvider>
		</StrictMode>
	);
}
