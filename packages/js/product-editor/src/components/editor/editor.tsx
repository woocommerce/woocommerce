/**
 * External dependencies
 */
import { createElement, StrictMode } from '@wordpress/element';
import {
	EditorSettings,
	EditorBlockListSettings,
} from '@wordpress/block-editor';
import { SlotFillProvider } from '@wordpress/components';
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
	return (
		<StrictMode>
			<EntityProvider kind="postType" type="product" id={ product.id }>
				<ShortcutProvider>
					<FullscreenMode isActive={ false } />
					<SlotFillProvider>
						<InterfaceSkeleton
							header={
								<Header
									productId={ product.id }
									productName={ product.name }
								/>
							}
							content={
								<BlockEditor
									settings={ settings }
									product={ product }
								/>
							}
						/>
					</SlotFillProvider>
				</ShortcutProvider>
			</EntityProvider>
		</StrictMode>
	);
}
