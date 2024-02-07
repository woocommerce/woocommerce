/**
 * External dependencies
 */
import classNames from 'classnames';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { __experimentalTooltip as Tooltip } from '@woocommerce/components';
import {
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { BlockSlot } from '../../../components/block-slot-fill';
import { sanitizeHTML } from '../../../utils/sanitize-html';
import { SectionBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';

export function SectionBlockEdit( {
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
	const tooltipClassName = `wp-block-woocommerce-product-section__heading-tooltip`;

	return (
		<SectionTagName { ...blockProps }>
			{ title && (
				<HeadingTagName className="wp-block-woocommerce-product-section__heading">
					<div className="wp-block-woocommerce-product-section__heading-title-wrapper">
						<h2 className="wp-block-woocommerce-product-section__heading-title">
							{ title }
							{ description && (
								<Tooltip
									className={ tooltipClassName }
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

						<div className="wp-block-woocommerce-product-section__actions">
							<BlockSlot name="section-actions" />
						</div>
					</div>

					<BlockSlot name="section-description" />
				</HeadingTagName>
			) }

			<div { ...innerBlockProps } />
		</SectionTagName>
	);
}
