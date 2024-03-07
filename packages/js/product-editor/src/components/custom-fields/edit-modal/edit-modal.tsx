/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Metadata } from '../../../types';
import type { EditModalProps } from './types';

export function EditModal( {
	initialValue,
	onUpdate,
	onCancel,
}: EditModalProps ) {
	const [ value, setValue ] = useState< Metadata< string > >( initialValue );

	function renderTitle() {
		return sprintf( __( 'Edit %s', 'woocommerce' ), value.key );
	}

	return (
		<Modal title={ renderTitle() } onRequestClose={ onCancel }>
			Hello
		</Modal>
	);
}
