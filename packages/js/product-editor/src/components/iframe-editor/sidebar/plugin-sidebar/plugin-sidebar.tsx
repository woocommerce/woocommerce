/**
 * External dependencies
 */
import { createElement, lazy } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SIDEBAR_COMPLEMENTARY_AREA_SCOPE } from '../../constants';

const ComplementaryArea = lazy( () =>
	// @ts-expect-error No types for this exist yet
	import( '@wordpress/interface' ).then( ( module ) => ( {
		default: module.ComplementaryArea,
	} ) )
);

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
	smallScreenTitle: string;
};

export function PluginSidebar( { className, ...props }: PluginSidebarProps ) {
	return (
		<ComplementaryArea
			// @ts-expect-error No types for this exist yet
			panelClassName={ className }
			className="woocommerce-iframe-editor__sidebar"
			scope={ SIDEBAR_COMPLEMENTARY_AREA_SCOPE }
			{ ...props }
		/>
	);
}
