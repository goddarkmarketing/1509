// @ts-check

/**
 * @param {number[]} heights
 * @param {number} [tolerance=2]
 */
function assertUniformHeights(heights, tolerance = 2) {
  const visible = heights.filter((h) => h > 0);
  if (visible.length < 2) {
    return { skipped: true, heights: visible };
  }
  const min = Math.min(...visible);
  const max = Math.max(...visible);
  return {
    skipped: false,
    heights: visible,
    min,
    max,
    delta: max - min,
    ok: max - min <= tolerance,
  };
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} containerSelector
 * @param {string[]} childSelectors
 */
async function collectHeights(page, containerSelector, childSelectors) {
  return page.evaluate(
    ({ container, children }) => {
      const root = document.querySelector(container);
      if (!root) return null;
      const heights = [];
      for (const selector of children) {
        root.querySelectorAll(selector).forEach((el) => {
          const rect = el.getBoundingClientRect();
          if (rect.width > 0 && rect.height > 0) {
            heights.push(Math.round(rect.height));
          }
        });
      }
      return heights;
    },
    { container: containerSelector, children: childSelectors }
  );
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 */
async function readGridGaps(page, selector) {
  return page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return null;
    const style = getComputedStyle(el);
    return {
      rowGap: style.rowGap,
      columnGap: style.columnGap,
      gap: style.gap,
    };
  }, selector);
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} containerSelector
 */
async function readFlexGap(page, containerSelector) {
  return page.evaluate((sel) => {
    const el = document.querySelector(sel);
    if (!el) return null;
    const style = getComputedStyle(el);
    return style.gap || style.columnGap;
  }, containerSelector);
}

/**
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 */
async function countVisible(page, selector) {
  return page.locator(selector).evaluateAll((els) =>
    els.filter((el) => {
      const rect = el.getBoundingClientRect();
      const style = getComputedStyle(el);
      return rect.width > 0 && rect.height > 0 && style.visibility !== 'hidden' && style.display !== 'none';
    }).length
  );
}

module.exports = {
  assertUniformHeights,
  collectHeights,
  readGridGaps,
  readFlexGap,
  countVisible,
};
