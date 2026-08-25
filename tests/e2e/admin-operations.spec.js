// @ts-check
const { test, expect } = require('@playwright/test');
const { loginAdmin } = require('../helpers/auth');

test.describe('ผู้ดูแล — จัดการรายการชำระเงินและนักเรียน', () => {
  test.beforeEach(async ({ page }) => {
    await loginAdmin(page);
  });

  test('หน้าชำระเงิน: โหลดรายการและมีปุ่มยืนยัน/ปฏิเสธสำหรับรายการรอตรวจสอบ', async ({ page }) => {
    await page.goto('admin/payments.php');
    await expect(page.locator('.admin-card-header h2')).toContainText('รายการแจ้งชำระเงิน');

    const pendingRow = page.locator('table.payments-table tbody tr', { has: page.locator('.badge-pending') }).first();
    if ((await pendingRow.count()) === 0) {
      test.skip(true, 'ไม่มีรายการรอตรวจสอบ — รัน e2e student-admin-flow ก่อน');
    }

    await expect(pendingRow.locator('button[name="status"][value="verified"]')).toBeVisible();
    await expect(pendingRow.locator('button[name="status"][value="rejected"]')).toBeVisible();
  });

  test('หน้านักเรียน: เปิด modal จัดการสิทธิ์ได้', async ({ page }) => {
    await page.goto('admin/students.php');
    const manageBtn = page.locator('[data-open-student]').first();
    test.skip((await manageBtn.count()) === 0, 'ไม่มีนักเรียนในระบบ');

    await manageBtn.click();
    await expect(page.locator('#studentManageModal')).toBeVisible();
    const activePanel = page.locator('.student-modal-panel:not([hidden])');
    await expect(activePanel).toBeVisible();
    await expect(activePanel.locator('.student-enroll-form select[name="course_id"]')).toBeVisible();
  });

  test('หน้าการจอง: โหลดได้', async ({ page }) => {
    const response = await page.goto('admin/bookings.php');
    expect(response?.ok()).toBeTruthy();
    await expect(page.locator('.admin-content')).toBeVisible();
  });

  test('หน้าคอร์ส: ปิดใช้งานแล้วไม่แสดงใน catalog', async ({ page }) => {
    await page.goto('admin/courses.php');
    const liveRow = page.locator('table.data-table tbody tr', { hasText: 'Live ทดสอบระบบจอง' }).first();
    test.skip((await liveRow.count()) === 0, 'ไม่มีคอร์ส Live demo');

    await liveRow.locator('a', { hasText: 'แก้ไข' }).click();
    await page.locator('input[name="is_active"][type="checkbox"]').setChecked(false);
    await page.locator('form.modal-form button[type="submit"]').click();
    await expect(page.locator('.alert-success')).toBeVisible({ timeout: 10_000 });

    await page.goto('public/courses.php');
    await expect(page.locator('.course-card', { hasText: 'Live ทดสอบระบบจอง' })).toHaveCount(0);

    // restore for other tests
    await loginAdmin(page);
    await page.goto('admin/courses.php');
    const restoreRow = page.locator('table.data-table tbody tr', { hasText: 'Live ทดสอบระบบจอง' }).first();
    await restoreRow.locator('a', { hasText: 'แก้ไข' }).click();
    await page.locator('input[name="is_active"][type="checkbox"]').setChecked(true);
    await page.locator('form.modal-form button[type="submit"]').click();
    await expect(page.locator('.alert-success')).toBeVisible({ timeout: 10_000 });
  });

  test('หน้ารอบเรียน Live: เพิ่มรอบเรียนได้', async ({ page }) => {
    await page.goto('admin/sessions.php?action=add');
    const migrationWarning = page.locator('.alert-warning, .alert-error');
    if ((await migrationWarning.count()) > 0) {
      const text = await migrationWarning.first().textContent();
      test.skip(true, text || 'schema ยังไม่พร้อม — รัน migration phase 9');
    }

    const courseSelect = page.locator('select[name="course_id"]');
    await expect(courseSelect).toBeVisible();
    const options = courseSelect.locator('option[value]:not([value=""])');
    test.skip((await options.count()) === 0, 'ไม่มีคอร์สในระบบ');

    const firstValue = await options.first().getAttribute('value');
    await courseSelect.selectOption(firstValue || '');

    const starts = new Date(Date.now() + 4 * 24 * 60 * 60 * 1000);
    starts.setHours(10, 0, 0, 0);
    const ends = new Date(starts.getTime() + 90 * 60 * 1000);
    const toLocal = (/** @type {Date} */ d) => {
      const p = (/** @type {number} */ n) => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
    };

    await page.locator('input[name="title"]').fill(`E2E Session ${Date.now()}`);
    await page.locator('input[name="starts_at"]').fill(toLocal(starts));
    await page.locator('input[name="ends_at"]').fill(toLocal(ends));
    await page.locator('button[type="submit"]').click();

    await page.waitForURL(/\/admin\/sessions\.php/, { timeout: 15_000 });
    await expect(page.locator('.alert-success, .alert-error').first()).toBeVisible();
    await expect(page.locator('.alert-error')).toHaveCount(0);
    await expect(page.locator('.alert-success')).toContainText(/เพิ่มรอบเรียนเรียบร้อย/);
  });

  test('หน้าตั้งค่า LINE OA: โหลดส่วน integration', async ({ page }) => {
    await page.goto('admin/settings.php#line-oa');
    await expect(page.locator('#line-oa')).toBeVisible();
    await expect(page.locator('input[name="line_oa_channel_secret"]')).toBeVisible();
    await expect(page.locator('button[name="line_test_action"]')).toBeVisible();
  });
});
