/**
 * External dependencies
 */
import { useContext, useState } from 'react';
import { WooFooterItem } from '@woocommerce/admin-layout';
import { PostTypeContext } from '@woocommerce/product-editor';
import { Button, NavigableMenu } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet, natively (not until 7.0.0).
// Including `@types/wordpress__data` as a devDependency causes build issues,
// so just going type-free for now.
// eslint-disable-next-line @woocommerce/dependency-group
import { useSelect, select as WPSelect } from '@wordpress/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { BlockInspector } from './block-inspector';
import { Help } from './help';
import { Product } from './product';
import { TabPanel } from './tab-panel';
import { useFocusedBlock } from './hooks/use-focused-block';

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

	const product = useSelect( ( select: typeof WPSelect ) =>
		select( 'core' ).getEditedEntityRecord( 'postType', postType, id )
	);

	const evaluationContext = {
		postType,
		editedProduct: product,
	};

	const [ selectedTab, setSelectedTab ] = useState< string >( 'inspector' );

	const blockInfo = useFocusedBlock();

	function handleNavigate( _childIndex: number, child: HTMLButtonElement ) {
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
					<TabPanel isSelected={ selectedTab === 'inspector' }>
						<BlockInspector blockInfo={ blockInfo } />
					</TabPanel>
					<TabPanel isSelected={ selectedTab === 'product' }>
						<Product evaluationContext={ evaluationContext } />
					</TabPanel>
					<TabPanel isSelected={ selectedTab === 'help' }>
						<Help />
					</TabPanel>
				</div>
			</div>
		</WooFooterItem>
	);
}
