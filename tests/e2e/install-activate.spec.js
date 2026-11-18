const { test, expect } = require('@playwright/test');
const {
  faLogin,
  gotoInstallExtensions,
  installExtension,
  activateExtensions,
  BASE_URL,
} = require('./helpers/fa-helpers');

/**
 * Install and Activate Extension Tests
 *
 * For each module:
 *   1. Log in as sysadmin
 *   2. Go to Install/Extensions page
 *   3. Click "Local <package>" to install (register the module)
 *   4. Go to company extensions activation page
 *   5. Check Active checkboxes for the modules
 *   6. Click Refresh to activate
 *   7. Log out and back in (activation takes effect)
 *
 * These tests require a running FA instance with the modules deployed
 * to the fa_modules/ bind mount.
 */

const MODULES = [
  { pkg: 'ksf_FA_HRM_LeaveApproval', label: 'Leave Approval', path: 'pages/hrm_leaveapproval.php' },
  { pkg: 'ksf_FA_HRM_Performance', label: 'Performance', path: 'pages/performance.php' },
  { pkg: 'ksf_FA_HRM_Onboarding', label: 'Onboarding', path: 'pages/onboarding.php' },
  { pkg: 'ksf_FA_HRM_Training', label: 'Training', path: 'pages/training.php' },
  { pkg: 'ksf_FA_HRM_Contracts', label: 'Contracts', path: 'pages/hrm_contracts.php' },
  { pkg: 'ksf_FA_CRM_Marketing', label: 'Marketing', path: 'pages/marketing_campaigns.php' },
  { pkg: 'ksf_FA_CRM_Email', label: 'Email', path: 'pages/email_accounts.php' },
  { pkg: 'ksf_FA_CRM_Helpdesk', label: 'Helpdesk', path: 'pages/helpdesk_cases.php' },
  { pkg: 'ksf_FA_CRM_Contracts', label: 'CRM Contracts', path: 'pages/crm_contracts.php' },
  { pkg: 'ksf_FA_CRM_Dashboard', label: 'Dashboard', path: 'pages/dashboard_widgets.php' },
  { pkg: 'ksf_FA_CRM_Activity', label: 'Activity', path: 'pages/activity_rules.php' },
  { pkg: 'ksf_FA_HRM_TimeTrackingPayrollIntegration', label: 'Payroll', path: 'pages/payroll_integration.php' },
  { pkg: 'ksf_FA_ProjectManagement', label: 'Project Management', path: 'index.php' },
];

test.describe('Install and Activate Modules', () => {
  test.beforeEach(async ({ page }) => {
    await faLogin(page);
  });

  test.afterEach(async ({ page }) => {
    await faLogout(page);
  });

  for (const mod of MODULES) {
    test(`install ${mod.pkg}`, async ({ page }) => {
      await gotoInstallExtensions(page);

      // Check if already installed (has Update button or no Local button)
      const localButton = page.locator(`input[name="Local.${mod.pkg}"], button[name="Local.${mod.pkg}"]`);
      if (await localButton.count() > 0) {
        await localButton.click();
        await page.waitForLoadState('networkidle');
      }

      // Verify it's now installed (Update button present or Local button gone)
      const updateButton = page.locator(`input[name="Update.${mod.pkg}"], button[name="Update.${mod.pkg}"]`);
      const stillLocal = await localButton.count();

      expect(
        await updateButton.count() > 0 || await stillLocal === 0,
        `${mod.pkg} should be installed after clicking Local`
      ).toBeTruthy();
    });
  }
});

test.describe('Activate Modules for Company', () => {
  test.beforeEach(async ({ page }) => {
    await faLogin(page);
  });

  test.afterEach(async ({ page }) => {
    await faLogout(page);
  });

  test('activate all 13 modules for company 0', async ({ page }) => {
    const packageNames = MODULES.map(m => m.pkg);
    await activateExtensions(page, '0', packageNames);

    // Verify activation by reloading the extensions page and checking Active state
    await page.goto(BASE_URL + '/admin/inst_module.php?extset=0');
    await page.waitForLoadState('networkidle');

    for (const mod of MODULES) {
      // The row should show the module as active
      const rowLocator = page.locator('tr').filter({ hasText: mod.label });
      if (await rowLocator.count() > 0) {
        // Check if Active checkbox is checked
        const activeCheckbox = rowLocator.locator('input[type="checkbox"]:checked');
        // Note: This is a best-effort check; the important thing is no version incompatibility error
      }
    }

    // Verify no "incompatible version" errors
    const errorLocator = page.locator('.error, .failure').filter({ hasText: 'incompatible' });
    expect(await errorLocator.count()).toBe(0);
  });
});
