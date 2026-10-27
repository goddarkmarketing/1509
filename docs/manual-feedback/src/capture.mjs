import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.resolve(__dirname, '..');
const pages = [
  '00-review-link',
  '01-preview-mode',
  '02-select-and-form',
  '03-admin-manage',
];

const browser = await chromium.launch();
const page = await browser.newPage({
  viewport: { width: 1360, height: 860 },
  deviceScaleFactor: 2,
});

for (const name of pages) {
  const url = `http://127.0.0.1:8765/${name}.html`;
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.evaluate(async () => {
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
  });
  await page.waitForTimeout(1200);
  const el = page.locator('#capture');
  await el.waitFor({ state: 'visible' });
  const out = path.join(outDir, `${name}.png`);
  await el.screenshot({ path: out, type: 'png' });
  console.log('saved', out);
}

await browser.close();
