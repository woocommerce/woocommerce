/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Fragment, StrictMode } from '@wordpress/element';
import {
	EditorSettings,
	EditorBlockListSettings,
} from '@wordpress/block-editor';
import { EntityProvider } from '@wordpress/core-data';
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
		<>
			<StrictMode>
				<ShortcutProvider>
					<EntityProvider
						kind="postType"
						type="product"
						id={ product.id }
					>
						<FullscreenMode isActive={ false } />
						{ /* @ts-ignore */ }
						<SlotFillProvider>
							<InterfaceSkeleton
								header={
									<Header
										title={
											product.name ||
											__(
												'Add new product',
												'woocommerce'
											)
										}
										product={ product }
									/>
								}
								sidebar={ <Sidebar /> }
								content={
									<>
										<BlockEditor
											settings={ settings }
											product={ product }
										/>
									</>
								}
							/>

							<Popover.Slot />
						</SlotFillProvider>
					</EntityProvider>
				</ShortcutProvider>
			</StrictMode>
		</>
	);
}
