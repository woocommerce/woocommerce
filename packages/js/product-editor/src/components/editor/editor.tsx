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
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { FullscreenMode, InterfaceSkeleton } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { Header } from '../header';
import { Sidebar } from '../sidebar';
import { BlockEditor } from '../block-editor';
import { initBlocks } from './init-blocks';

initBlocks();

type EditorProps = {
	product: Product;
	settings: Partial< EditorSettings & EditorBlockListSettings > | undefined;
};

export function Editor( { product, settings }: EditorProps ) {
	return (
		<StrictMode>
			<ShortcutProvider>
				<FullscreenMode isActive={ false } />
				<SlotFillProvider>
					<InterfaceSkeleton
						header={ <Header title={ product.name } /> }
						sidebar={ <Sidebar /> }
						content={
							<BlockEditor
								settings={ settings }
								product={ product }
							/>
						}
					/>
				</SlotFillProvider>
			</ShortcutProvider>
		</StrictMode>
	);
}
