/**
 * External dependencies
 */
import { createElement, StrictMode, useCallback } from '@wordpress/element';
import {
	LayoutContextProvider,
	useExtendLayout,
} from '@woocommerce/admin-layout';
import { navigateTo, getNewPath, getQuery } from '@woocommerce/navigation';
import { useLayoutTemplate } from '@woocommerce/block-templates';
import { Popover } from '@wordpress/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { EntityProvider } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { Header } from '../../../components/header';
import { ValidationProvider } from '../../../contexts/validation-context';
import { EditorProps } from './types';
import { ProductTabs } from '../tabs';
import { initFields } from '../fields';

initFields();

export function Editor( { productId, postType = 'product' }: EditorProps ) {
	const query = getQuery() as Record< string, string >;
	const selectedTab = query.tab || null;

	const setSelectedTab = useCallback( ( tabId: string ) => {
		navigateTo( { url: getNewPath( { tab: tabId } ) } );
	}, [] );

	const updatedLayoutContext = useExtendLayout( 'product-editor' );

	const { layoutTemplate } = useLayoutTemplate( 'simple-product' );

	return (
		<LayoutContextProvider value={ updatedLayoutContext }>
			<StrictMode>
				<EntityProvider
					kind="postType"
					type={ postType }
					id={ productId }
				>
					<ValidationProvider
						postType={ postType }
						productId={ productId }
					>
						<Header
							onTabSelect={ setSelectedTab }
							productType={ postType }
							selectedTab={ selectedTab }
						/>
						{ /* @ts-expect-error Type definitions are missing */ }
						{ layoutTemplate?.blockTemplates && (
							<ProductTabs
								sectionTemplate={
									// @ts-expect-error Type definitions are missing
									layoutTemplate?.blockTemplates
								}
								postType={ postType }
								productId={ productId }
							/>
						) }
						{ /* @ts-expect-error name does exist on PopoverSlot see: https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/popover/index.tsx#L555 */ }
						<Popover.Slot />
					</ValidationProvider>
				</EntityProvider>
			</StrictMode>
		</LayoutContextProvider>
	);
}
