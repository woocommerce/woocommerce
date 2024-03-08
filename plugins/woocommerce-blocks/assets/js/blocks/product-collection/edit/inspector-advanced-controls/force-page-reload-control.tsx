/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

import type { ProductCollectionSetAttributes } from '../../types';

type ForcePageReloadControlProps = {
	enhancedPagination: boolean;
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
	const { enhancedPagination, setAttributes } = props;
	const hasUnsupportedBlocks = false; // TODO: Detect unsupported blocks

	const helpText = hasUnsupportedBlocks
		? helpTextIfDisabled
		: helpTextIfEnabled;

	return (
		<ToggleControl
			label={ __( 'Force Page Reload', 'woocommerce' ) }
			help={ helpText }
			checked={ ! enhancedPagination }
			onChange={ () =>
				setAttributes( { enhancedPagination: ! enhancedPagination } )
			}
			disabled={ hasUnsupportedBlocks }
		/>
	);
};

export default ForcePageReloadControl;
