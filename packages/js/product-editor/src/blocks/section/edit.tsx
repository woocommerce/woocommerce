/**
 * External dependencies
 */
import classNames from 'classnames';
import { Product } from '@woocommerce/data';
import { createElement, useEffect } from '@wordpress/element';
import type { BlockEditProps } from '@wordpress/blocks';
import { useEntityProp } from '@wordpress/core-data';
import {
	useBlockProps,
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../utils/sanitize-html';
import { SectionBlockAttributes } from './types';
import { hasAttributesUsedForVariations } from '../../utils';

export function Edit( {
	attributes,
	context,
}: BlockEditProps< SectionBlockAttributes > & {
	context?: {
		selectedTab?: string | null;
	};
} ) {
	const { description, title, blockGap } = attributes;
	const tab = context?.selectedTab || '';
	const blockProps = useBlockProps();
	const [ productAttributes ] = useEntityProp< Product[ 'attributes' ] >(
		'postType',
		'product',
		'attributes'
	);
	const isDisabledSection =
		hasAttributesUsedForVariations( productAttributes ) &&
		[ 'inventory', 'pricing', 'shipping' ].includes( tab );
	const innerBlockProps = useInnerBlocksProps(
		{
			className: classNames(
				'wp-block-woocommerce-product-section__content',
				`wp-block-woocommerce-product-section__content--block-gap-${ blockGap }`,
				{
					'is-disabled-section': isDisabledSection,
				}
			),
		},
		{ templateLock: 'all' }
	);
	const SectionTagName = title ? 'fieldset' : 'div';
	const HeadingTagName = SectionTagName === 'fieldset' ? 'legend' : 'div';

	useEffect( () => {
		const elementsToReEnable: (
			| HTMLInputElement
			| HTMLButtonElement
			| HTMLSelectElement
			| HTMLTextAreaElement
		 )[] = [];

		if ( isDisabledSection ) {
			const disabledSections = document.querySelectorAll(
				'.is-disabled-section'
			);

			disabledSections.forEach( ( section ) => {
				const controls = section.querySelectorAll(
					'input, button, select, textarea'
				);

				controls.forEach( ( control ) => {
					if (
						control instanceof HTMLInputElement ||
						control instanceof HTMLButtonElement ||
						control instanceof HTMLSelectElement ||
						control instanceof HTMLTextAreaElement
					) {
						if ( ! control.disabled ) {
							elementsToReEnable.push( control );
							control.disabled = true;
						}
					}
				} );
			} );
		}
		return () => {
			elementsToReEnable.forEach( ( control ) => {
				control.disabled = false;
			} );
		};
	}, [ isDisabledSection ] );

	return (
		<SectionTagName { ...blockProps }>
			{ title && (
				<HeadingTagName
					className={ classNames(
						'wp-block-woocommerce-product-section__heading',
						{ 'is-disabled-section': isDisabledSection }
					) }
				>
					<h2 className="wp-block-woocommerce-product-section__heading-title">
						{ title }
					</h2>
					{ description && (
						<p
							className="wp-block-woocommerce-product-section__heading-description"
							dangerouslySetInnerHTML={ sanitizeHTML(
								description
							) }
						/>
					) }
				</HeadingTagName>
			) }

			<div { ...innerBlockProps } />
		</SectionTagName>
	);
}
