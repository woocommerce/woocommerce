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
import './simple-navigation.scss';

export default function SimpleNavigation( {
	actionLabel,
	actionHref,
	backHref,
	nextHref,
	className,
	...props
}: SimpleNavigationProps ) {
	const backNavigationProps = {
		className: 'simple-navigation__back',
		'aria-label': __( 'Back', 'woocommerce' ),
		children: <Icon icon={ arrowLeft } size={ 24 } fill="currentColor" />,
	};

	const nextNavigationProps = {
		className: 'simple-navigation__next',
		'aria-label': __( 'Next', 'woocommerce' ),
		children: <Icon icon={ arrowRight } size={ 24 } fill="currentColor" />,
	};

	return (
		<nav
			{ ...props }
			className={ classNames( className, 'simple-navigation' ) }
		>
			{ backHref ? (
				<Link
					{ ...backNavigationProps }
					type="wc-admin"
					href={ backHref }
				/>
			) : (
				<div
					{ ...backNavigationProps }
					tabIndex={ -1 }
					role="button"
					aria-disabled="true"
				/>
			) }

			<Link
				className="simple-navigation__action"
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

export type SimpleNavigationProps = React.DetailedHTMLProps<
	React.HTMLAttributes< HTMLElement >,
	HTMLElement
> & {
	actionLabel: string;
	actionHref: string;
	backHref?: string;
	nextHref?: string;
};
