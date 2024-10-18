/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
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
			className={ clsx(
				'woocommerce-profiler-navigation-container',
				classNames
			) }
		>
			{ showProgress && (
				<ProgressBar
					className={ 'progress-bar' }
					percent={ percentage }
					color={ progressBarColor }
					bgcolor={ 'transparent' }
				/>
			) }
			<div className="woocommerce-profiler-navigation">
				<div className="woocommerce-profiler-navigation-col-left">
					{ showLogo && (
						<span className="woologo">
							<WooLogo />
						</span>
					) }
				</div>
				<div className="woocommerce-profiler-navigation-col-right">
					{ typeof onSkip === 'function' && (
						<Button
							onClick={ onSkip }
							className={ clsx(
								'woocommerce-profiler-navigation-skip-link',
								classNames.mobile ? 'mobile' : ''
							) }
							isLink
						>
							{ skipText }
						</Button>
					) }
				</div>
			</div>
		</div>
	);
};
