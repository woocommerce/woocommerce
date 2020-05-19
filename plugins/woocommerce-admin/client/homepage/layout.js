/**
 * External dependencies
 */
import { useState, useRef, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import QuickLinks from '../quick-links';
import StatsOverview from './stats-overview';
import './style.scss';

const Layout = () => {
	const [ showInbox, setShowInbox ] = useState( true );
	const [ isContentSticky, setIsContentSticky ] = useState( false );
	const content = useRef( null );
	const maybeStickContent = () => {
		if ( ! content.current ) {
			return;
		}
		const { bottom } = content.current.getBoundingClientRect();
		const shouldBeSticky = showInbox && bottom < window.innerHeight;

		setIsContentSticky( shouldBeSticky );
	};

	useEffect( () => {
		maybeStickContent();
		window.addEventListener( 'resize', maybeStickContent );

		return () => {
			window.removeEventListener( 'resize', maybeStickContent );
		};
	}, [] );

	return (
		<div
			className={ classnames( 'woocommerce-homepage', {
				hasInbox: showInbox,
			} ) }
		>
			{ showInbox && (
				<div className="woocommerce-homepage-column is-inbox">
					<div className="temp-content">
						<Button
							isPrimary
							onClick={ () => {
								setShowInbox( false );
							} }
						>
							Dismiss All
						</Button>
					</div>
					<div className="temp-content" />
					<div className="temp-content" />
					<div className="temp-content" />
					<div className="temp-content" />
					<div className="temp-content" />
					<div className="temp-content" />
				</div>
			) }
			<div
				className="woocommerce-homepage-column"
				ref={ content }
				style={ {
					position: isContentSticky ? 'sticky' : 'static',
				} }
			>
				<StatsOverview />

				<QuickLinks />
			</div>
		</div>
	);
};

export default Layout;
