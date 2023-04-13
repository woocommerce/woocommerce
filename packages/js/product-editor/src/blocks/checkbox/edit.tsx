/**
 * External dependencies
 */
import { createElement, createInterpolateElement } from '@wordpress/element';
import type { BlockAttributes } from '@wordpress/blocks';
import { CheckboxControl, Tooltip } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { Icon, help } from '@wordpress/icons';

/**
 * Internal dependencies
 */

export function Edit( { attributes }: { attributes: BlockAttributes } ) {
	const blockProps = useBlockProps( {
		className: 'woocommerce-product-form__checkbox',
	} );
	const { property, title, label, tooltip } = attributes;
	const [ value, setValue ] = useEntityProp< boolean >(
		'postType',
		'product',
		property
	);

	return (
		<div { ...blockProps }>
			<h4> { title } </h4>
			<CheckboxControl
				label={
					tooltip
						? createInterpolateElement( `<label /> <tooltip />`, {
								label: <span>{ label }</span>,
								tooltip: (
									<Tooltip
										text={ <span>{ tooltip }</span> }
										position="top center"
										// eslint-disable-next-line @typescript-eslint/ban-ts-comment
										// @ts-ignore Incorrect types.
										className={
											'woocommerce-product-form__checkbox-tooltip'
										}
										delay={ 0 }
									>
										<span className="woocommerce-product-form__checkbox-tooltip-icon">
											<Icon
												icon={ help }
												size={ 20 }
												fill="#949494"
											/>
										</span>
									</Tooltip>
								),
						  } )
						: label
				}
				checked={ value }
				onChange={ ( selected ) => setValue( selected ) }
			/>
		</div>
	);
}
