/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import React from 'react';

/**
 * Internal dependencies
 */
// import { Label } from '../label';

function Label( { children = null } ) {
	return <div>Label: { children }</div>;
}

export default {
	title: 'Product Editor/components/Label',
	component: Label,
};
