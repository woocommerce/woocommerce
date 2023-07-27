/**
 * External dependencies
 */
import classNames from 'classnames';
import { Link } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import {
	createElement,
	useState,
	createInterpolateElement,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	CheckboxControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */

import { RequirePasswordBlockAttributes } from './types';

export function Edit( {
	attributes,
}: BlockEditProps< RequirePasswordBlockAttributes > ) {
	const blockProps = useBlockProps();
	const { label, title, help } = attributes;

	const [ postPassword, setPostPassword ] = useEntityProp< string >(
		'postType',
		'product',
		'post_password'
	);

	const [ checked, setChecked ] = useState( Boolean( postPassword ) );
	return (
		<div { ...blockProps }>
			<h4>{ title }</h4>
			<CheckboxControl
				label={ label }
				checked={ checked }
				onChange={ ( selected ) => setChecked( selected ) }
			/>
			<BaseControl id="todo">
				<InputControl />
			</BaseControl>
		</div>
	);
}
