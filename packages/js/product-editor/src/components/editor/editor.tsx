/**
 * External dependencies
 */
import {
	createElement,
	StrictMode,
	Fragment,
	useState,
} from '@wordpress/element';
import {
	LayoutContextProvider,
	useExtendLayout,
} from '@woocommerce/admin-layout';
import {
	EditorSettings,
	EditorBlockListSettings,
} from '@wordpress/block-editor';
import { Template } from '@wordpress/blocks';
import { Popover } from '@wordpress/components';
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
									</>
								}
							/>
							<Popover.Slot />
						</ValidationProvider>
					</ShortcutProvider>
				</EntityProvider>
			</StrictMode>
		</LayoutContextProvider>
	);
}
