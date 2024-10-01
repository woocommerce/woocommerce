/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useHasUnsupportedBlocks } from './utils';
import type { ProductCollectionSetAttributes } from '../../types';

type ForcePageReloadControlProps = {
	clientId: string;
	forcePageReload: boolean;
	setAttributes: ProductCollectionSetAttributes;
};

const helpTextIfEnabled = __(
	'Enforce full page reload on certain interactions, like using paginations controls.',
	'woocommerce'
);
const helpTextIfDisabled = __(
	"Force page reload can't be disabled because there are non-compatible blocks inside the Product Collection block.",
	'woocommerce'
);

const ForcePageReloadControl = ( props: ForcePageReloadControlProps ) => {
	const { clientId, forcePageReload, setAttributes } = props;
	const hasUnsupportedBlocks = useHasUnsupportedBlocks( clientId );

	useEffect( () => {
		if ( ! forcePageReload && hasUnsupportedBlocks ) {
			setAttributes( { forcePageReload: true } );
		}
	}, [ forcePageReload, hasUnsupportedBlocks, setAttributes ] );

	const helpText = hasUnsupportedBlocks
		? helpTextIfDisabled
		: helpTextIfEnabled;

	return (
		<ToggleControl
			label={ __( 'Force Page Reload', 'woocommerce' ) }
			help={ helpText }
			checked={ forcePageReload }
			onChange={ () =>
				setAttributes( { forcePageReload: ! forcePageReload } )
			}
			disabled={ hasUnsupportedBlocks }
		/>
	);
};

export default ForcePageReloadControl;
