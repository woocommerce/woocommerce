<?php
// Vite prototype, dev only: a React DevTools hook that exists before react-dom
// runs, so Vite's React Fast Refresh can find WordPress's React renderer.
add_action(
	'wp_default_scripts',
	function ( $scripts ) {
		// With SCRIPT_DEBUG, WordPress loads its own refresh runtime before
		// React. The hook has to exist before that one too.
		$scripts->add_inline_script(
			$scripts->query( 'wp-react-refresh-runtime' ) ? 'wp-react-refresh-runtime' : 'react',
			'if(!window.__REACT_DEVTOOLS_GLOBAL_HOOK__){(function(){var n=0;window.__REACT_DEVTOOLS_GLOBAL_HOOK__={renderers:new Map(),supportsFiber:true,inject:function(r){var id=n++;this.renderers.set(id,r);return id},onScheduleFiberRoot:function(){},onCommitFiberRoot:function(){},onCommitFiberUnmount:function(){}}})()}',
			'before'
		);
	},
	20
);
