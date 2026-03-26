import { mkdir, rm } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import esbuild from 'esbuild';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectDir = path.resolve(__dirname, '..');
const outputDir = path.join(projectDir, 'public', 'assets', 'build');
const entryFile = path.join(projectDir, 'public', 'assets', 'js', 'app.js');
const mode = process.argv[2] === 'prod' ? 'prod' : 'dev';

async function ensureCleanOutputDir(){
  await rm(outputDir, { recursive: true, force: true });
  await mkdir(outputDir, { recursive: true });
}

async function build(){
  await ensureCleanOutputDir();

  if(mode === 'prod'){
    await esbuild.build({
      entryPoints: [entryFile],
      outfile: path.join(outputDir, 'app.min.js'),
      bundle: true,
      minify: true,
      sourcemap: false,
      format: 'esm',
      target: ['es2020'],
      platform: 'browser',
      legalComments: 'none',
    });

    console.log('Built production asset: public/assets/build/app.min.js');
    return;
  }

  await esbuild.build({
    entryPoints: [entryFile],
    outfile: path.join(outputDir, 'app.js'),
    bundle: true,
    minify: false,
    sourcemap: true,
    format: 'esm',
    target: ['es2020'],
    platform: 'browser',
  });

  console.log('Built development asset: public/assets/build/app.js');
}

build().catch((error)=>{
  console.error(error);
  process.exitCode = 1;
});
