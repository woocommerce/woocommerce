/**
 * External dependencies
 */
import React from 'react';
import { createElement } from '@wordpress/element';
import { createRegistry, RegistryProvider } from '@wordpress/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { store as coreDataStore } from '@wordpress/core-data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { RichTextEditor } from '../';

const registry = createRegistry();
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
registry.register( coreDataStore );
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
registry.register( blockEditorStore );

export const Basic: React.FC = () => {
	return (
		<RegistryProvider value={ registry }>
			<RichTextEditor blocks={ [] } onChange={ () => null } />
		</RegistryProvider>
	);
};

export const MultipleEditors: React.FC = () => {
	return (
		<RegistryProvider value={ registry }>
			<RichTextEditor blocks={ [] } onChange={ () => null } />
			<br />
			<RichTextEditor blocks={ [] } onChange={ () => null } />
		</RegistryProvider>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/RichTextEditor',
	component: RichTextEditor,
};
