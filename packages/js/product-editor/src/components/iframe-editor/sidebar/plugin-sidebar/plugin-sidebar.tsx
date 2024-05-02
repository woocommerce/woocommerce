/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { ComplementaryArea } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { SIDEBAR_COMPLEMENTARY_AREA_SCOPE } from '../../constants';

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
			scope={ SIDEBAR_COMPLEMENTARY_AREA_SCOPE }
			{ ...props }
		/>
	);
}
