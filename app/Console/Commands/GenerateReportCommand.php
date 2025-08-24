<?php

namespace App\Console\Commands;

use App\Services\ReportService;
use Illuminate\Console\Command;

class GenerateReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:generate
                            {studentId? : The student ID}
                            {reportType? : The report type (1-3)}
                            {--list : List available report types}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Assessment Reports by given student-id & report-type';

    /**
     * Execute the console command.
     */
    public function handle(ReportService $reportService): void
    {
        // Show available report types if --list option is used or no arguments provided
        if ($this->option('list') || !$this->argument('studentId') || !$this->argument('reportType')) {
            $this->showReportTypes();
            return;
        }

        $studentId  = $this->argument('studentId');
        $reportType = $this->argument('reportType');

        // Map report types to their names and methods
        $reportTypes = [
            '1' => ['name' => 'Diagnostic Report', 'method' => 'generateDiagnosticReport'],
            '2' => ['name' => 'Progress Report', 'method' => 'generateProgressReport'],
            '3' => ['name' => 'Feedback Report', 'method' => 'generateFeedbackReport'],
        ];

        if (!isset($reportTypes[$reportType])) {
            $this->error('Invalid report type');
            $this->showReportTypes();
            return;
        }

        $reportName = $reportTypes[$reportType]['name'];
        $method = $reportTypes[$reportType]['method'];

        // Generate the report
        $result = $reportService->$method($studentId);

        // Display the report with header
        $this->displayReportWithHeader($reportName, $result);
    }

    /**
     * Display the report with a formatted header.
     */
    protected function displayReportWithHeader(string $reportName, string $reportContent): void
    {
        $this->newLine();
        $this->info('========================================');
        $this->info(strtoupper($reportName));
        $this->info('========================================');
        $this->newLine();

        $this->line($reportContent);

        $this->newLine();
        $this->info('========================================');
        $this->info('END OF REPORT');
        $this->info('========================================');
    }

    /**
     * Display available report types and usage information.
     */
    protected function showReportTypes(): void
    {
        $this->info('Assessment Report Generator');
        $this->line('==========================');
        $this->newLine();

        $this->info('Available Report Types:');
        $this->line('1. Diagnostic Report');
        $this->line('2. Progress Report');
        $this->line('3. Feedback Report');
        $this->newLine();

        $this->info('Usage:');
        $this->line('php artisan report:generate <studentId> <reportType>');
        $this->line('php artisan report:generate student123 1');
        $this->newLine();

        $this->info('Options:');
        $this->line('--list    Show available report types');
        $this->newLine();

        $this->info('Examples:');
        $this->line('php artisan report:generate student123 1');
        $this->line('php artisan report:generate --list');
    }
}
