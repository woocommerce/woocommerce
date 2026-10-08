/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { type BlockAttributes } from './types';
import { getColorsFromBlockSupports } from './utils/get-colors-from-block-supports';
import { presetToCssVariable } from './utils/preset-to-css-variable';

function isObject< T extends Record< string, unknown >, U >(
	term: T | U
): term is NonNullable< T > {
	return (
		term !== null && term instanceof Object && term.constructor === Object
	);
}

function objectHasProp< P extends PropertyKey >(
	target: unknown,
	property: P
): target is { [ K in P ]: unknown } {
	return isObject( target ) && property in target;
}

function getProductFiltersCssV1( attributes: BlockAttributes ) {
	const colors = getColorsFromBlockSupports( attributes );
	const styles: Record< string, string | undefined > = {
		'--wc-product-filters-text-color': colors.textColor || '#111',
		'--wc-product-filters-background-color':
			colors.backgroundColor || '#fff',
	};
	if (
		objectHasProp( attributes, 'style' ) &&
		objectHasProp( attributes.style, 'spacing' ) &&
		objectHasProp( attributes.style.spacing, 'blockGap' ) &&
		typeof attributes.style.spacing.blockGap === 'string'
	) {
		styles[ '--wc-product-filter-block-spacing' ] = presetToCssVariable(
			attributes.style.spacing.blockGap
		);
	}
	return styles;
}

type LegacyDrawerAttributes = BlockAttributes & {
	showFilterDrawer?: boolean;
};

const v2 = {
	attributes: {
		...metadata.attributes,
		showFilterDrawer: {
			type: 'boolean',
		},
	},
	save( { attributes }: { attributes: LegacyDrawerAttributes } ) {
		const blockProps = useBlockProps.save( {
			className: clsx( 'wc-block-product-filters', {
				'is-filter-drawer-disabled':
					attributes.showFilterDrawer === false,
			} ),
		} );
		const innerBlocksProps = useInnerBlocksProps.save( blockProps );
		return <div { ...innerBlocksProps } />;
	},
	isEligible( attributes: LegacyDrawerAttributes ) {
		return typeof attributes.showFilterDrawer === 'boolean';
	},
	migrate( attributes: LegacyDrawerAttributes ) {
		const { showFilterDrawer, ...currentAttributes } = attributes;

		return {
			...currentAttributes,
			overlayMode: showFilterDrawer === false ? 'off' : 'mobile',
		};
	},
};

const v1 = {
	save( { attributes }: { attributes: BlockAttributes } ) {
		const blockProps = useBlockProps.save( {
			className: 'wc-block-product-filters',
			style: getProductFiltersCssV1( attributes ),
		} );
		const innerBlocksProps = useInnerBlocksProps.save( blockProps );
		return <div { ...innerBlocksProps } />;
	},
};

export default [ v2, v1 ];
