/**
 * External dependencies
 */
import { createElement, useMemo } from '@wordpress/element';
import { Template } from '@wordpress/blocks';
import { DataFormProps } from '@wordpress/dataviews';
import { Product } from '@woocommerce/data';
// @ts-expect-error missing types.
// eslint-disable-next-line @woocommerce/dependency-group
import { privateApis as componentsPrivateApis } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { unlock } from '../lock-unlock';
import { ProductSection } from './product-section';

const { Tabs } = unlock( componentsPrivateApis );

type ProductSectionProps = {
	sectionTemplate: Template[];
	postType: string;
} & Omit< DataFormProps< Product >, 'fields' | 'form' >;

type Tab = {
	id: string;
	title: string;
	children: Template[];
};

export function ProductTabs( {
	sectionTemplate,
	postType,
	...dataFormProps
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
			<Tabs.TabList>
				{ tabs.map( ( tab ) => (
					<Tabs.Tab key={ tab.id } tabId={ tab.id }>
						{ tab.title }
					</Tabs.Tab>
				) ) }
			</Tabs.TabList>
			{ tabs.map( ( tab ) => (
				<Tabs.TabPanel key={ tab.id } tabId={ tab.id }>
					{ tab.children.map( ( child, index ) => (
						<ProductSection
							key={ child[ 1 ]?._templateBlockId || index }
							{ ...dataFormProps }
							postType={ postType }
							sectionTemplate={ child }
						/>
					) ) }
				</Tabs.TabPanel>
			) ) }
		</Tabs>
	);
}
