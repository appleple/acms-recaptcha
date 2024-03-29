const cmd = require('node-cmd');
const fs = require('fs-extra');
const co = require('co');
const archiver = require('archiver');
const pkg = fs.readJsonSync('./package.json');

/**
 * Run system command
 *
 * @param cmdString
 * @returns {Promise}
 */
const systemCmd = cmdString => {
  return new Promise((resolve) => {
    cmd.get(
      cmdString,
      (data, err, stderr) => {
        console.log(cmdString);
        console.log(data);
        if (err) {
          console.log(err);
        }
        if (stderr) {
          console.log(stderr);
        }
        resolve(data);
      }
    );
  });
}

const zipPromise = (src, dist) => {
  return new Promise((resolve, reject) => {
    const archive = archiver.create('zip', {});
    const output = fs.createWriteStream(dist);

    // listen for all archive data to be written
    output.on('close', () => {
      console.log(archive.pointer() + ' total bytes');
      console.log('Archiver has been finalized and the output file descriptor has closed.');
      resolve();
    });

    // good practice to catch this error explicitly
    archive.on('error', (err) => {
      reject(err);
    });

    archive.pipe(output);
    archive.directory(src).finalize();
  });
}

const pluginVersionUp = () => {
  const { version } = require('./package.json');
  const serviceProvider = 'ServiceProvider.php';

  try {
    let appCode = fs.readFileSync(serviceProvider, 'utf-8');
    appCode = appCode.replace(/\$version =\s*'[\d\.]+';/, `$version = '${version}';`);
    fs.writeFileSync(serviceProvider, appCode);
  } catch (err) {
    console.log(err);
  }
}

co(function* () {
  pluginVersionUp();

  try {
    fs.mkdirsSync(`ReCaptcha`);
    fs.mkdirsSync(`build`);
    fs.copySync(`./LICENSE`, `ReCaptcha/LICENSE`);
    fs.copySync(`./README.md`, `ReCaptcha/README.md`);
    fs.copySync(`./Hook.php`, `ReCaptcha/Hook.php`);
    fs.copySync('./assets', 'ReCaptcha/assets');
    fs.copySync('./template', 'ReCaptcha/template');
    fs.copySync(`./ServiceProvider.php`, `ReCaptcha/ServiceProvider.php`);
    fs.copySync(`./GET/ReCaptcha.php`, `ReCaptcha/GET/ReCaptcha.php`);
    yield zipPromise(`ReCaptcha`, `./build/recaptcha.zip`);
    fs.removeSync(`ReCaptcha`);
    yield systemCmd('git add -A');
    yield systemCmd(`git commit -m "v${pkg.version}"`);
    yield systemCmd('git push');
  } catch (err) {
    console.log(err);
  }
});
