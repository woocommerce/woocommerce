/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { ComplementaryArea } from '@wordpress/interface';

type PluginSidebarProps = {
	children: React.ReactNode;
	className?: string;
	closeLabel?: string;
	header?: React.ReactNode;
	icon?: string | React.ReactNode;
	identifier?: string;
	isActiveByDefault?: boolean;
	name?: string;
	title?: string;
};

export function PluginSidebar( { className, ...props }: PluginSidebarProps ) {
	return (
		<ComplementaryArea
			panelClassName={ className }
			className="TODO"
			scope="woocommerce/product-editor-iframe-editor"
			{ ...props }
		/>
	);
}
