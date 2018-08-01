/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import classnames from 'classnames';
import { decodeEntities } from '@wordpress/html-entities';
import { Fill } from 'react-slot-fill';
import { isArray } from 'lodash';
import Link from 'components/link';
import PropTypes from 'prop-types';
import ReactDom from 'react-dom';

/**
 * Internal dependencies
 */
import './style.scss';
import ActivityPanel from '../activity-panel';

class Header extends Component {
	constructor() {
		super();
		this.state = {
			isScrolled: false,
		};

		this.onWindowScroll = this.onWindowScroll.bind( this );
		this.updateIsScrolled = this.updateIsScrolled.bind( this );
	}

	componentDidMount() {
		this.threshold = ReactDom.findDOMNode( this ).offsetTop;
		window.addEventListener( 'scroll', this.onWindowScroll );
		this.updateIsScrolled();
	}

	componentWillUnmount() {
		window.removeEventListener( 'scroll', this.onWindowScroll );
		window.cancelAnimationFrame( this.handle );
	}

	onWindowScroll() {
		this.handle = window.requestAnimationFrame( this.updateIsScrolled );
	}

	updateIsScrolled() {
		const isScrolled = window.pageYOffset > this.threshold - 20;
		if ( isScrolled !== this.state.isScrolled ) {
			this.setState( {
				isScrolled: isScrolled,
			} );
		}
	}

	render() {
		const { sections, isEmbedded } = this.props;
		const { isScrolled } = this.state;
		const _sections = isArray( sections ) ? sections : [ sections ];

		const documentTitle = _sections
			.map( section => {
				return isArray( section ) ? section[ 1 ] : section;
			} )
			.reverse()
			.join( ' &lsaquo; ' );

		document.title = decodeEntities(
			sprintf(
				__( '%1$s &lsaquo; %2$s &#8212; WooCommerce', 'wc-admin' ),
				documentTitle,
				wcSettings.siteTitle
			)
		);

		const className = classnames( 'woocommerce-layout__header', {
			'is-scrolled': isScrolled,
		} );

		return (
			<div className={ className }>
				<h1 className="woocommerce-layout__header-breadcrumbs">
					<span>
						<Link href="/">WooCommerce</Link>
					</span>
					{ _sections.map( ( section, i ) => {
						const sectionPiece = isArray( section ) ? (
							<Link href={ section[ 0 ] } type={ isEmbedded ? 'wp-admin' : 'wc-admin' }>
								{ section[ 1 ] }
							</Link>
						) : (
							section
						);
						return <span key={ i }>{ sectionPiece }</span>;
					} ) }
				</h1>
				<ActivityPanel />
			</div>
		);
	}
}

Header.propTypes = {
	sections: PropTypes.node.isRequired,
	isEmbedded: PropTypes.bool,
};

Header.defaultProps = {
	isEmbedded: false,
};

export default function( props ) {
	return (
		<Fill name="header">
			<Header { ...props } />
		</Fill>
	);
}
