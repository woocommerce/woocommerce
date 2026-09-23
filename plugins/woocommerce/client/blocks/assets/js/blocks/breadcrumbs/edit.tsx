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

	// Remove the default 'has-small-font-size' class when the block has a
	// custom font size defined in theme.json.
	// This is needed because block.json defines a default font size, which is
	// considered an anti-pattern since styles should be defined by themes and
	// plugins instead.
	// As a result, font sizes defined in theme.json will take priority over a
	// `small` font size selected in the editor. When selecting other font
	// sizes, the editor font size will take priority over the theme.json font
	// size as expected.
	// That's a trade-off we are making until we can migrate this block to a new
	// version or to the WP core Breadcrumbs block.
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
