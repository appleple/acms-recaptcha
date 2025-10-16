'use strict';

const { systemCmd } = require('./lib/system.js');

(async () => {
  try {
    await systemCmd('npm ci');
  } catch (err) {
    console.log(err);
  }
})();
