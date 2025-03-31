/**
 * External dependencies
 */
import { createElement, useMemo } from '@wordpress/element';
import { Template } from '@wordpress/blocks';
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';
import { privateApis as componentsPrivateApis } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductSection } from '../section';

const { unlock } = __dangerousOptInToUnstableAPIsOnlyForCoreModules(
	'I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.',
	'@wordpress/edit-site' // The module name must be in the list of allowed, so for now I used the package name of the post editor
);

const { Tabs } = unlock( componentsPrivateApis );

type ProductSectionProps = {
	sectionTemplate: Template[];
	postType: string;
	productId: number;
};

type Tab = {
	id: string;
	title: string;
	children: Template[];
};

export function ProductTabs( {
	sectionTemplate,
	postType,
	productId,
}: ProductSectionProps ) {
	const tabs = useMemo( () => {
		return sectionTemplate
			.map( ( template ) => {
				if ( template[ 0 ] === 'woocommerce/product-tab' ) {
					return {
						...template[ 1 ],
						children: template[ 2 ],
					};
				}
				return null;
			} )
			.filter( ( tab ): tab is Tab => !! tab );
	}, [ sectionTemplate ] );

	return (
		<Tabs
			// onNavigate={ selectTabOnNavigate }
			// onKeyDown={ handleKeyDown }
			className="woocommerce-product-tabs"
		>
			<div className="woocommerce-product-tabs__tablist-container">
				<Tabs.TabList className="woocommerce-product-tabs__tablist">
					{ tabs.map( ( tab ) => (
						<Tabs.Tab key={ tab.id } tabId={ tab.id }>
							{ tab.title }
						</Tabs.Tab>
					) ) }
				</Tabs.TabList>
			</div>
			{ tabs.map( ( tab ) => (
				<Tabs.TabPanel
					key={ tab.id }
					tabId={ tab.id }
					className="woocommerce-product-tabs__tabpanel"
				>
					{ tab.children.map( ( child, index ) => {
						if ( child[ 0 ] === 'woocommerce/product-section' ) {
							return (
								<ProductSection
									key={
										child[ 1 ]?._templateBlockId || index
									}
									postType={ postType }
									sectionTemplate={ child }
									productId={ productId }
								/>
							);
						}
						return null;
					} ) }
				</Tabs.TabPanel>
			) ) }
		</Tabs>
	);
}
