/**
 * Tailwind configuration for ePasskeys manager UI.
 * Scoped to the `.epasskeys` container to avoid leaking styles.
 */
const path = require('path');

const root = path.resolve(__dirname, '../../../../..');
const blade = glob => path.join(__dirname, '../views', glob).replace(/\\/g, '/');

let base = {};
try {
    base = require(path.join(root, 'manager/media/style/common/tailwind.config.js'));
} catch (e) {
    console.warn('⚠  Global Tailwind config not found, using local only.');
}

module.exports = {
    content: [
        ...((base && base.content) ? base.content : []),
        blade('**/*.blade.php'),
    ],

    theme: {
        ...(base?.theme || {}),
        extend: {
            ...(base?.theme?.extend || {}),
        },
    },

    plugins: base?.plugins || [],

    corePlugins: {
        ...(base?.corePlugins || {}),
        preflight: false,
    },

    important: '.epasskeys',
};
