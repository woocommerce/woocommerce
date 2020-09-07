expect.extend( {
	async toRenderBlock( page, block = {} ) {
		const gutenbergNotFoundError = ( await page.content() ).match(
			/Your site doesn’t include support for/gi
		);
		if ( gutenbergNotFoundError !== null ) {
			return {
				message: () =>
					`the ${
						block.name || 'block'
					} is not registered and not loading in the editor.`,
				pass: false,
			};
		}

		const gutenbergValidationError = ( await page.content() ).match(
			/This block contains unexpected or invalid content/gi
		);
		if ( gutenbergValidationError !== null ) {
			return {
				message: () =>
					`the ${
						block.name || 'block'
					} had a validation error while trying to render.`,
				pass: false,
			};
		}

		const errorBoundary = ( await page.content() ).match(
			/There was an error whilst rendering/gi
		);
		if ( errorBoundary !== null ) {
			return {
				message: () =>
					`the ${
						block.name || 'block'
					} had a js error that was caught by our errorBoundary.`,
				pass: false,
			};
		}

		const blockElement = await page.$( block.class );
		if ( blockElement === null ) {
			return {
				message: () =>
					`the ${ block.name || 'block' } with classname \`${
						block.class
					}\` did not render.`,
				pass: false,
			};
		}

		return {
			message: () => `expected block to render without breaking.`,
			pass: true,
		};
	},
} );
