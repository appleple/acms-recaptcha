/**
 * 配布バージョン作成プログラム
 */

const fs = require('fs-extra');
const co = require('co');
const { zipPromise } = require('./lib/system.js');

const ignores = [
  'vendor',
  'composer.json',
  'composer.lock',
];

co(function* () {
  try {
    /**
     * ready plugins files
     */
    const copyFiles = fs.readdirSync('src');
    fs.mkdirsSync('ReCaptcha');
    fs.mkdirsSync('build');

    /**
     * copy root files
     */
    ['images', 'README.md', 'LICENSE'].forEach((name) => {
      if (fs.existsSync(name)) {
        fs.copySync(name, `ReCaptcha/${name}`);
      }
    });

    /**
     * copy plugins files
     */
    copyFiles.forEach((file) => {
      fs.copySync(`src/${file}`, `ReCaptcha/${file}`);
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
