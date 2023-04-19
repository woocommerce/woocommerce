/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Button } from '@wordpress/components';
/**
 * Internal dependencies
 */
import WooLogo from './woologo';
import './navigation.scss';
import ProgressBar from '../progress-bar/progress-bar';

type NavigationProps = {
	onSkip?: () => void;
	percentage?: number;
	previous?: string;
	showProgress?: boolean;
	showLogo?: boolean;
	classNames?: { mobile?: boolean };
	skipText?: string;
<<<<<<< HEAD
	progressBarColor?: string;
};

export const Navigation = ( {
	percentage = 0,
	onSkip,
	skipText = __( 'Skip this step', 'woocommerce' ),
	showProgress = true,
	showLogo = true,
	classNames = {},
	progressBarColor = 'var(--wp-admin-theme-color)',
}: NavigationProps ) => {
	return (
		<div
			className={ classnames(
				'woocommerce-profiler-navigation-container',
				classNames
			) }
		>
=======
};

const Navigation = ( {
	percentage = 0,
	onSkip,
	skipText = __( 'Skip this step', 'woocommerce' ),
	showProgress = true,
	showLogo = true,
	classNames = {},
}: NavigationProps ) => {
	return (
		<div className={ classnames( 'wc-navigation-container', classNames ) }>
>>>>>>> 86c4dd7f82 (Add navigation and progress-bar components)
			{ showProgress && (
				<ProgressBar
					className={ 'progress-bar' }
					percent={ percentage }
<<<<<<< HEAD
					color={ progressBarColor }
					bgcolor={ 'transparent' }
				/>
			) }
			<div className="woocommerce-profiler-navigation">
				<div className="woocommerce-profiler-navigation-col-left">
=======
					color={ 'var(--wp-admin-theme-color)' }
					bgcolor={ 'transparent' }
				/>
			) }
			<div className="wc-navigation">
				<div className="wc-navigation-col-left">
>>>>>>> 86c4dd7f82 (Add navigation and progress-bar components)
					{ showLogo && (
						<span className="woologo">
							<WooLogo />
						</span>
					) }
				</div>
<<<<<<< HEAD
				<div className="woocommerce-profiler-navigation-col-right">
=======
				<div className="wc-navigation-col-right">
>>>>>>> 86c4dd7f82 (Add navigation and progress-bar components)
					{ typeof onSkip === 'function' && (
						<Button
							onClick={ onSkip }
							className={ classnames(
<<<<<<< HEAD
								'woocommerce-profiler-navigation-skip-link',
=======
								'wc-navigation-skip-link',
>>>>>>> 86c4dd7f82 (Add navigation and progress-bar components)
								classNames.mobile ? 'mobile' : ''
							) }
							isLink
						>
<<<<<<< HEAD
							{ skipText }
=======
							{ __(
								skipText ?? 'Skip this step',
								'woocommerce'
							) }
>>>>>>> 86c4dd7f82 (Add navigation and progress-bar components)
						</Button>
					) }
				</div>
			</div>
		</div>
	);
};
<<<<<<< HEAD
=======

export default Navigation;
>>>>>>> 86c4dd7f82 (Add navigation and progress-bar components)
