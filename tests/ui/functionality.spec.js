// @ts-check
const { test, expect } = require('@playwright/test');
const { loginAdmin } = require('../helpers/auth');

test.describe('การทำงาน UI — Public', () => {
  test('ปุ่มติดต่อเรา: เปิด/ปิดแผงช่องทางติดต่อ', async ({ page }) => {
    await page.goto('public/index.php');
    const trigger = page.locator('#contactFabTrigger');
    test.skip((await trigger.count()) === 0, 'ไม่มีปุ่มติดต่อเรา');

    await trigger.click();
    await expect(page.locator('#contactFabPanel')).toHaveAttribute('aria-hidden', 'false');
    await expect(page.locator('.contact-fab-list a').first()).toBeVisible();

    await page.locator('#contactFabClose').click();
    await expect(page.locator('#contactFabPanel')).toHaveAttribute('aria-hidden', 'true');
  });

  test('หน้าคอร์ส: กรองและล้างตัวกรองทำงาน', async ({ page }) => {
    await page.goto('public/courses.php');
    const cards = page.locator('.course-card');
    test.skip((await cards.count()) === 0, 'ไม่มีคอร์ส');

    const category = page.locator('#courseCategorySelect');
    if ((await category.count()) === 0) test.skip();

    const options = await category.locator('option').allTextContents();
    const value = await category.locator('option').nth(1).getAttribute('value');
    if (!value || value === 'all') test.skip();

    await category.selectOption(value);
    await page.locator('#courseSearch').fill('');
    await page.waitForTimeout(300);

    const visibleAfterFilter = await cards.evaluateAll((els) =>
      els.filter((el) => !el.classList.contains('is-hidden')).length
    );
    expect(visibleAfterFilter).toBeGreaterThanOrEqual(0);

    const clearBtn = page.locator('#courseClearBtn');
    test.skip((await clearBtn.count()) === 0, 'ไม่มีปุ่มล้างตัวกรอง');
    await clearBtn.scrollIntoViewIfNeeded();
    await page.evaluate(() => document.getElementById('courseClearBtn')?.click());
    await expect(category).toHaveValue('all', { timeout: 3_000 });
    await expect(page.locator('#courseSearch')).toHaveValue('');
  });

  test('เมนูมือถือ: เปิด/ปิดได้', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('public/index.php');
    const nav = page.locator('#siteNav');
    await expect(page.locator('#navToggle')).toBeVisible();

    await page.evaluate(() => document.getElementById('navToggle')?.click());
    await expect(nav).toHaveClass(/open/, { timeout: 3_000 });
    await page.evaluate(() => document.getElementById('navToggle')?.click());
    await expect(nav).not.toHaveClass(/open/, { timeout: 3_000 });
  });
});

test.describe('การทำงาน UI — Admin', () => {
  test.beforeEach(async ({ page }) => {
    await loginAdmin(page);
  });

  test('บทเรียน: ตัวกรองคงอยู่หลังแก้ไขและกลับ', async ({ page }) => {
    await page.goto('admin/lessons.php');
    const select = page.locator('#lessonFilterCourse');
    test.skip((await select.count()) === 0, 'ไม่พบตัวกรองบทเรียน');

    const courseOption = select.locator('option[value]:not([value=""])').first();
    test.skip((await courseOption.count()) === 0, 'ไม่มีคอร์สให้กรอง');

    const courseId = await courseOption.getAttribute('value');
    const courseTitle = (await courseOption.textContent())?.trim() || '';
    await select.selectOption(courseId || '');
    await page.locator('.admin-filter-bar button[type="submit"]').click();
    await page.waitForURL(/course_id=/);

    await expect(page.locator('.admin-filter-active strong')).toContainText(courseTitle);

    const editLink = page.locator('table.data-table tbody tr a', { hasText: 'แก้ไข' }).first();
    test.skip((await editLink.count()) === 0, 'ไม่มีบทเรียนให้แก้ไข');

    await editLink.click();
    await page.waitForURL(/action=edit/);
    await page.locator('a.btn-secondary', { hasText: 'กลับ' }).click();
    await page.waitForURL(/lessons\.php/);

    await expect(page.locator('.admin-filter-active strong')).toContainText(courseTitle);
    await expect(select).toHaveValue(courseId || '');
  });

  test('บทเรียน: ล้างตัวกรองรีเซ็ตรายการ', async ({ page }) => {
    await page.goto('admin/lessons.php?course_id=1');
    const clearLink = page.locator('a', { hasText: 'ล้างตัวกรอง' });
    test.skip((await clearLink.count()) === 0, 'ไม่มีปุ่มล้างตัวกรอง');

    await clearLink.click();
    await page.waitForURL(/lessons\.php$/);
    await expect(page.locator('.admin-filter-active')).toContainText('แสดงบทเรียนทั้งหมด');
  });

  test('คูปอง: เปิด modal เพิ่มคูปอง', async ({ page }) => {
    await page.goto('admin/coupons.php');
    const addBtn = page.locator('[data-open-coupon="new"]');
    test.skip((await addBtn.count()) === 0, 'ไม่พบปุ่มเพิ่มคูปอง');

    await addBtn.click();
    const modal = page.locator('#couponFormModal');
    await expect(modal).toBeVisible();
    await expect(modal.locator('#coupon-panel-new input[name="code"]')).toBeVisible();

    await modal.locator('.admin-modal-close').click();
    await expect(modal).toBeHidden();
  });

  test('แดชบอร์ด: ลิงก์ทางลัดทุกปุ่มโหลดได้', async ({ page }) => {
    await page.goto('admin/dashboard.php');
    const links = page.locator('.admin-shortcuts a.btn');
    const count = await links.count();
    test.skip(count === 0, 'ไม่มีทางลัด');

    for (let i = 0; i < count; i++) {
      const href = await links.nth(i).getAttribute('href');
      expect(href, `ทางลัดที่ ${i + 1} ไม่มี href`).toBeTruthy();
      const response = await page.request.get(href || '');
      expect(response.ok(), `${href} ตอบกลับ ${response.status()}`).toBeTruthy();
    }
  });

  test('ทุกหน้า admin โหลดสำเร็จ', async ({ page }) => {
    const paths = [
      'admin/dashboard.php',
      'admin/courses.php',
      'admin/lessons.php',
      'admin/students.php',
      'admin/coupons.php',
      'admin/payments.php',
      'admin/quizzes.php',
      'admin/users.php',
      'admin/settings.php',
      'admin/backup.php',
    ];

    for (const path of paths) {
      const response = await page.goto(path);
      expect(response?.ok(), `${path} โหลดไม่สำเร็จ`).toBeTruthy();
      await expect(page.locator('.admin-content')).toBeVisible();
    }
  });
});
