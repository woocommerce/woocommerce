/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

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
import '../settings-general/settings-general-main.scss';

type ReactSettingsRegistryEntry = {
	id: string;
	dataPath: string[];
	mountId: string;
	className?: string;
	fieldTransformer: FieldTransformer;
	rowConfigurations?: RowConfigurations;
	missingDataMessage?: string;
};

type ReactSettingsScreenOverrides = {
	className?: string;
	fieldTransformer?: FieldTransformer;
	rowConfigurations?: RowConfigurations;
	missingDataMessage?: string;
};

const defaultFieldTransformer: FieldTransformer = ( field ) =>
	baseFieldTransformer( field ) as Record< string, unknown >;

const screenOverrides = new Map< string, ReactSettingsScreenOverrides >();

const getScreenKey = ( tab: string, section: string ) =>
	`${ tab }::${ section || 'default' }`;

const buildDataPath = ( tab: string, section: string ) => [
	'settings',
	tab,
	section || 'default',
];

export const overrideReactSettingsScreen = (
	tab: string,
	section: string,
	overrides: ReactSettingsScreenOverrides
) => {
	screenOverrides.set( getScreenKey( tab, section ), overrides );
};

const getOverridesForScreen = ( tab: string, section: string ) =>
	screenOverrides.get( getScreenKey( tab, section ) ) || {};

overrideReactSettingsScreen( 'general', '', {
	className: 'woocommerce-settings-general',
	fieldTransformer: generalFieldTransformer,
	rowConfigurations: generalRowConfigurations,
	missingDataMessage: __(
		'General settings data is missing.',
		'woocommerce'
	),
} );

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
	const elements = document.querySelectorAll< HTMLElement >(
		'[data-wc-modern-settings]'
	);

	elements.forEach( ( element ) => {
		const tab = element.dataset.wcSettingsTab;
		if ( ! tab ) {
			return;
		}

		const section = element.dataset.wcSettingsSection || '';
		const overrides = getOverridesForScreen( tab, section );
		const entry: ReactSettingsRegistryEntry = {
			id: getScreenKey( tab, section ),
			dataPath: buildDataPath( tab, section ),
			mountId: element.id,
			className: overrides.className || `woocommerce-settings-${ tab }`,
			fieldTransformer:
				overrides.fieldTransformer || defaultFieldTransformer,
			rowConfigurations: overrides.rowConfigurations,
			missingDataMessage: overrides.missingDataMessage,
		};

		createRoot( element ).render( <ReactSettingsScreen entry={ entry } /> );
	} );
};

const windowWithRegistry = window as Window & {
	wcReactSettings?: {
		overrideReactSettingsScreen?: typeof overrideReactSettingsScreen;
	};
};

windowWithRegistry.wcReactSettings = windowWithRegistry.wcReactSettings || {};
windowWithRegistry.wcReactSettings.overrideReactSettingsScreen =
	overrideReactSettingsScreen;
