/** @format */

/**
 * External dependencies
 */

import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { select as d3Select } from 'd3-selection';
import { isEqual } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';

class D3Base extends Component {
	constructor() {
		super( ...arguments );
		this.updateParams = this.updateParams.bind( this );
		this.setNodeRef = this.setNodeRef.bind( this );
		this.state = {};
	}

	componentDidMount() {
		window.addEventListener( 'resize', this.updateParams );
		this.updateParams();
	}

	componentDidUpdate( prevProps ) {
		if ( ! isEqual( prevProps, this.props ) ) {
			this.updateParams( this.props );
			this.draw();
		}
	}

	componentWillUnmount() {
		window.removeEventListener( 'resize', this.updateParams );
		delete this.node;
	}

	updateParams( nextProps ) {
		const getParams = ( nextProps && nextProps.getParams ) || this.props.getParams;
		this.setState( getParams( this.node ), this.draw );
	}

	draw() {
		this.props.drawChart( this.createNewContext(), this.state );
	}

	createNewContext() {
		const { className } = this.props;
		const { width, height } = this.state;

		d3Select( this.node )
			.selectAll( 'svg' )
			.remove();
		d3Select( this.node )
			.selectAll( `.${ className }__tooltip` )
			.remove();

		const newNode = d3Select( this.node );

		newNode
			.append( 'svg' )
			.attr( 'class', `${ className }__viewbox` )
			.attr( 'viewBox', `0 0 ${ width } ${ height }` )
			.attr( 'preserveAspectRatio', 'xMidYMid meet' )
			.append( 'g' );
		newNode
			.append( 'div' )
			.attr( 'class', `${ className }__tooltip tooltip` )
			.style( 'display', 'none' );

		return newNode;
	}

	setNodeRef( node ) {
		this.node = node;
	}

	render() {
		return (
			<div className={ classNames( 'd3-base', this.props.className ) } ref={ this.setNodeRef } />
		);
	}
}

D3Base.propTypes = {
	className: PropTypes.string,
	drawChart: PropTypes.func.isRequired,
	getParams: PropTypes.func.isRequired,
};

export default D3Base;
