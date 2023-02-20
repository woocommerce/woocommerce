/**
 * External dependencies
 */
import { createElement, StrictMode } from '@wordpress/element';
import {
	EditorSettings,
	EditorBlockListSettings,
} from '@wordpress/block-editor';
import { Popover, SlotFillProvider } from '@wordpress/components';
import { Product } from '@woocommerce/data';
// @ts-ignore
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
// @ts-ignore
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
				{ /* @ts-ignore */ }
				<SlotFillProvider>
					<InterfaceSkeleton
						header={ <Header title={ product.name } /> }
						sidebar={ <Sidebar /> }
						content={ <BlockEditor settings={ settings } /> }
					/>
					<Popover.Slot />
				</SlotFillProvider>
			</ShortcutProvider>
		</StrictMode>
	);
}
