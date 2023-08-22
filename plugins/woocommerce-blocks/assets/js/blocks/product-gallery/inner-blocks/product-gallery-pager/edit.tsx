/**
 * External dependencies
 */
import { Icon } from '@wordpress/icons';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ProductGalleryPagerBlockSettings } from './settings';
import { PagerDotIcon, PagerSelectedDotIcon } from './icons';
import { BlockAttributes } from './types';
import { PagerDisplayModes } from './constants';
import type { ProductGalleryPagerContext } from '../../types';

const DigitsPager = (): JSX.Element => {
	const pagerDigitsItems = Array.from( { length: 4 }, ( _, index ) => {
		const isActive = index === 0;

		return (
			<li
				className={ `wc-block-editor-product-gallery-pager__pager-item ${
					isActive ? 'is-active' : ''
				}` }
				key={ index }
			>
				{ index + 1 }
			</li>
		);
	} );

	return (
		<ul className="wc-block-editor-product-gallery-pager__pager">
			{ pagerDigitsItems }
		</ul>
	);
};

interface DotsPagerProps {
	iconClass?: string;
}

const DotsPager = ( props: DotsPagerProps ): JSX.Element => {
	const { iconClass } = props;
	const pagerDotsItems = Array.from( { length: 3 }, ( _, index ) => {
		const icon = index === 0 ? PagerSelectedDotIcon : PagerDotIcon;

		return (
			<li key={ index }>
				<Icon className={ iconClass } icon={ icon } size={ 12 } />
			</li>
		);
	} );

	return (
		<ul className="wc-block-editor-product-gallery-pager__pager">
			{ pagerDotsItems }
		</ul>
	);
};

interface PagerProps {
	pagerDisplayMode: PagerDisplayModes;
}

const Pager = ( props: PagerProps ): JSX.Element | null => {
	const { pagerDisplayMode } = props;

	switch ( pagerDisplayMode ) {
		case PagerDisplayModes.DOTS:
			return <DotsPager />;
		case PagerDisplayModes.DIGITS:
			return <DigitsPager />;
		case PagerDisplayModes.OFF:
			return null;
		default:
			return <DotsPager />;
	}
};

interface EditProps {
	attributes: BlockAttributes;
	setAttributes: ( newAttributes: BlockAttributes ) => void;
	context: ProductGalleryPagerContext;
}

export const Edit = ( props: EditProps ): JSX.Element => {
	const { context } = props;
	const blockProps = useBlockProps( {
		className: 'wc-block-editor-product-gallery-pager',
	} );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<ProductGalleryPagerBlockSettings context={ context } />
			</InspectorControls>

			<Pager pagerDisplayMode={ context.pagerDisplayMode } />
		</div>
	);
};
