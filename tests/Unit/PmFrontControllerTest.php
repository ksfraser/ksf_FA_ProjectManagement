<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\App\TabRegistration;
use ksfraser\FrontAccounting\ProjectManagement\App\PmAppShell;
use ksfraser\FrontAccounting\ProjectManagement\App\PmFrontController;
use PHPUnit\Framework\TestCase;

/**
 * A tab controller that records that it ran, so dispatch can be observed
 * without booting FA or touching a database.
 */
class RecordingTabController
{
    /** @var string|null Key the front controller resolved. */
    public static $dispatched = null;

    /** @var array $_GET as the controller saw it. */
    public static $get = [];

    public function run(): void
    {
        self::$dispatched = 'recording';
        self::$get        = $_GET;
    }
}

/**
 * Minimal shell: app id 'pm' (so boot() fires `pm_register_tabs`), the same
 * view parameter PM publishes, and two tabs — one controller-backed, one with
 * no controller at all.
 */
class TestPmShell extends AbstractAppShell
{
    public function __construct()
    {
        parent::__construct('pm', 'index.php', 'view', 'dashboard');
    }
}

/**
 * PmFrontController — the PM adapter of the shared AbstractFrontController.
 *
 * These tests pin the things that make the adoption behaviour-preserving rather
 * than merely tidy:
 *
 *   - `?view=` is still the canonical tab selector (the sub-menu mints it and
 *     AbstractTabController::preserveParams() copies it onto pager links);
 *   - `?tab=` is accepted as the house-standard alias;
 *   - `page` is never a tab selector, and `$_GET` survives verbatim;
 *   - an action verb still renders the tab, because NONE of PM's six tab
 *     controllers implement runAction() — they own their verbs inside
 *     run() → handlePost() — so dispatchAction() is overridden;
 *   - access is enforced once, against the resolved tab's OWN area, including
 *     for a tab contributed through `pm_register_tabs`;
 *   - the front page still calls getSecurityArea() before session.inc and
 *     run() exactly once after it.
 *
 * @BABOK Related: BR-012
 */
class PmFrontControllerTest extends TestCase
{
    /** @var array Areas the fake access checker saw, in order. */
    private $checked = [];

    protected function setUp(): void
    {
        RecordingTabController::$dispatched = null;
        RecordingTabController::$get        = [];
        $GLOBALS['__fa_hook_handlers']      = [];
        $this->checked                     = [];
        $_GET                              = [];
        $_REQUEST                          = [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['__fa_hook_handlers'] = [];
        $_GET                           = [];
        $_REQUEST                       = [];
    }

    /**
     * run() echoes the page and the sub-menu; capture it so a test can assert on
     * what was rendered and the test output stays readable.
     *
     * @param PmFrontController $controller Controller under test
     * @return string Captured output
     */
    private function runCapturing(PmFrontController $controller): string
    {
        ob_start();
        try {
            $controller->run();
        } finally {
            $output = ob_get_clean();
        }

        return $output === false ? '' : $output;
    }

    /**
     * @param AbstractAppShell $shell   Shell under test
     * @param array            $options Overrides for the PM defaults
     * @return PmFrontController
     */
    private function controller(AbstractAppShell $shell, array $options = []): PmFrontController
    {
        return new PmFrontController(
            $shell,
            'SA_ksf_FA_ProjectManagementVIEW',
            '',
            $options + ['security_checker' => function (string $area): void {
                $this->checked[] = $area;
            }]
        );
    }

    /**
     * @return TestPmShell Shell carrying two controller tabs and one bare tab
     */
    private function shell(): TestPmShell
    {
        $shell = new TestPmShell();
        $shell->registerTab(new TabRegistration(
            'dashboard',
            '&Dashboard',
            'SA_ksf_FA_ProjectManagementVIEW',
            RecordingTabController::class
        ));
        $shell->registerTab(new TabRegistration(
            'projects',
            'Projects',
            'SA_ksf_FA_ProjectManagementVIEW',
            RecordingTabController::class
        ));
        $shell->registerTab(new TabRegistration('bare_tab', 'Bare', '', null));

        return $shell;
    }

    public function testViewParameterIsTheCanonicalTabSelector(): void
    {
        $_GET = ['view' => 'projects'];

        $this->assertSame('projects', $this->controller($this->shell())->resolveTabKey());
    }

    public function testTabParameterIsAcceptedAsAnAlias(): void
    {
        $_GET = ['tab' => 'projects'];

        $this->assertSame('projects', $this->controller($this->shell())->resolveTabKey());
    }

    public function testViewWinsOverTheAlias(): void
    {
        $_GET = ['view' => 'projects', 'tab' => 'bare_tab'];

        $this->assertSame('projects', $this->controller($this->shell())->resolveTabKey());
    }

    public function testUnrecognisedViewFallsBackToTheDefaultTab(): void
    {
        $_GET = ['view' => 'no_such_tab'];

        $this->assertSame('dashboard', $this->controller($this->shell())->resolveTabKey());
    }

    public function testPageIsNeverATabSelector(): void
    {
        $_GET = ['page' => 'projects'];

        $this->assertSame(
            'dashboard',
            $this->controller($this->shell())->resolveTabKey(),
            'the pager position must never be read as a tab selector'
        );
    }

    public function testGetIsNotNormalisedSoPagerLinksKeepTheView(): void
    {
        $_GET = ['view' => 'projects', 'page' => '3'];

        $controller = $this->controller($this->shell());
        $controller->normaliseRequest();

        $this->assertSame(
            ['view' => 'projects', 'page' => '3'],
            $_GET,
            "AbstractTabController::preserveParams() defaults to ['view'] and "
            . 'MasterSummaryTable copies those $_GET keys verbatim onto pager links, '
            . 'so normalising here would strip the tab off every pager link'
        );
    }

    public function testPagePositionIsResolvedAndClamped(): void
    {
        $_GET = ['view' => 'projects', 'page' => '3'];
        $this->assertSame(3, $this->controller($this->shell())->resolvePagePosition());

        $_GET = ['view' => 'projects', 'page' => '0'];
        $this->assertSame(1, $this->controller($this->shell())->resolvePagePosition());

        $_GET = ['view' => 'projects'];
        $this->assertSame(1, $this->controller($this->shell())->resolvePagePosition());
    }

    public function testSecurityAreaIsTheResolvedTabsOwnArea(): void
    {
        $_GET = ['view' => 'projects'];

        $this->assertSame(
            'SA_ksf_FA_ProjectManagementVIEW',
            $this->controller($this->shell())->getSecurityArea()
        );
    }

    public function testSecurityAreaFallsBackForATabWithoutOne(): void
    {
        $_GET = ['view' => 'bare_tab'];

        $this->assertSame(
            'SA_ksf_FA_ProjectManagementVIEW',
            $this->controller($this->shell())->getSecurityArea(),
            'a tab that declares no area must still be checked against the fallback'
        );
    }

    public function testAnEmptyFallbackAreaIsRejected(): void
    {
        $this->expectException(
            \ksfraser\FrontAccounting\Common\App\FrontControllerSecurityException::class
        );

        new PmFrontController($this->shell(), '   ', '');
    }

    public function testRunDispatchesTheRequestedTabAndChecksItsArea(): void
    {
        $_GET = ['view' => 'projects'];

        $this->runCapturing($this->controller($this->shell()));

        $this->assertSame('recording', RecordingTabController::$dispatched);
        $this->assertSame(['SA_ksf_FA_ProjectManagementVIEW'], $this->checked);
    }

    public function testRunLeavesGetAloneForTheTabController(): void
    {
        $_GET = ['view' => 'projects', 'page' => '2'];

        $this->runCapturing($this->controller($this->shell()));

        $this->assertSame(['view' => 'projects', 'page' => '2'], RecordingTabController::$get);
    }

    public function testActionVerbStillRendersTheTabInsteadOfTheUnavailableMessage(): void
    {
        $_GET     = ['view' => 'projects'];
        $_REQUEST = ['view' => 'projects', 'action' => 'save'];

        $output = $this->runCapturing($this->controller($this->shell()));

        $this->assertSame(
            'recording',
            RecordingTabController::$dispatched,
            'no PM tab controller implements runAction(); a verb must not short-circuit the tab'
        );
        $this->assertStringNotContainsString(
            'not available for the selected tab',
            $output,
            'the base class default (renderUnavailable) must never fire for PM'
        );
        $this->assertSame(
            ['SA_ksf_FA_ProjectManagementVIEW'],
            $this->checked,
            'access is enforced before the action path is chosen'
        );
    }

    public function testRunBootsTheShellThenChecksAContributedTabAgainstItsOwnArea(): void
    {
        $GLOBALS['__fa_hook_handlers']['pm_register_tabs'] = static function (&$data, $opts = null): void {
            $data['tabs'][] = new TabRegistration(
                'probe',
                'Probe',
                'SA_THIRDPARTY_VIEW',
                RecordingTabController::class
            );
        };
        $_GET = ['view' => 'probe'];

        $controller = $this->controller($this->shell());
        $this->runCapturing($controller);

        $this->assertSame(
            ['SA_THIRDPARTY_VIEW'],
            $this->checked,
            'a tab contributed through pm_register_tabs must be reachable and checked '
            . 'against its OWN area, not the fallback'
        );
        $this->assertSame('recording', RecordingTabController::$dispatched);
        $this->assertSame('probe', $controller->getResolvedTab());
    }

    public function testContributedTabIsInvisibleToThePreSessionAreaResolution(): void
    {
$GLOBALS['__fa_hook_handlers']['pm_register_tabs'] = static function (&$data, $opts = null): void {
            $data['tabs'][] = new TabRegistration(
                'probe',
                'Probe',
                'SA_THIRDPARTY_VIEW',
                RecordingTabController::class
            );
        };
        $_GET = ['view' => 'probe'];

        // getSecurityArea() runs BEFORE session.inc, so the hook cannot have fired.
        $this->assertSame(
            'SA_ksf_FA_ProjectManagementVIEW',
            $this->controller($this->shell())->getSecurityArea()
        );
    }

    public function testTheRealShellPublishesThePmRegisterHookAndSixCoreTabs(): void
    {
        $shell = new PmAppShell();

        $this->assertSame('pm_register_tabs', $shell->getRegisterHook());
        $this->assertSame('dashboard', $shell->getDefaultView());
        $this->assertCount(6, $shell->getTabs());

        foreach (['dashboard', 'projects', 'tasks', 'team', 'reports', 'project_types'] as $key) {
            $this->assertNotNull($shell->getTab($key), "core tab {$key} must exist");
            $this->assertTrue($shell->getTab($key)->hasController());
        }
    }

    public function testNoPmTabControllerImplementsRunAction(): void
    {
        $shell = new PmAppShell();

        foreach ($shell->getTabs() as $tab) {
            $class = $tab->getControllerClass();
            if ($class === null || !class_exists($class)) {
                continue;
            }
            $this->assertFalse(
                method_exists($class, 'runAction'),
                $class . ' has no runAction(); PmFrontController::dispatchAction() must '
                . 'keep dispatching the tab or every POST save becomes a silent no-op'
            );
        }
    }

    public function testDefaultOptionsPinPmsPublishedUrlSurface(): void
    {
        $options = PmFrontController::defaultOptions();

        $this->assertSame('view', $options['tab_param']);
        $this->assertSame('tab', $options['alias_param']);
        $this->assertFalse($options['normalise_get']);
        $this->assertFalse($options['load_ui_includes']);
    }

    public function testExplicitOptionsOverrideThePmDefaults(): void
    {
        $_GET = ['selector' => 'projects'];

        $controller = $this->controller(
            $this->shell(),
            ['tab_param' => 'selector', 'alias_param' => '']
        );

        $this->assertSame('projects', $controller->resolveTabKey());
    }

    public function testTheFrontPageKeepsTheTwoPhaseFaBootstrap(): void
    {
        $index = file_get_contents(dirname(__DIR__, 2) . '/index.php');

        $securityPos = strpos($index, 'getSecurityArea()');
        $sessionPos  = strpos($index, 'includes/session.inc');
        $runPos      = strpos($index, '->run()');

        $this->assertNotFalse($securityPos, 'index.php must resolve the area');
        $this->assertNotFalse($sessionPos, 'index.php must include session.inc');
        $this->assertNotFalse($runPos, 'index.php must call run()');
        $this->assertLessThan($sessionPos, $securityPos, 'the area must be known BEFORE session.inc');
        $this->assertLessThan($runPos, $sessionPos, 'run() must come AFTER session.inc');
        $this->assertSame(1, substr_count($index, '->run()'), 'run() is called exactly once');
    }

    public function testRunIsInheritedFromTheBaseAndCannotBeOverridden(): void
    {
        $method = new \ReflectionMethod(PmFrontController::class, 'run');
        $declaring = $method->getDeclaringClass()->getName();

        $this->assertSame(
            \ksfraser\FrontAccounting\Common\App\AbstractFrontController::class,
            $declaring,
            'PM must inherit run() so the boot-then-enforce order cannot be reordered'
        );
        $this->assertTrue($method->isFinal());

        $enforce = new \ReflectionMethod(PmFrontController::class, 'enforceSecurity');
        $this->assertTrue($enforce->isFinal(), 'enforceSecurity() must stay final');
    }
}