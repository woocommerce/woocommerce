/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ReactSettingsPage } from './react-settings-page';
import { baseFieldTransformer } from './field-transformers';
import { useReactSettings } from './hooks/use-react-settings';
import type { FieldTransformer, RowConfigurations } from './types';
import {
	fieldTransformer as generalFieldTransformer,
	rowConfigurations as generalRowConfigurations,
} from '../settings-general/react-settings-config';

type ReactSettingsRegistryEntry = {
	id: string;
	dataPath: string[];
	mountId: string;
	className?: string;
	fieldTransformer: FieldTransformer;
	rowConfigurations?: RowConfigurations;
	missingDataMessage?: string;
};

const defaultFieldTransformer: FieldTransformer = ( field ) =>
	baseFieldTransformer( field ) as Record< string, unknown >;

const registry: ReactSettingsRegistryEntry[] = [
	{
		id: 'general',
		dataPath: [ 'settings', 'general' ],
		mountId: 'wc_settings_react_general_default',
		className: 'woocommerce-settings-general',
		fieldTransformer: generalFieldTransformer,
		rowConfigurations: generalRowConfigurations,
		missingDataMessage: 'General settings data is missing.',
	},
	{
		id: 'products.general',
		dataPath: [ 'settings', 'products', 'general' ],
		mountId: 'wc_settings_react_products_default',
		className: 'woocommerce-settings-products',
		fieldTransformer: defaultFieldTransformer,
	},
	{
		id: 'products.inventory',
		dataPath: [ 'settings', 'products', 'inventory' ],
		mountId: 'wc_settings_react_products_inventory',
		className: 'woocommerce-settings-products',
		fieldTransformer: defaultFieldTransformer,
	},
];

const ReactSettingsScreen = ( {
	entry,
}: {
	entry: ReactSettingsRegistryEntry;
} ) => {
	const { data, isLoading, error } = useReactSettings( {
		dataPath: entry.dataPath,
		missingDataMessage: entry.missingDataMessage,
	} );

	return (
		<ReactSettingsPage
			className={ entry.className }
			data={ data }
			error={ error }
			fieldTransformer={ entry.fieldTransformer }
			isLoading={ isLoading }
			rowConfigurations={ entry.rowConfigurations }
		/>
	);
};

export const registerReactSettingsScreens = () => {
	registry.forEach( ( entry ) => {
		const rootElement = document.getElementById( entry.mountId );
		if ( ! rootElement ) {
			return;
		}

		createRoot( rootElement ).render(
			<ReactSettingsScreen entry={ entry } />
		);
	} );
};
