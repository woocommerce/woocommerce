/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type { BlockAttributes } from '@wordpress/blocks';
import { CheckboxControl, Tooltip } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { Icon, help } from '@wordpress/icons';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const blockProps = useWooBlockProps( {
		className: 'woocommerce-product-form__checkbox',
		...attributes,
	} );
	const { property, title, label, tooltip } = attributes;
	const [ value, setValue ] = useEntityProp< boolean >(
		'postType',
		'product',
		property
	);

	return (
		<div { ...blockProps }>
			<h4>{ title }</h4>
			<div className="woocommerce-product-form__checkbox-wrapper">
				<CheckboxControl
					label={ label }
					checked={ value }
					onChange={ ( selected ) => setValue( selected ) }
				/>
				{ tooltip && (
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
							<Icon icon={ help } size={ 21.94 } fill="#949494" />
						</span>
					</Tooltip>
				) }
			</div>
		</div>
	);
}
