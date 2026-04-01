import { mkdir, readdir, rm } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as esbuild from 'esbuild';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectDir = path.resolve(__dirname, '..');
const outputDir = path.join(projectDir, 'public', 'assets', 'build');
const jsEntryFile = path.join(projectDir, 'public', 'assets', 'js', 'app.js');
const cssEntryFile = path.join(projectDir, 'public', 'assets', 'css', 'styles.css');
const mode = process.argv[2] === 'prod' ? 'prod' : 'dev';

async function ensureCleanOutputDir(){
  await mkdir(outputDir, { recursive: true });

  const entries = await readdir(outputDir, { withFileTypes: true });
  const removableEntries = entries.filter((entry)=> entry.name !== '.gitkeep');

  await Promise.all(removableEntries.map((entry)=>{
    const entryPath = path.join(outputDir, entry.name);
    return rm(entryPath, { recursive: true, force: true });
  }));
}

async function build(){
  await ensureCleanOutputDir();

  if(mode === 'prod'){
    await Promise.all([
      esbuild.build({
        entryPoints: [jsEntryFile],
        outfile: path.join(outputDir, 'app.min.js'),
        bundle: true,
        minify: true,
        sourcemap: false,
        format: 'esm',
        target: ['es2020'],
        platform: 'browser',
        legalComments: 'none',
      }),
      esbuild.build({
        entryPoints: [cssEntryFile],
        outfile: path.join(outputDir, 'styles.min.css'),
        bundle: true,
        minify: true,
        sourcemap: false,
        legalComments: 'none',
      }),
    ]);

    console.log('Built production assets: public/assets/build/app.min.js, public/assets/build/styles.min.css');
    return;
  }

  await Promise.all([
    esbuild.build({
      entryPoints: [jsEntryFile],
      outfile: path.join(outputDir, 'app.js'),
      bundle: true,
      minify: false,
      sourcemap: true,
      format: 'esm',
      target: ['es2020'],
      platform: 'browser',
    }),
    esbuild.build({
      entryPoints: [cssEntryFile],
      outfile: path.join(outputDir, 'styles.css'),
      bundle: true,
      minify: false,
      sourcemap: true,
    }),
  ]);

  console.log('Built development assets: public/assets/build/app.js, public/assets/build/styles.css');
}

build().catch((error)=>{
  console.error(error);
  process.exitCode = 1;
});
