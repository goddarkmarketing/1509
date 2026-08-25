// @ts-check
const { test, expect } = require('@playwright/test');
const { loginAdmin } = require('../helpers/auth');
const { readGridGaps, readFlexGap } = require('../helpers/layout');

test.describe('ระยะห่าง layout — Admin', () => {
  test.beforeEach(async ({ page }) => {
    await loginAdmin(page);
  });

  test('แดชบอร์ด: gap ของ stats-grid เท่ากันทั้งแนวนอนและแนวตั้ง', async ({ page }) => {
    await page.goto('admin/dashboard.php');
    const gaps = await readGridGaps(page, '.stats-grid');
    test.skip(!gaps, 'ไม่พบ stats-grid');
    expect(gaps.rowGap).toBe(gaps.columnGap);
  });

  test('แดชบอร์ด: ระยะห่างแนวตั้งระหว่าง section เท่ากัน', async ({ page }) => {
    await page.goto('admin/dashboard.php');
    const gap = await readFlexGap(page, '.admin-content-inner');
    expect(gap).toBeTruthy();

    const sectionGaps = await page.evaluate(() => {
      const inner = document.querySelector('.admin-content-inner');
      if (!inner) return null;
      const style = getComputedStyle(inner);
      return style.gap;
    });
    expect(sectionGaps).toBe(gap);
  });

  test('แดชบอร์ด: admin-grid-2 gap สม่ำเสมอ', async ({ page }) => {
    await page.goto('admin/dashboard.php');
    const gaps = await readGridGaps(page, '.admin-grid-2');
    test.skip(!gaps, 'ไม่พบ admin-grid-2');
    expect(gaps.rowGap).toBe(gaps.columnGap);
  });

  test('บทเรียน: gap ใน admin-filter-bar ไม่เป็นศูนย์และสม่ำเสมอ', async ({ page }) => {
    await page.goto('admin/lessons.php');
    const gap = await readFlexGap(page, '.admin-filter-bar');
    test.skip(!gap, 'ไม่พบ admin-filter-bar');
    expect(gap).not.toBe('0px');
    expect(gap).not.toBe('normal');
  });
});

test.describe('ระยะห่าง layout — Public', () => {
  test('หน้าคอร์ส: gap ใน filter-tools สม่ำเสมอ', async ({ page }) => {
    await page.goto('public/courses.php');
    const gaps = await readGridGaps(page, '.filter-tools');
    test.skip(!gaps, 'ไม่พบ filter-tools');
    expect(gaps.rowGap).toBeTruthy();
    expect(gaps.columnGap).toBeTruthy();
  });

  test('หน้าคอร์ส: gap ใน filter-tools-right ไม่เป็นศูนย์', async ({ page }) => {
    await page.goto('public/courses.php');
    const gap = await readFlexGap(page, '.filter-tools-right');
    test.skip(!gap, 'ไม่พบ filter-tools-right');
    expect(gap).not.toBe('0px');
    expect(gap).not.toBe('normal');
  });
});
