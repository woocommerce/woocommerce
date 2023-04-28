/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';
import { CoreProfilerStateMachineContext } from '../..';

type Props = {
	title: string | JSX.Element;
	progress?: number;
	className?: string;
	paragraphs?: Array< {
		text: string | JSX.Element;
		duration?: number;
	} >;
};

export const Loader = ( {
	context,
}: {
	context: CoreProfilerStateMachineContext;
} ) => {
	const stage = context.loader.stages[ context.loader.currentStage ?? 0 ];
	return (
		<div
			className={ classNames(
				'woocommerce-profiler-loader',
				stage.className
			) }
		>
			<h1 className="woocommerce-profiler-loader__title">
				{ stage.title }
			</h1>
		</div>
	);
};
