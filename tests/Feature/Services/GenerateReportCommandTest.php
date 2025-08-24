<?php

namespace Tests\Feature\Services;

use Tests\TestCase;

class GenerateReportCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestDataFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestDataFiles();
        parent::tearDown();
    }

    private function createTestDataFiles(): void
    {
        $sourcePath = database_path('data');
        $targetPath = base_path('data');

        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        $files = ['students.json', 'questions.json', 'assessments.json', 'student-responses.json'];

        foreach ($files as $file) {
            $sourceFile = $sourcePath . '/' . $file;
            $targetFile = $targetPath . '/' . $file;

            if (file_exists($sourceFile)) {
                copy($sourceFile, $targetFile);
            }
        }
    }

    private function cleanupTestDataFiles(): void
    {
        $basePath = base_path('data');
        $files = ['students.json', 'questions.json', 'assessments.json', 'student-responses.json'];

        foreach ($files as $file) {
            if (file_exists($basePath . '/' . $file)) {
                unlink($basePath . '/' . $file);
            }
        }

        if (is_dir($basePath) && count(scandir($basePath)) == 2) {
            rmdir($basePath);
        }
    }

    /** @test */
    public function it_can_list_available_report_types()
    {
        $this->artisan('report:generate --list')
            ->expectsOutput('Assessment Report Generator')
            ->expectsOutput('==========================')
            ->expectsOutput('Available Report Types:')
            ->expectsOutput('1. Diagnostic Report')
            ->expectsOutput('2. Progress Report')
            ->expectsOutput('3. Feedback Report')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_generates_diagnostic_report_successfully()
    {
        $this->artisan('report:generate student1 1')
            ->expectsOutputToContain('DIAGNOSTIC REPORT')
            ->expectsOutputToContain('Tony Stark')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_generates_progress_report_successfully()
    {
        $this->artisan('report:generate student1 2')
            ->expectsOutputToContain('PROGRESS REPORT')
            ->expectsOutputToContain('Tony Stark')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_generates_feedback_report_successfully()
    {
        $this->artisan('report:generate student1 3')
            ->expectsOutputToContain('FEEDBACK REPORT')
            ->expectsOutputToContain('Tony Stark')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_shows_error_for_invalid_student_id()
    {
        $this->artisan('report:generate invalid-student 1')
            ->expectsOutputToContain('Student not found')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_shows_error_for_invalid_report_type()
    {
        $this->artisan('report:generate student1 99')
            ->expectsOutput('Invalid report type')
            ->expectsOutput('Available Report Types:')
            ->assertExitCode(0);
    }

}
