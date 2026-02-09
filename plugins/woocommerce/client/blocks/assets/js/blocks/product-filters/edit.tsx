/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, close } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import { filterThreeLines } from '@woocommerce/icons';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './editor.scss';
import { type BlockAttributes } from './types';
import { getProductFiltersCss } from './utils/get-product-filters-css';

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/heading',
		{
			level: 2,
			content: __( 'Filters', 'woocommerce' ),
			style: {
				margin: { top: '0', bottom: '0' },
				spacing: { margin: { top: '0', bottom: '0' } },
			},
		},
	],
	[ 'woocommerce/product-filter-active' ],
	[ 'woocommerce/product-filter-price' ],
	[ 'woocommerce/product-filter-rating' ],
	[ 'woocommerce/product-filter-attribute' ],
	[ 'woocommerce/product-filter-taxonomy' ],
	[ 'woocommerce/product-filter-status' ],
];

export const Edit = ( props: BlockEditProps< BlockAttributes > ) => {
	const { attributes } = props;
	const { isPreview } = attributes;
	const [ isOpen, setIsOpen ] = useState( false );
	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filters', {
			'is-overlay-opened': isOpen,
		} ),
		style: getProductFiltersCss( attributes ),
	} );

	return (
		<div { ...blockProps }>
			{ isPreview ? (
				<div className="wc-block-product-filters__overlay-content">
					<InnerBlocks templateLock={ false } template={ TEMPLATE } />
				</div>
			) : (
				<>
					<button
						className="wc-block-product-filters__open-overlay"
						onClick={ () => setIsOpen( ! isOpen ) }
					>
						<Icon icon={ filterThreeLines } />
						<span>{ __( 'Filter products', 'woocommerce' ) }</span>
					</button>

					<div className="wc-block-product-filters__overlay">
						<div className="wc-block-product-filters__overlay-wrapper">
							<div
								className="wc-block-product-filters__overlay-dialog"
								role="dialog"
							>
								<header className="wc-block-product-filters__overlay-header">
									<button
										className="wc-block-product-filters__close-overlay"
										onClick={ () => setIsOpen( ! isOpen ) }
									>
										<span>
											{ __( 'Close', 'woocommerce' ) }
										</span>
										<Icon icon={ close } />
									</button>
								</header>
								<div className="wc-block-product-filters__overlay-content">
									<InnerBlocks
										templateLock={ false }
										template={ TEMPLATE }
									/>
								</div>
								<footer className="wc-block-product-filters__overlay-footer">
									<button
										className="wc-block-product-filters__apply wp-block-button__link wp-element-button"
										onClick={ () => setIsOpen( ! isOpen ) }
									>
										<span>
											{ __( 'Apply', 'woocommerce' ) }
										</span>
									</button>
								</footer>
							</div>
						</div>
					</div>
				</>
			) }
		</div>
	);
};
