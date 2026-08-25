// @ts-check

/**
 * @param {import('@playwright/test').Page} page
 * @param {{ username?: string; password?: string }} [credentials]
 */
async function loginAdmin(page, credentials = {}) {
  const username = credentials.username || process.env.LMS_ADMIN_USER || 'admin';
  const password = credentials.password || process.env.LMS_ADMIN_PASS || 'admin123';

  await page.goto('admin/login.php');
  await page.locator('input[name="username"]').fill(username);
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/admin\/dashboard\.php/, { timeout: 15_000 });
}

module.exports = { loginAdmin };
