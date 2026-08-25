// @ts-check

/**
 * @returns {{ fullName: string; phone: string; email: string; password: string }}
 */
function uniqueStudent() {
  const suffix = Date.now().toString().slice(-8);
  return {
    fullName: `E2E นักเรียน ${suffix}`,
    phone: `08${suffix}`,
    email: `e2e${suffix}@wenxin-test.local`,
    password: 'test1234',
  };
}

module.exports = { uniqueStudent };
