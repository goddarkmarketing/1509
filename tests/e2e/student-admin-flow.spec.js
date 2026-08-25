// @ts-check
const { test, expect } = require('@playwright/test');
const { loginAdmin } = require('../helpers/auth');
const { uniqueStudent } = require('../helpers/testData');
const {
  registerStudent,
  loginStudent,
  addFirstRecordedCourseToCart,
  submitBankTransferCheckout,
} = require('../helpers/student');

/** @type {{ fullName: string; phone: string; email: string; password: string } | null} */
let testStudent = null;

test.describe.serial('ผู้เรียน + ผู้ดูแล — flow สมัครถึงเปิดสิทธิ์', () => {
  test('ผู้เรียน: สมัครสมาชิกและเข้าสู่ระบบได้', async ({ page }) => {
    testStudent = uniqueStudent();
    await registerStudent(page, testStudent);
    await expect(page.locator('.account-sidebar-user strong')).toContainText(testStudent.fullName);
    await expect(page.getByRole('link', { name: 'คอร์สของฉัน' })).toBeVisible();
  });

  test('ผู้เรียน: เลือกคอร์ส ใส่ตะกร้า และแจ้งโอนเงิน', async ({ page }) => {
    test.skip(!testStudent, 'ไม่มีข้อมูลนักเรียนทดสอบ');
    await loginStudent(page, testStudent);
    await addFirstRecordedCourseToCart(page);
    await page.goto('public/cart.php');
    await expect(page.locator('.checkout-order-item, .cart-page .course-card, .cart-item').first()).toBeVisible({
      timeout: 10_000,
    });

    await submitBankTransferCheckout(page);
    await expect(page.locator('.alert-success')).toContainText(/แจ้งชำระเงินเรียบร้อย/);
  });

  test('ผู้เรียน: หลังแจ้งชำระ เห็นคอร์สสถานะรอตรวจสอบ', async ({ page }) => {
    test.skip(!testStudent, 'ไม่มีข้อมูลนักเรียนทดสอบ');
    await loginStudent(page, testStudent);
    await page.goto('public/profile.php?tab=courses');
    await expect(page.locator('.my-courses-item--pending, .my-courses-badge--pending').first()).toBeVisible({
      timeout: 10_000,
    });
  });

  test('ผู้ดูแล: เห็นรายการแจ้งชำระและยืนยันได้', async ({ page }) => {
    test.skip(!testStudent, 'ไม่มีข้อมูลนักเรียนทดสอบ');
    await loginAdmin(page);
    await page.goto('admin/payments.php');
    const row = page.locator('table.payments-table tbody tr', { hasText: testStudent.phone }).first();
    await expect(row).toBeVisible({ timeout: 10_000 });
    await expect(row.locator('.badge-pending, .badge')).toContainText(/รอตรวจสอบ/);

    await row.locator('button[name="status"][value="verified"]').click();
    await page.waitForURL(/\/admin\/payments\.php/, { timeout: 10_000 });
    await expect(page.locator('.alert-success')).toContainText(/ยืนยันและเปิดสิทธิ์เรียนเรียบร้อย/);
  });

  test('ผู้ดูแล: ตรวจสอบนักเรียนในรายชื่อ', async ({ page }) => {
    test.skip(!testStudent, 'ไม่มีข้อมูลนักเรียนทดสอบ');
    await loginAdmin(page);
    await page.goto('admin/students.php');
    const row = page.locator('table.students-compact-table tbody tr', { hasText: testStudent.fullName }).first();
    await expect(row).toBeVisible({ timeout: 10_000 });
    await expect(row.locator('.students-status-badge')).not.toContainText('ยังไม่มีคอร์ส');
  });

  test('ผู้เรียน: หลัง admin ยืนยัน เห็นคอร์สเปิดสิทธิ์แล้ว', async ({ page }) => {
    test.skip(!testStudent, 'ไม่มีข้อมูลนักเรียนทดสอบ');
    await loginStudent(page, testStudent);
    await page.goto('public/profile.php?tab=courses');
    await expect(page.locator('.my-courses-badge--active').first()).toContainText(/เปิดสิทธิ์แล้ว/, {
      timeout: 10_000,
    });
    await expect(page.locator('a.btn-primary', { hasText: /เริ่มเรียน|ทบทวน/ }).first()).toBeVisible();
  });

  test('ผู้เรียน: แก้ไขโปรไฟล์ได้', async ({ page }) => {
    test.skip(!testStudent, 'ไม่มีข้อมูลนักเรียนทดสอบ');
    await loginStudent(page, testStudent);
    await page.goto('public/profile.php?tab=profile');
    const updatedName = `${testStudent.fullName} (อัปเดต)`;
    await page.locator('input[name="full_name"]').fill(updatedName);
    await page.locator('button[type="submit"]', { hasText: 'บันทึก' }).click();
    await page.waitForURL(/tab=profile/, { timeout: 10_000 });
    await expect(page.locator('.alert-success')).toBeVisible();
    await expect(page.locator('input[name="full_name"]')).toHaveValue(updatedName);
    testStudent.fullName = updatedName;
  });
});
