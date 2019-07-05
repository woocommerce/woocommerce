/** @format */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { find, get } from 'lodash';

/**
 * Internal dependencies
 */
import ComponentExample from './example';
import ComponentDocs from './docs';
import { Card, Link } from '@woocommerce/components';
import examples from './examples.json';
import './style.scss';

const camelCaseToSlug = name => {
	return name.replace( /\.?([A-Z])/g, ( x, y ) => '-' + y.toLowerCase() ).replace( /^-/, '' );
};

const getExampleData = example => {
	const componentName = get( example, 'component' );
	const filePath = get( example, 'filePath', camelCaseToSlug( componentName ) );
	const render = get( example, 'render', `My${ componentName }` );

	return {
		componentName,
		filePath,
		render,
	};
};

export default class extends Component {
	render() {
		const { params: { component } } = this.props;
		const className = classnames( 'woocommerce_devdocs', {
			'is-single': component,
			'is-list': ! component,
		} );

		let exampleList = examples;
		if ( component ) {
			const example = find( examples, ex => component === camelCaseToSlug( ex.component ) );
			exampleList = [ example ];
		}

		return (
			<div className={ className }>
				{ exampleList.map( example => {
					const { componentName, filePath, render, docPath } = getExampleData( example );
					const cardClasses = classnames(
						'woocommerce-devdocs__card',
						`woocommerce-devdocs__card--${ filePath }`,
						'woocommerce-analytics__card'
					);
					return (
						<Card
							key={ componentName }
							className={ cardClasses }
							title={
								component ? (
									componentName
								) : (
									<Link href={ `/devdocs/${ filePath }` }>{ componentName }</Link>
								)
							}
							action={ component ? <Link href={ '/devdocs' }>Full list</Link> : null }
						>
							<ComponentExample
								asyncName={ componentName }
								component={ componentName }
								filePath={ filePath }
								render={ render }
							/>

							{ component && (
								<ComponentDocs
									componentName={ componentName }
									filePath={ filePath }
									docPath={ docPath }
								/>
							) }
						</Card>
					);
				} ) }
			</div>
		);
	}
}
