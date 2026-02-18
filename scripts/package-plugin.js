const fs = require('fs-extra');
const archiver = require('archiver');
const path = require('path');

const PLUGIN_DIR = 'plugin';
const OUTPUT_DIR = 'public/downloads';
const ZIP_NAME = 'revora.zip';
const ZIP_ROOT_FOLDER = 'revora';

async function packagePlugin() {
  console.log('Starting plugin packaging...');
  
  // Ensure output directory exists
  await fs.ensureDir(OUTPUT_DIR);

  const outputPath = path.join(OUTPUT_DIR, ZIP_NAME);
  const output = fs.createWriteStream(outputPath);
  const archive = archiver('zip', {
    zlib: { level: 9 } // Sets the compression level.
  });

  output.on('close', function() {
    console.log(archive.pointer() + ' total bytes');
    console.log('Plugin packaged successfully to ' + outputPath);
  });

  archive.on('warning', function(err) {
    if (err.code === 'ENOENT') {
      console.warn(err);
    } else {
      throw err;
    }
  });

  archive.on('error', function(err) {
    throw err;
  });

  archive.pipe(output);

  // Add the plugin directory contents to the zip under the ZIP_ROOT_FOLDER namespace
  archive.directory(PLUGIN_DIR, ZIP_ROOT_FOLDER);

  await archive.finalize();
}

packagePlugin().catch(err => {
  console.error('Packaging failed:', err);
  process.exit(1);
});
