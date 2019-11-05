/**
 * Show text based content, limited to a number of lines, with a read more link.
 *
 * Based on https://github.com/zoltantothcom/react-clamp-lines.
 */
import React, { createRef, Component } from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { clampLines } from './utils';

class ReadMore extends Component {
	constructor( props ) {
		super( ...arguments );

		this.state = {
			/**
			 * This is true when read more has been pressed and the full review is shown.
			 */
			isExpanded: false,
			/**
			 * True if we are clamping content. False if the review is short. Null during init.
			 */
			clampEnabled: null,
			/**
			 * Content is passed in via children.
			 */
			content: props.children,
			/**
			 * Summary content generated from content HTML.
			 */
			summary: '.',
		};

		this.reviewSummary = createRef();
		this.reviewContent = createRef();
		this.getButton = this.getButton.bind( this );
		this.onClick = this.onClick.bind( this );
	}

	componentDidMount() {
		if ( this.props.children ) {
			const { maxLines, ellipsis } = this.props;

			const lineHeight = this.reviewSummary.current.clientHeight + 1;
			const reviewHeight = this.reviewContent.current.clientHeight + 1;
			const maxHeight = ( lineHeight * maxLines ) + 1;
			const clampEnabled = reviewHeight > maxHeight;

			this.setState( {
				clampEnabled,
			} );

			if ( clampEnabled ) {
				this.setState( {
					summary: clampLines( this.reviewContent.current.innerHTML, this.reviewSummary.current, maxHeight, ellipsis ),
				} );
			}
		}
	}

	getButton() {
		const { isExpanded } = this.state;
		const { className, lessText, moreText } = this.props;

		const buttonText = isExpanded ? lessText : moreText;

		if ( ! buttonText ) {
			return;
		}

		return (
			<a
				href="#more"
				className={ className + '__read_more' }
				onClick={ this.onClick }
				aria-expanded={ ! isExpanded }
				role="button"
			>
				{ buttonText }
			</a>
		);
	}

	/**
	 * Handles the click event for the read more/less button.
	 *
	 * @param {obj} e event
	 */
	onClick( e ) {
		e.preventDefault();

		const { isExpanded } = this.state;

		this.setState( {
			isExpanded: ! isExpanded,
		} );
	}

	render() {
		const { className } = this.props;
		const { content, summary, clampEnabled, isExpanded } = this.state;

		if ( ! content ) {
			return null;
		}

		if ( false === clampEnabled ) {
			return (
				<div className={ className }>
					<div ref={ this.reviewContent }>
						{ content }
					</div>
				</div>
			);
		}

		return (
			<div className={ className }>
				{ ( ! isExpanded || null === clampEnabled ) && (
					<div
						ref={ this.reviewSummary }
						aria-hidden={ isExpanded }
						dangerouslySetInnerHTML={ {
							__html: summary,
						} }
					/>
				) }
				{ ( isExpanded || null === clampEnabled ) && (
					<div
						ref={ this.reviewContent }
						aria-hidden={ ! isExpanded }
					>
						{ content }
					</div>
				) }
				{ this.getButton() }
			</div>
		);
	}
}

ReadMore.propTypes = {
	children: PropTypes.node.isRequired,
	maxLines: PropTypes.number,
	ellipsis: PropTypes.string,
	moreText: PropTypes.string,
	lessText: PropTypes.string,
	className: PropTypes.string,
};

ReadMore.defaultProps = {
	maxLines: 3,
	ellipsis: '&hellip;',
	moreText: __( 'Read more', 'woocommerce' ),
	lessText: __( 'Read less', 'woocommerce' ),
	className: 'read-more-content',
};

export default ReadMore;
