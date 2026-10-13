<?php

namespace Tests\Unit;

use App\Jobs\GenerateAttendanceSheet;
use App\Models\AttendanceSheet;
use Tests\TestCase;

class GenerateAttendanceSheetTest extends TestCase
{
    public function test_job_timeout_is_1100_seconds(): void
    {
        $sheet = new AttendanceSheet;

        $job = new GenerateAttendanceSheet($sheet);

        $this->assertSame(1100, $job->timeout);
    }
}
