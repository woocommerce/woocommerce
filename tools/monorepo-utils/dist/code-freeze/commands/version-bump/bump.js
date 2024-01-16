"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.bumpFiles = void 0;
/**
 * Internal dependencies
 */
const validate_1 = require("./lib/validate");
const update_1 = require("./lib/update");
const bumpFiles = (tmpRepoPath, version) => __awaiter(void 0, void 0, void 0, function* () {
    let nextVersion = version;
    yield (0, update_1.updatePluginFile)(tmpRepoPath, nextVersion);
    // Any updated files besides the plugin file get a version stripped of prerelease parameters.
    nextVersion = (0, validate_1.stripPrereleaseParameters)(nextVersion);
    // Bumping the dev version means updating the readme's changelog.
    yield (0, update_1.updateReadmeChangelog)(tmpRepoPath, nextVersion);
    yield (0, update_1.updateJSON)('composer', tmpRepoPath, nextVersion);
    yield (0, update_1.updateJSON)('package', tmpRepoPath, nextVersion);
    yield (0, update_1.updateClassPluginFile)(tmpRepoPath, nextVersion);
});
exports.bumpFiles = bumpFiles;
