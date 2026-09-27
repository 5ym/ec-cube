// quick stills for review: node shot.mjs 16x9 1.0 5.2 ...
import { chromium } from 'playwright';
import path from 'node:path';
const [fmt, ...ts] = process.argv.slice(2);
const [W, H] = fmt === '9x16' ? [1080, 1920] : [1920, 1080];
const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
const p = await b.newPage({ viewport: { width: W, height: H } });
p.on('pageerror', e => console.log('ERR', e.message));
await p.goto('file://' + path.resolve('uzone-promo.html') + '?format=' + fmt + '&capture=1');
await p.evaluate(() => document.fonts.ready);
for (const t of ts) { await p.evaluate(t => renderAt(+t), t); await p.screenshot({ path: `frames/${fmt}-${t}.png` }); }
await b.close();
