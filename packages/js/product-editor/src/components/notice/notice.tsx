/**
 * External dependencies
 */
import { ReactNode } from 'react';
import { createElement } from '@wordpress/element';
import classNames from 'classnames';

export type NoticeProps = {
	title?: string;
	content?: string;
	className?: string;
	type?: 'error-type' | 'success' | 'warning' | 'info';
	children?: ReactNode;
};

export function Notice( {
	title = '',
	content = '',
	className,
	type = 'info',
	children,
}: NoticeProps ) {
	return (
		<div
			className={ classNames(
				className,
				type,
				'woocommerce-product-notice'
			) }
		>
			{ title && (
				<h3 className="woocommerce-product-notice__title">{ title }</h3>
			) }
			{ content && (
				<p className="woocommerce-product-notice__content">
					{ content }
				</p>
			) }
			<div className="woocommerce-product-notice__content">
				{ children }
			</div>
		</div>
	);
}
