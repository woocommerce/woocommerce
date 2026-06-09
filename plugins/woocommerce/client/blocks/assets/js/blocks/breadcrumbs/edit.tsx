/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { useBreadcrumbsThemeFontSize } from './hooks';

export type Attributes = {
	className?: string;
	fontSize?: string;
};

const Edit = ( { attributes }: BlockEditProps< Attributes > ) => {
	const blockProps = useBlockProps( {
		className: 'woocommerce wc-block-breadcrumbs',
	} );

	const themeFontSize = useBreadcrumbsThemeFontSize();

	if (
		attributes.fontSize === 'small' &&
		themeFontSize &&
		themeFontSize !== 'var(--wp--preset--font-size--small)'
	) {
		blockProps.className = blockProps.className
			.split( ' ' )
			.filter( ( cls ) => cls && cls !== 'has-small-font-size' )
			.join( ' ' );
		blockProps.style.fontSize = themeFontSize;
	}

	return (
		<div { ...blockProps }>
			<Disabled>
				<a href="/">{ __( 'Breadcrumbs', 'woocommerce' ) }</a>
				{ __( ' / Navigation / Path', 'woocommerce' ) }
			</Disabled>
		</div>
	);
};

export default Edit;
