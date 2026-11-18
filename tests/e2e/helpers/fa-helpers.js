/**
 * FA Extension Management Helpers for Playwright E2E Tests
 *
 * Provides functions to:
 *   - Log into FrontAccounting as a system administrator
 *   - Install third-party extensions via the Install/Extensions page
 *   *   - Activate extensions for a company
 *   - Grant System Administrator access to a module
 *   - Log out and log back in
 *   - Navigate to and verify module pages render
 *
 * These tests require a running FA instance at FA_BASE_URL (default http://localhost:8080)
 * and the test credentials FA_USER / FA_PASS (default opencode/opencode, company 0).
 */

const { expect } = require('@playwright/test');

const BASE_URL = process.env.FA_BASE_URL || 'http://localhost:8080';
const FA_USER = process.env.FA_USER || 'opencode';
const FA_PASS = process.env.FA_PASS || 'opencode';
const COMPANY_ID = process.env.FA_COMPANY_ID || '0';

/**
 * Log into FrontAccounting as system administrator.
 * Handles the FA 2.4.3 login form quirks:
 * - user_name_entry_field (not user_id)
 * - _token field with mixed quoting
 * - company_login_name is required
 */
async function faLogin(page) {
  await page.goto(BASE_URL + '/');
  await page.waitForSelector('input[name="user_name_entry_field"]', { timeout: 10000 });

  await page.fill('input[name="user_name_entry_field"]', FA_USER);
  await page.fill('input[name="password"]', FA_PASS);
  await page.selectOption('select[name="company_login_name"]', COMPANY_ID);

  await Promise.all([
    page.waitForNavigation({ timeout: 15000 }),
    page.click('input[type="submit"], button[type="submit"]'),
  ]);
  await page.waitForSelector('a:has-text("Logout")', { timeout: 15000 });
}

/**
 * Log out of FrontAccounting.
 */
async function faLogout(page) {
  const logoutLink = page.locator('a:has-text("Logout")');
  if (await logoutLink.count() > 0) {
    await logoutLink.click();
    await page.waitForLoadState('networkidle');
  }
}

/**
 * Navigate to the Install/Extensions admin page.
 */
async function gotoInstallExtensions(page) {
  // Navigate via Setup menu -> Install/Activate extensions
  // The URL pattern is /admin/inst_module.php
  await page.goto(BASE_URL + '/admin/inst_module.php');
  await page.waitForLoadState('networkidle');
}

/**
 * Install a third-party extension by clicking its "Local" button.
 * @param {string} packageName - The package name (e.g. 'ksf_FA_HRM_LeaveApproval')
 */
async function installExtension(page, packageName) {
  await gotoInstallExtensions(page);

  // The Local button is named 'Local.<package>'
  const localButton = page.locator(`input[name="Local.${packageName}"], button[name="Local.${packageName}"]`);
  if (await localButton.count() > 0) {
    await localButton.click();
    await page.waitForLoadState('networkidle');
    // After install, the page reloads; extension should now show as installed
    return true;
  }

  // Check if already installed (no Local button means either installed or not available)
  const updateButton = page.locator(`input[name="Update.${packageName}"], button[name="Update.${packageName}"]`);
  if (await updateButton.count() > 0) {
    // Already installed
    return true;
  }

  throw new Error(`Could not find Install button for ${packageName}`);
}

/**
 * Activate extensions for a company.
 * Navigates to the company extensions activation page, checks the Active
 * checkboxes for the given packages, and clicks Refresh to submit.
 *
 * Checkbox names are Active<i> where <i> is the row index in the merged +
 * natural-sorted GLOBAL registry list (per FA's inst_module.php logic).
 * @param {number} companyId - The company index (default 0)
 * @param {string[]} packages - Array of package names to activate
 */
async function activateExtensions(page, companyId = COMPANY_ID, packages) {
  // Navigate to the company extensions activation page
  await page.goto(BASE_URL + `/admin/inst_module.php?extset=${companyId}`);
  await page.waitForLoadState('networkidle');

  // Read the page to find Active checkbox indices for our packages.
  // Checkboxes are named Active<i> where i is the row index in the table.
  const tableRows = page.locator('table tr');
  const rowCount = await tableRows.count();

  const checkboxesToCheck = [];

  for (let i = 0; i < rowCount; i++) {
    const row = tableRows.nth(i);
    const rowText = await row.textContent();
    // Check if this row contains one of our target package names
    const match = packages.find(pkg => rowText.includes(pkg));
    if (match) {
      // Find the Active checkbox in this row
      const activeCheckbox = row.locator(`input[name="Active${i}"]`);
      if (await activeCheckbox.count() > 0) {
        const isChecked = await activeCheckbox.isChecked();
        if (!isChecked) {
          checkboxesToCheck.push(i);
        }
      }
    }
  }

  // Check all needed checkboxes
  for (const idx of checkboxesToCheck) {
    await page.check(`input[name="Active${idx}"]`);
  }

  // If we checked anything, click Refresh to submit
  if (checkboxesToCheck.length > 0) {
    await page.click('input[name="Refresh"], button[name="Refresh"]');
    await page.waitForLoadState('networkidle');

    // Verify no "incompatible version" errors
    const errorLocator = page.locator('.error, .failure').filter({ hasText: 'incompatible' });
    if (await errorLocator.count() > 0) {
      const text = await errorLocator.first().textContent();
      throw new Error(`Activation failed: ${text}`);
    }
  }
}

/**
 * Grant System Administrator access to a module's security areas.
 * Navigates to Setup -> Access and enables the module's SA_ areas for sysadmin.
 * @param {string} modulePrefix - Module prefix (e.g. 'SA_HRM_LEAVEAPPROVAL')
 */
async function grantSysAdminAccess(page, securityAreas) {
  await page.goto(BASE_URL + '/admin/user_access.php?user_id=1'); // sysadmin is typically user_id=1
  await page.waitForLoadState('networkidle');

  for (const area of securityAreas) {
    const checkbox = page.locator(`input[name="${area}"]`);
    if (await checkbox.count() > 0) {
      await checkbox.check();
    }
  }

  await page.click('input[type="submit"], button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

/**
 * Navigate to a module page and verify it renders (HTTP 200, expected content).
 * @param {string} pagePath - Relative path from module root (e.g. 'index.php?view=list')
 * @param {string} expectedText - Text expected to appear on the page
 * @param {Object} options - { moduleName, securityAreas }
 */
async function verifyModulePage(page, modulePath, expectedText, options = {}) {
  const url = BASE_URL + '/modules/' + options.moduleName + '/' + modulePath;
  const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });

  if (response) {
    const status = response.status();
    expect(status).toBe(200);
  }

  const body = await page.textContent('body');
  expect(body).toContain(expectedText);
}

module.exports = { faLogin, faLogout, gotoInstallExtensions, installExtension, activateExtensions, grantSysAdminAccess, verifyModulePage, BASE_URL, FA_USER, FA_PASS, COMPANY_ID };
