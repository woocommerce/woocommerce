/**
 * External dependencies
 */
import { useState } from 'react';
import { WooFooterItem } from '@woocommerce/admin-layout';
import { Button, NavigableMenu } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { BlockInspector } from './block-inspector';
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
								name="about"
								selectedTab={ selectedTab }
								onClick={ handleTabClick }
							>
								{ __( 'About', 'woocommerce' ) }
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
					{ selectedTab === 'inspector' && (
						<BlockInspector blockInfo={ blockInfo } />
					) }
					{ selectedTab === 'about' && (
						<div>About developer tools</div>
					) }
				</div>
			</div>
		</WooFooterItem>
	);
}
