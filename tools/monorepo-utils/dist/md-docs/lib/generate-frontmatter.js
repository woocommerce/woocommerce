"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.generatePostFrontMatter = void 0;
/**
 * External dependencies
 */
const gray_matter_1 = __importDefault(require("gray-matter"));
const js_yaml_1 = __importDefault(require("js-yaml"));
/**
 * Generate front-matter for supported post attributes.
 *
 * @param fileContents
 */
const generatePostFrontMatter = (fileContents, includeContent) => {
    var _a, _b;
    const allowList = [
        'post_date',
        'post_title',
        'page_template',
        'post_author',
        'post_name',
        'category_title',
        'category_slug',
        'content',
        'menu_title',
    ];
    const frontMatter = (0, gray_matter_1.default)(fileContents, {
        engines: {
            // By passing yaml.JSON_SCHEMA we disable date parsing that changes date format.
            // See https://github.com/jonschlinkert/gray-matter/issues/62#issuecomment-577628177 for more details.
            yaml: (s) => js_yaml_1.default.load(s, { schema: js_yaml_1.default.JSON_SCHEMA }),
        },
    });
    const content = frontMatter.content.split('\n');
    const headings = content.filter((line) => line.substring(0, 2) === '# ');
    const title = (_a = headings[0]) === null || _a === void 0 ? void 0 : _a.substring(2).trim();
    frontMatter.data.post_title = (_b = frontMatter.data.post_title) !== null && _b !== void 0 ? _b : title;
    if (includeContent !== null && includeContent !== void 0 ? includeContent : false) {
        frontMatter.data.content = frontMatter.content;
    }
    return Object.keys(frontMatter.data)
        .filter((key) => allowList.includes(key))
        .reduce((obj, key) => {
        obj[key] = frontMatter.data[key];
        return obj;
    }, {});
};
exports.generatePostFrontMatter = generatePostFrontMatter;
