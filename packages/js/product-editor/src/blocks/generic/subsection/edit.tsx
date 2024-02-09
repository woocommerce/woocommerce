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
import { SubsectionBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';

export function SubsectionBlockEdit( {
	attributes,
}: ProductEditorBlockEditProps< SubsectionBlockAttributes > ) {
	const { description, title, blockGap } = attributes;

	const blockProps = useWooBlockProps( attributes );
	const innerBlockProps = useInnerBlocksProps(
		{
			className: classNames(
				'wp-block-woocommerce-product-subsection__content',
				`wp-block-woocommerce-product-subsection__content--block-gap-${ blockGap }`
			),
		},
		{ templateLock: 'all' }
	);
	const SubsectionTagName = title ? 'fieldset' : 'div';
	const HeadingTagName = SubsectionTagName === 'fieldset' ? 'legend' : 'div';
	const tooltipClassName = `wp-block-woocommerce-product-subsection__heading-tooltip`;

	return (
		<SubsectionTagName { ...blockProps }>
			{ title && (
				<HeadingTagName className="wp-block-woocommerce-product-subsection__heading">
					<div className="wp-block-woocommerce-product-subsection__heading-title-wrapper">
						<h2 className="wp-block-woocommerce-product-subsection__heading-title">
							{ title }
							{ description && (
								<Tooltip
									className={ tooltipClassName }
									text={
										<p
											className="wp-block-woocommerce-product-subsection__heading-description"
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

						<div className="wp-block-woocommerce-product-subsection__actions">
							<BlockSlot name="subsection-actions" />
						</div>
					</div>

					<BlockSlot name="subsection-description" />
				</HeadingTagName>
			) }

			<div { ...innerBlockProps } />
		</SubsectionTagName>
	);
}
