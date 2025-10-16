/**
 * 配布バージョン作成プログラム
 */

const fs = require('fs-extra');
const co = require('co');
const { zipPromise } = require('./lib/system.js');

const ignores = [
  '.git',
  '.gitignore',
  '.gitattributes',
  'node_modules',
  '.editorconfig',
  '.eslintrc.js',
  '.node-version',
  '.husky',
  'build',
  '.prettierrc.js',
  'composer.json',
  'composer.lock',
  'package-lock.json',
  'package.json',
  'phpcs.xml',
  'phpmd.xml',
  '.phplint-cache',
  'phpmd.log',
  'tools',
];

co(function* () {
  try {
    /**
     * ready plugins files
     */
    const copyFiles = fs.readdirSync('.');
    fs.mkdirsSync('ReCaptcha');
    fs.mkdirsSync('build');

    /**
     * copy plugins files
     */
    copyFiles.forEach((file) => {
      fs.copySync(`./${file}`, `ReCaptcha/${file}`);
    });

    /**
     * Ignore files
     */
    console.log('Remove unused files.');
    console.log(ignores);
    ignores.forEach((path) => {
      fs.removeSync(`ReCaptcha/${path}`);
    });

    yield zipPromise('ReCaptcha', `./build/ReCaptcha.zip`);
  } catch (err) {
    console.log(err);
  } finally {
    fs.removeSync('ReCaptcha');
  }
});
