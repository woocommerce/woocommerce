/**
 * External dependencies
 */

import { ReactNode } from 'react';
import { CheckboxControl } from '@wordpress/components';
/**
 * Internal dependencies
 */

import './extension-box.scss';

type ExtensionBoxProps = {
	// Checkbox will be hidden if true
	installed?: boolean;
	key?: string;
	icon: ReactNode;
	title: string | ReactNode;
	description: string | ReactNode;
	checked?: boolean;
	onChange?: () => void;
};

export const ExtensionBox = ( {
	installed = false,
	icon,
	title,
	onChange,
	checked = false,
	description,
}: ExtensionBoxProps ) => {
	return (
		<div className="woocommerce-profiler-plugins-plugin-box">
			<CheckboxControl
				checked={ checked }
				onChange={ onChange ? onChange : () => {} }
			/>
			{ icon }
			<div className="woocommerce-profiler-plugins-plugin-box-text">
				<h3>{ title }</h3>
				<p>{ description }</p>
			</div>
		</div>
	);
};
