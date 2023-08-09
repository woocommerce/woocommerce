/**
 * External dependencies
 */
import { ReactNode } from 'react';
import { createElement } from '@wordpress/element';
import classNames from 'classnames';

export type NoticeProps = {
	title?: string;
	description?: string;
	className?: string;
	status?: 'error' | 'success' | 'warning' | 'info';
	children?: ReactNode;
};

export function Notice( {
	title = '',
	description = '',
	className,
	status = 'info',
	children,
}: NoticeProps ) {
	return (
		<div
			className={ classNames(
				className,
				status,
				'woocommerce-product-notice'
			) }
		>
			{ title && (
				<h2 className="woocommerce-product-notice__title">{ title }</h2>
			) }
			{ description && (
				<p className="woocommerce-product-notice__description">
					{ description }
				</p>
			) }
			<div className="woocommerce-product-notice__content">
				{ children }
			</div>
		</div>
	);
}
