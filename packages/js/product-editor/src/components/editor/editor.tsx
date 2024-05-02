/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import {
	createElement,
	StrictMode,
	Fragment,
	useState,
	useEffect,
} from '@wordpress/element';
import {
	LayoutContextProvider,
	useExtendLayout,
} from '@woocommerce/admin-layout';
import { useSelect, select as WPSelect } from '@wordpress/data';
import { Popover } from '@wordpress/components';
import { Product } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { EntityProvider, useEntityRecord } from '@wordpress/core-data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { InterfaceSkeleton } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { Header } from '../header';
import { BlockEditor } from '../block-editor';
import { EditorLoadingContext } from '../../contexts/editor-loading-context';
import { ValidationProvider as OldValidationProvider } from '../../contexts/validation-context';
import { ValidationProvider } from '../validation-provider';
import { EditorProps } from './types';
import { store as productEditorUiStore } from '../../store/product-editor-ui';
import { PrepublishPanel } from '../prepublish-panel/prepublish-panel';
import {
	EntityConfig,
	OptionsResponse,
	Schema,
} from '../validation-provider/types';

export function Editor( { product, productType = 'product' }: EditorProps ) {
	const [ isEditorLoading, setIsEditorLoading ] = useState( true );
	const [ selectedTab, setSelectedTab ] = useState< string | null >( null );
	const [ schema, setSchema ] = useState< Schema | null >( null );

	const productConfig: EntityConfig = useSelect(
		( select: typeof WPSelect ) => {
			const { getEntityConfig } = select( 'core' );
			return getEntityConfig( 'postType', 'product' );
		}
	);

	useEffect( () => {
		if ( ! productConfig?.baseURL ) {
			return;
		}
		apiFetch< OptionsResponse >( {
			path: productConfig.baseURL,
			method: 'OPTIONS',
		} ).then( ( results ) => {
			setSchema( results.schema );
		} );
	}, [ productConfig ] );

	const updatedLayoutContext = useExtendLayout( 'product-block-editor' );

	const productId = product?.id || -1;

	// Check if the prepublish sidebar is open from the store.
	const isPrepublishPanelOpen = useSelect( ( select ) => {
		return select( productEditorUiStore ).isPrepublishPanelOpen();
	}, [] );

	const { editedRecord } = useEntityRecord< Product >(
		'postType',
		'product',
		productId
	);

	return (
		<LayoutContextProvider value={ updatedLayoutContext }>
			<StrictMode>
				<EntityProvider
					kind="postType"
					type={ productType }
					id={ productId }
				>
					<ShortcutProvider>
						<ValidationProvider
							record={ editedRecord }
							schema={ schema }
						>
							<OldValidationProvider initialValue={ product }>
								<EditorLoadingContext.Provider
									value={ isEditorLoading }
								>
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
													postType={ productType }
													productId={ productId }
													context={ {
														selectedTab,
														postType: productType,
														postId: productId,
													} }
													setIsEditorLoading={
														setIsEditorLoading
													}
												/>
											</>
										}
										actions={
											isPrepublishPanelOpen && (
												<PrepublishPanel
													productType={ productType }
												/>
											)
										}
									/>
								</EditorLoadingContext.Provider>
								<Popover.Slot />
							</OldValidationProvider>
						</ValidationProvider>
					</ShortcutProvider>
				</EntityProvider>
			</StrictMode>
		</LayoutContextProvider>
	);
}
