/**
 * External dependencies
 */
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { Plugin, PluginProps } from './Plugin';
import './PluginList.scss';

export type PluginListProps = {
	currentPlugin?: string | null;
	key?: string;
	installAndActivate?: ( slug: string ) => void;
	onManage?: ( slug: string ) => void;
	plugins?: PluginProps[];
	title?: string;
};

export const PluginList: React.FC< PluginListProps > = ( {
	currentPlugin,
	installAndActivate = () => {},
	onManage = () => {},
	plugins = [],
	title,
} ) => {
	return (
		<div className="woocommerce-plugin-list">
			{ title && (
				<div className="woocommerce-plugin-list__title">
					<Text variant="sectionheading" as="h3">
						{ title }
					</Text>
				</div>
			) }
			{ plugins.map( ( plugin ) => {
				const {
					description,
					imageUrl,
					isActive,
					isBuiltByWC,
					isInstalled,
					manageUrl,
					slug,
					name,
				} = plugin;
				return (
					<Plugin
						key={ slug }
						description={ description }
						manageUrl={ manageUrl }
						name={ name }
						imageUrl={ imageUrl }
						installAndActivate={ installAndActivate }
						onManage={ onManage }
						isActive={ isActive }
						isBuiltByWC={ isBuiltByWC }
						isBusy={ currentPlugin === slug }
						isDisabled={ !! currentPlugin }
						isInstalled={ isInstalled }
						slug={ slug }
					/>
				);
			} ) }
		</div>
	);
};
