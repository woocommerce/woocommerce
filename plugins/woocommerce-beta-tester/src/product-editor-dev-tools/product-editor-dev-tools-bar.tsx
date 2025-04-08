/**
 * External dependencies
 */
import { useContext, useEffect, useState } from 'react';
import { WooFooterItem } from '@woocommerce/admin-layout';
import { PostTypeContext } from '@woocommerce/product-editor';
import { Button, NavigableMenu } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { useEntityProp, store as coreDataStore } from '@wordpress/core-data';

import { store as blockEditorStore } from '@wordpress/block-editor';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { BlockInspectorTabPanel } from './block-inspector-tab-panel';
import { HelpTabPanel } from './help-tab-panel';
import { ProductTabPanel } from './product-tab-panel';
import { UserPreferencesTabPanel } from './user-preferences-panel';

function TabButton( {
	name,
	selectedTab,
	onClick,
	children,
}: {
	name: string;
	selectedTab: string;
	onClick: ( name: string ) => void;
	children: React.ReactNode;
} ) {
	return (
		<Button
			onClick={ () => onClick( name ) }
			aria-selected={ name === selectedTab }
			className="woocommerce-product-editor-dev-tools-bar__tab-button"
		>
			{ children }
		</Button>
	);
}

export function ProductEditorDevToolsBar( {
	onClose,
}: {
	onClose: () => void;
} ) {
	const postType = useContext( PostTypeContext );

	const [ id ] = useEntityProp( 'postType', postType, 'id' );

	// @ts-expect-error TODO: react-18-upgrade: getEditedEntityRecord return Product type which is not defined in @wordpress/core-data
	const product: Product = useSelect(
		( select ) =>
			select( coreDataStore ).getEditedEntityRecord(
				'postType',
				postType,
				id
			),
		[ id, postType ]
	);

	const [ lastSelectedBlock, setLastSelectedBlock ] = useState( null );

	const selectedBlock = useSelect(
		// @ts-expect-error No types for this exist yet. Need to add it to the blockEditorStore types.
		( select ) => select( blockEditorStore ).getSelectedBlock(),
		[ id, postType ]
	);

	useEffect( () => {
		if ( selectedBlock !== null ) {
			setLastSelectedBlock( selectedBlock );
		}
	}, [ selectedBlock ] );

	const evaluationContext: {
		postType: string;
		editedProduct: Product;
	} = {
		postType,
		editedProduct: product,
	};

	const [ selectedTab, setSelectedTab ] = useState< string >( 'inspector' );

	function handleNavigate( _childIndex: number, child: HTMLElement ) {
		child.click();
	}

	function handleTabClick( tabName: string ) {
		setSelectedTab( tabName );
	}

	return (
		<WooFooterItem>
			<div className="woocommerce-product-editor-dev-tools-bar">
				<div className="woocommerce-product-editor-dev-tools-bar__header">
					<div className="woocommerce-product-editor-dev-tools-bar__tabs">
						<NavigableMenu
							role="tablist"
							orientation="horizontal"
							onNavigate={ handleNavigate }
						>
							<TabButton
								name="inspector"
								selectedTab={ selectedTab }
								onClick={ handleTabClick }
							>
								{ __( 'Block Inspector', 'woocommerce' ) }
							</TabButton>
							<TabButton
								name="product"
								selectedTab={ selectedTab }
								onClick={ handleTabClick }
							>
								{ __( 'Product', 'woocommerce' ) }
							</TabButton>

							<TabButton
								name="user-preferences"
								selectedTab={ selectedTab }
								onClick={ handleTabClick }
							>
								{ __( 'User Preferences', 'woocommerce' ) }
							</TabButton>

							<TabButton
								name="help"
								selectedTab={ selectedTab }
								onClick={ handleTabClick }
							>
								{ __( 'Help', 'woocommerce' ) }
							</TabButton>
						</NavigableMenu>
					</div>
					<div className="woocommerce-product-editor-dev-tools-bar__actions">
						<Button
							icon={ closeSmall }
							label={ __( 'Close', 'woocommerce' ) }
							onClick={ onClose }
						/>
					</div>
				</div>
				<div className="woocommerce-product-editor-dev-tools-bar__panel">
					<BlockInspectorTabPanel
						selectedBlock={ lastSelectedBlock }
						isSelected={ selectedTab === 'inspector' }
					/>
					<ProductTabPanel
						evaluationContext={ evaluationContext }
						isSelected={ selectedTab === 'product' }
					/>
					<UserPreferencesTabPanel
						isSelected={ selectedTab === 'user-preferences' }
					/>
					<HelpTabPanel isSelected={ selectedTab === 'help' } />
				</div>
			</div>
		</WooFooterItem>
	);
}
