// Frame-accurate export: drives window.renderAt(t) in the promo HTML and pipes
// each screenshot into ffmpeg (H.264, 30fps, yuv420p, no audio).
// Usage: node render.mjs [16x9|9x16] [fromSec] [toSec]
import { chromium } from 'playwright';
import { spawn } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const fmt = process.argv[2] || '16x9';
const [W, H] = fmt === '9x16' ? [1080, 1920] : [1920, 1080];
const FPS = 30;

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
const page = await browser.newPage({ viewport: { width: W, height: H }, deviceScaleFactor: 1 });
await page.goto('file://' + path.join(here, 'uzone-promo.html') + '?format=' + fmt + '&capture=1');
await page.evaluate(() => document.fonts.ready);
const DURATION = await page.evaluate(() => window.DURATION);
const from = Number(process.argv[3] ?? 0), to = Number(process.argv[4] ?? DURATION);
const frames = Math.round((to - from) * FPS);
const out = path.join(here, 'out', `uzone-promo-${fmt}.mp4`);

const ff = spawn('ffmpeg', ['-y', '-loglevel', 'error', '-f', 'image2pipe', '-framerate', String(FPS), '-c:v', 'png', '-i', '-',
  '-c:v', 'libx264', '-preset', 'slow', '-crf', '18', '-pix_fmt', 'yuv420p', '-r', String(FPS), '-movflags', '+faststart', '-an', out],
  { stdio: ['pipe', 'inherit', 'inherit'] });

for (let i = 0; i < frames; i++) {
  await page.evaluate((t) => window.renderAt(t), from + i / FPS);
  const buf = await page.screenshot({ type: 'png' });
  if (!ff.stdin.write(buf)) await new Promise((r) => ff.stdin.once('drain', r));
  if (i % 150 === 0) console.log(`${fmt} frame ${i}/${frames}`);
}
ff.stdin.end();
await new Promise((r) => ff.on('close', r));
await browser.close();
console.log('wrote', out);
