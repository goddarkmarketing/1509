// @ts-check

/**
 * @param {import('@playwright/test').Page} page
 * @param {{ fullName: string; phone: string; email: string; password: string }} student
 */
async function registerStudent(page, student) {
  await page.goto('public/register.php');
  await page.locator('#register-full-name').fill(student.fullName);
  await page.locator('#register-phone').fill(student.phone);
  await page.locator('#register-email').fill(student.email);
  await page.locator('#register-password').fill(student.password);
  await page.locator('#register-password-confirm').fill(student.password);
  await page.locator('input[name="accept_terms"]').check();
  await page.locator('.auth-register-submit').click();
  await page.waitForURL(/\/public\/profile\.php/, { timeout: 15_000 });
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {{ phone: string; password: string; email?: string }} credentials
 */
async function loginStudent(page, credentials) {
  await page.goto('public/login.php');
  await page.locator('input[name="identifier"]').fill(credentials.email || credentials.phone);
  await page.locator('input[name="password"]').fill(credentials.password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/public\/(profile|my-courses)\.php/, { timeout: 15_000 });
}

/**
 * @param {import('@playwright/test').Page} page
 */
async function addFirstRecordedCourseToCart(page) {
  await page.goto('public/courses.php');
  const card = page.locator('.course-card[data-type="recorded"], .course-card').first();
  await card.waitFor({ state: 'visible', timeout: 10_000 });

  const addLink = card.locator('a.js-cart-add, a[href*="cart_add.php"]').first();
  const href = await addLink.getAttribute('href');
  if (!href) {
    throw new Error('ไม่พบปุ่มเพิ่มลงตะกร้าบนหน้าคอร์ส');
  }
  await page.goto(href);
  await page.waitForURL(/\/public\/(courses|cart)\.php/, { timeout: 10_000 });
}

/**
 * @param {import('@playwright/test').Page} page
 */
async function submitBankTransferCheckout(page) {
  await page.goto('public/checkout.php');
  await page.locator('[data-checkout-method="transfer"]').click();
  await expectTransferFormVisible(page);

  const today = new Date().toISOString().slice(0, 10);
  await page.locator('#transfer_date').fill(today);
  await page.locator('#transfer_time').fill('14:30');
  await page.locator('#checkoutForm button[type="submit"]').click();
  await page.waitForURL(/\/public\/checkout\.php/, { timeout: 15_000 });
}

/**
 * @param {import('@playwright/test').Page} page
 */
async function expectTransferFormVisible(page) {
  await page.locator('#checkoutDetailTransfer').waitFor({ state: 'visible', timeout: 5_000 });
  await page.locator('#checkoutForm').waitFor({ state: 'visible' });
}

module.exports = {
  registerStudent,
  loginStudent,
  addFirstRecordedCourseToCart,
  submitBankTransferCheckout,
  expectTransferFormVisible,
};
