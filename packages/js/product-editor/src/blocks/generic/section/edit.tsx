/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { __experimentalTooltip as Tooltip } from '@woocommerce/components';
import {
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../../utils/sanitize-html';
import { SectionBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< SectionBlockAttributes > ) {
	const { description, title, blockGap } = attributes;
	const blockProps = useWooBlockProps( attributes );
	const innerBlockProps = useInnerBlocksProps(
		{
			className: classNames(
				'wp-block-woocommerce-product-section__content',
				`wp-block-woocommerce-product-section__content--block-gap-${ blockGap }`
			),
		},
		{ templateLock: 'all' }
	);
	const SectionTagName = title ? 'fieldset' : 'div';
	const HeadingTagName = SectionTagName === 'fieldset' ? 'legend' : 'div';

	return (
		<SectionTagName { ...blockProps }>
			{ title && (
				<HeadingTagName className="wp-block-woocommerce-product-section__heading">
					<h2 className="wp-block-woocommerce-product-section__heading-title">
						{ title }
						{ description && (
							<Tooltip
								text={
									<p
										className="wp-block-woocommerce-product-section__heading-description"
										dangerouslySetInnerHTML={ sanitizeHTML(
											description
										) }
									/>
								}
								position={ 'bottom center' }
								helperText={ __(
									'View helper text',
									'woocommerce'
								) }
							/>
						) }
					</h2>
				</HeadingTagName>
			) }

			<div { ...innerBlockProps } />
		</SectionTagName>
	);
}
