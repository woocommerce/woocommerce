/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { recordEvent } from '@woocommerce/tracks';
import { ReactNode } from 'react';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ToggleBlockAttributes } from './types';
import { sanitizeHTML } from '../../../utils/sanitize-html';
import { ProductEditorBlockEditProps } from '../../../types';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { TRACKS_SOURCE } from '../../../constants';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< ToggleBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const {
		_templateBlockId,
		label,
		property,
		disabled,
		disabledCopy,
		checkedValue,
		uncheckedValue,
	} = attributes;
	const [ value, setValue ] = useProductEntityProp< boolean >( property, {
		postType,
		fallbackValue: false,
	} );
	const productId = useEntityId( 'postType', postType );
	const [ parentId ] = useEntityProp< number >(
		'postType',
		postType,
		'parent_id'
	);

	function isChecked() {
		if ( checkedValue !== undefined ) {
			return checkedValue === value;
		}
		return value as boolean;
	}

	function handleChange( checked: boolean ) {
		recordEvent( 'product_toggle_click', {
			block_id: _templateBlockId,
			source: TRACKS_SOURCE,
			product_id: parentId > 0 ? parentId : productId,
		} );
		if ( checked ) {
			setValue( checkedValue !== undefined ? checkedValue : checked );
		} else {
			setValue( uncheckedValue !== undefined ? uncheckedValue : checked );
		}
	}

	let help: ReactNode = null;

	// Default help text.
	if ( attributes?.help ) {
		help = createElement( 'div', {
			dangerouslySetInnerHTML: {
				__html: sanitizeHTML( attributes.help )?.__html,
			},
		} );
	}

	/*
	 * Redefine the help text when:
	 * - The checked help text is defined
	 * - The toggle is checked
	 */
	if ( attributes?.checkedHelp && isChecked() ) {
		help = createElement( 'div', {
			dangerouslySetInnerHTML: {
				__html: sanitizeHTML( attributes.checkedHelp )?.__html,
			},
		} );
	}

	/*
	 * Redefine the help text when:
	 * - The unchecked help text is defined
	 * - The toggle is unchecked
	 */
	if ( attributes?.uncheckedHelp && ! isChecked() ) {
		help = createElement( 'div', {
			dangerouslySetInnerHTML: {
				__html: sanitizeHTML( attributes.uncheckedHelp )?.__html,
			},
		} );
	}

	return (
		<div { ...blockProps }>
			<ToggleControl
				label={ label }
				checked={ isChecked() }
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore disabled prop exists
				disabled={ disabled }
				onChange={ handleChange }
				help={ help }
			/>
			{ disabled && (
				<p
					className="wp-block-woocommerce-product-toggle__disable-copy"
					dangerouslySetInnerHTML={ sanitizeHTML( disabledCopy ) }
				/>
			) }
		</div>
	);
}
