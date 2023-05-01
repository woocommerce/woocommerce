/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, arrowLeft, arrowRight } from '@wordpress/icons';
import { Link } from '@woocommerce/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './posts-navigation.scss';

export default function PostsNavigation( {
	actionLabel,
	actionHref,
	prevHref,
	prevLabel,
	nextHref,
	nextLabel,
	className,
	...props
}: PostsNavigationProps ) {
	const prevNavigationProps = {
		className: 'posts-navigation__prev',
		'aria-label': prevLabel ?? __( 'Previous post', 'woocommerce' ),
		children: <Icon icon={ arrowLeft } size={ 24 } fill="currentColor" />,
	};

	const nextNavigationProps = {
		className: 'posts-navigation__next',
		'aria-label': nextLabel ?? __( 'Next post', 'woocommerce' ),
		children: <Icon icon={ arrowRight } size={ 24 } fill="currentColor" />,
	};

	return (
		<nav
			{ ...props }
			className={ classNames( className, 'posts-navigation' ) }
		>
			{ prevHref ? (
				<Link
					{ ...prevNavigationProps }
					type="wc-admin"
					href={ prevHref }
				/>
			) : (
				<div
					{ ...prevNavigationProps }
					tabIndex={ -1 }
					role="button"
					aria-disabled="true"
				/>
			) }

			<Link
				className="posts-navigation__action"
				type="wc-admin"
				href={ actionHref }
			>
				{ actionLabel }
			</Link>

			{ nextHref ? (
				<Link
					{ ...nextNavigationProps }
					type="wc-admin"
					href={ nextHref }
				/>
			) : (
				<div
					{ ...nextNavigationProps }
					tabIndex={ -1 }
					role="button"
					aria-disabled="true"
				/>
			) }
		</nav>
	);
}

export type PostsNavigationProps = React.DetailedHTMLProps<
	React.HTMLAttributes< HTMLElement >,
	HTMLElement
> & {
	actionLabel: string;
	actionHref: string;
	prevHref?: string;
	prevLabel?: string;
	nextHref?: string;
	nextLabel?: string;
};
