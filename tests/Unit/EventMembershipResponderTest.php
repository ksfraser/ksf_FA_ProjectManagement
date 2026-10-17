<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Service\EventMembershipResponder;
use PHPUnit\Framework\TestCase;

/**
 * EventMembershipResponder — FR-PM-007-001 membership classification.
 *
 * Identity chain: email → users.email → users.user_id →
 * 0_fa_pm_assignments.employee_id (Team tab stores FA user_id in
 * employee_id). Expects SINGLE-ROW results nested in the stub queue
 * (db_fetch_assoc shifts one row off the current result list).
 *
 * @BABOK Related: BR-007, FR-PM-007-001, FR-CAL-007-003
 */
class EventMembershipResponderTest extends TestCase
{
    /** @var EventMembershipResponder */
    private $responder;

    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue'] = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_sql_log'] = [];
        $GLOBALS['__fa_next_id'] = 1;
        $this->responder = new EventMembershipResponder();
    }

    /**
     * Attendee emails that bridge to an assigned user become MEMBERS.
     */
    public function testProjectAssigneesAreMembers(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['employee_id' => 'kevin'], ['employee_id' => 'alice']],
            [['user_id' => 'kevin']],
            [],
        ];

        $members = $this->responder->respondMembership([
            'project_id' => 'PRJ-0001',
            'attendee_emails' => ['u1@x.test', 'u2@x.test'],
        ]);

        $this->assertSame(['u1@x.test'], $members);
    }

    /**
     * project_id may come from the task row (task implies project only when
     * the task exists).
     */
    public function testTaskImpliesProjectViaTaskRow(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['task_id' => 'TSK-0001', 'project_id' => 'PRJ-0001']],
            [['employee_id' => 'kevin']],
            [['user_id' => 'kevin']],
        ];

        $members = $this->responder->respondMembership([
            'task_id' => 'TSK-0001',
            'attendee_emails' => ['u1@x.test'],
        ]);

        $this->assertSame(['u1@x.test'], $members);
    }

    /**
     * AZZ: neither project_id nor task_id — responder returns nothing and
     * runs no queries.
     */
    public function testNoProjectNoTaskNoOp(): void
    {
        $members = $this->responder->respondMembership([
            'event_id' => 7,
            'attendee_emails' => ['u1@x.test'],
        ]);

        $this->assertSame([], $members);
        $this->assertSame([], (array) $GLOBALS['__fa_sql_log']);
    }

    /**
     * AZZ: task_id unknown to the module — nothing classified.
     */
    public function testUnknownTaskNoOp(): void
    {
        $GLOBALS['__fa_select_queue'] = [[]];

        $members = $this->responder->respondMembership([
            'task_id' => 'TSK-9999',
            'attendee_emails' => ['u1@x.test'],
        ]);

        $this->assertSame([], $members);
    }

    /**
     * A task row lacking a project does not imply one.
     */
    public function testTaskRowWithoutProjectNoOp(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['task_id' => 'TSK-0001', 'project_id' => '']],
        ];

        $members = $this->responder->respondMembership([
            'task_id' => 'TSK-0001',
            'attendee_emails' => ['u1@x.test'],
        ]);

        $this->assertSame([], $members);
    }

    /**
     * A user without a project assignment is not a member.
     */
    public function testUserNotAssignedIsNotMember(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['employee_id' => 'alice']],
            [['user_id' => 'kevin']],
        ];

        $members = $this->responder->respondMembership([
            'project_id' => 'PRJ-0001',
            'attendee_emails' => ['u1@x.test'],
        ]);

        $this->assertSame([], $members);
    }

    /**
     * BON: email unknown to the users table cannot resolve -> not a member.
     */
    public function testEmailNotInUsersIsNotMember(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['employee_id' => 'kevin']],
            [],
        ];

        $members = $this->responder->respondMembership([
            'project_id' => 'PRJ-0001',
            'attendee_emails' => ['u1@x.test'],
        ]);

        $this->assertSame([], $members);
    }

    /**
     * Attendee emails are lower-cased and de-duplicated before classification;
     * the handle match is case-insensitive.
     */
    public function testMembersLowercasedAndDeduped(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['employee_id' => 'kevin']],
            [['user_id' => 'KEVIN']],
        ];

        $members = $this->responder->respondMembership([
            'project_id' => 'PRJ-0001',
            'attendee_emails' => ['  U1@X.TEST  ', 'U1@X.TEST'],
        ]);

        $this->assertSame(['u1@x.test'], $members);
    }

    /**
     * READ-ONLY guarantee: classification never issues writes.
     */
    public function testResponderIsReadOnly(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['employee_id' => 'kevin']],
            [['user_id' => 'kevin']],
        ];

        $this->responder->respondMembership([
            'project_id' => 'PRJ-0001',
            'attendee_emails' => ['u1@x.test'],
        ]);

        foreach ((array) $GLOBALS['__fa_sql_log'] as $sql) {
            $prefix = strtolower(substr(ltrim((string) $sql), 0, 6));
            $this->assertNotContains($prefix, ['insert', 'update', 'delete', 'replac']);
        }
    }

    /**
     * Object-form DTO (toArray) is accepted without a Calendar class dependency.
     */
    public function testObjectDtoIsAccepted(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['employee_id' => 'kevin']],
            [['user_id' => 'kevin']],
        ];

        $dto = new class([]) {
            public $payload;
            public function __construct(array $payload)
            {
                $this->payload = $payload;
            }
            public function toArray(): array
            {
                return $this->payload;
            }
        };
        $dto->payload = [
            'project_id' => 'PRJ-0001',
            'attendee_emails' => ['u1@x.test'],
        ];

        $members = $this->responder->respondMembership($dto);

        $this->assertSame(['u1@x.test'], $members);
    }
}