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
			{ showProgress && (
				<ProgressBar
					className={ 'progress-bar' }
					percent={ percentage }
					color={ 'var(--wp-admin-theme-color)' }
					bgcolor={ 'transparent' }
				/>
			) }
			<div className="wc-navigation">
				<div className="wc-navigation-col-left">
					{ showLogo && (
						<span className="woologo">
							<WooLogo />
						</span>
					) }
				</div>
				<div className="wc-navigation-col-right">
					{ typeof onSkip === 'function' && (
						<Button
							onClick={ onSkip }
							className={ classnames(
								'wc-navigation-skip-link',
								classNames.mobile ? 'mobile' : ''
							) }
							isLink
						>
							{ __(
								skipText ?? 'Skip this step',
								'woocommerce'
							) }
						</Button>
					) }
				</div>
			</div>
		</div>
	);
};

export default Navigation;
