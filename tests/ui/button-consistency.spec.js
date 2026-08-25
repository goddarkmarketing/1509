// @ts-check
const { test, expect } = require('@playwright/test');
const { loginAdmin } = require('../helpers/auth');
const { PUBLIC_PAGES, ADMIN_PAGES } = require('../helpers/pages');
const { assertUniformHeights, collectHeights } = require('../helpers/layout');

test.describe('ความสูงปุ่มสม่ำเสมอ — หน้า Public', () => {
  for (const pageInfo of PUBLIC_PAGES) {
    test(`${pageInfo.name}: ปุ่มในกลุ่มเดียวกันสูงเท่ากัน`, async ({ page }) => {
      const response = await page.goto(pageInfo.path);
      test.skip(!response || !response.ok(), `โหลด ${pageInfo.path} ไม่สำเร็จ`);

      const checks = [
        { container: '.filter-tools-right', children: ['.btn', '.form-control', 'button'] },
        { container: '.home-cta-actions', children: ['.btn', 'a.btn'] },
        { container: '.faq-page-cta-actions', children: ['.btn', 'a.btn'] },
        { container: '.auth-card form', children: ['.btn', 'button[type="submit"]'] },
        { container: '.admin-shortcuts', children: ['.btn', 'a.btn'] },
      ];

      for (const check of checks) {
        const heights = await collectHeights(page, check.container, check.children);
        if (!heights || heights.length < 2) continue;
        const result = assertUniformHeights(heights, 2);
        expect.soft(result.ok, `${check.container} สูงไม่เท่ากัน: ${result.heights.join(', ')}px (Δ${result.delta}px)`).toBeTruthy();
      }
    });
  }

  test('หน้าคอร์ส: ปุ่มกรองกับ select สูงเท่ากัน', async ({ page }) => {
    await page.goto('public/courses.php');
    const heights = await collectHeights(page, '.filter-tools-right', ['.btn', '.form-control', 'select']);
    test.skip(!heights || heights.length < 2, 'ไม่พบแถบกรอง');
    const result = assertUniformHeights(heights, 2);
    expect(result.ok, `filter-tools-right: ${result.heights.join(', ')}px`).toBeTruthy();
  });
});

test.describe('ความสูงปุ่มสม่ำเสมอ — หน้า Admin', () => {
  test.beforeEach(async ({ page }) => {
    await loginAdmin(page);
  });

  for (const pageInfo of ADMIN_PAGES) {
    test(`${pageInfo.name}: ปุ่มใน card header / toolbar สูงเท่ากัน`, async ({ page }) => {
      const response = await page.goto(pageInfo.path);
      test.skip(!response || !response.ok(), `โหลด ${pageInfo.path} ไม่สำเร็จ`);

      const checks = [
        { container: '.admin-filter-bar', children: ['.btn', '.form-control', 'select', 'button'] },
        { container: '.admin-card-header', children: ['.btn', 'a.btn', 'button'] },
        { container: '.admin-shortcuts', children: ['.btn', 'a.btn'] },
      ];

      for (const check of checks) {
        const heights = await collectHeights(page, check.container, check.children);
        if (!heights || heights.length < 2) continue;
        const result = assertUniformHeights(heights, 2);
        expect.soft(result.ok, `${pageInfo.name} ${check.container}: ${result.heights.join(', ')}px (Δ${result.delta}px)`).toBeTruthy();
      }
    });
  }

  test('บทเรียน: ปุ่มกรองกับ dropdown สูงเท่ากัน', async ({ page }) => {
    await page.goto('admin/lessons.php');
    const heights = await collectHeights(page, '.admin-filter-bar', ['.btn', '.form-control', 'select']);
    test.skip(!heights || heights.length < 2, 'ไม่พบแถบกรองบทเรียน');
    const result = assertUniformHeights(heights, 2);
    expect(result.ok, `admin-filter-bar: ${result.heights.join(', ')}px`).toBeTruthy();
  });
});
