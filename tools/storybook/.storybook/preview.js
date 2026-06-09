/**
 * Internal dependencies
 */
// Compile the package and admin app stylesheets from source using the same
// SCSS pipeline Storybook inherits from the admin webpack config (sass-loader
// + postcss + MiniCssExtractPlugin + WebpackRTLPlugin). These `src/style.scss`
// files are standalone (not imported by each package's `index.ts`), so they
// must be imported explicitly. This replaces copying pre-built `build-style`
// artifacts, keeping Storybook self-contained — no package builds required.
import '../../../packages/js/components/src/style.scss';
import '../../../packages/js/experimental/src/style.scss';
import '../../../packages/js/onboarding/src/style.scss';
import '../../../plugins/woocommerce/client/admin/client/stylesheets/_index.scss';
