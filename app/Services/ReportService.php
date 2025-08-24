<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Student;
use App\Models\StudentResponse;

class ReportService
{
    public function __construct(public DataLoaderService $dataLoaderService)
    {
        $this->dataLoaderService->loadData();
    }
    public function generateDiagnosticReport(string $studentId): string
    {
        $student = $this->dataLoaderService->getStudent($studentId);

        if (!$student) {
            return "Student not found with ID: {$studentId}";
        }

        $mostRecentResponse = $this->dataLoaderService->getMostRecentCompletedResponse($studentId);

        if (!$mostRecentResponse) {
            return "No completed assessments found for student: {$student->getFullName()}";
        }

        $performance = $this->dataLoaderService->getStudentPerformanceByStrand($studentId);

        return $this->formatDiagnosticReport($student, $mostRecentResponse, $performance);
    }

    public function generateProgressReport(string $studentId): string
    {
        $student = $this->dataLoaderService->getStudent($studentId);
        if (!$student) {
            return "Student not found with ID: $studentId";
        }

        $progress = $this->dataLoaderService->getStudentProgress($studentId);
        if (empty($progress)) {
            return "No completed assessments found for student: {$student->getFullName()}";
        }

        return $this->formatProgressReport($student, $progress);
    }

    public function generateFeedbackReport(string $studentId): string
    {
        $student = $this->dataLoaderService->getStudent($studentId);
        if (!$student) {
            return "Student not found with ID: $studentId";
        }

        $mostRecentResponse = $this->dataLoaderService->getMostRecentCompletedResponse($studentId);
        if (!$mostRecentResponse) {
            return "No completed assessments found for student: {$student->getFullName()}";
        }

        return $this->formatFeedbackReport($student, $mostRecentResponse);
    }

    // Private Functions

    private function formatDiagnosticReport(Student $student, StudentResponse $response, array $performance): string
    {

        $completedDate = Carbon::createFromFormat('d/m/Y H:i:s', $response->completed);
        $formattedDate = $completedDate->format('jS F Y g:i A');

        $assessmentName = $this->dataLoaderService->getAssessmentName($response->assessmentId);

        $report = "{$student->getFullName()} recently completed {$assessmentName} assessment on {$formattedDate}\n";
        $report .= "He got {$performance['totalCorrect']} questions right out of {$performance['totalQuestions']}. Details by strand given below:\n\n";

        foreach ($performance['strandPerformance'] as $strand => $stats) {
            $report .= "{$strand}: {$stats['correct']} out of {$stats['total']} correct\n";
        }

        return $report;
    }

    private function formatProgressReport(Student $student, array $progress): string
    {
        $assessmentName = $this->dataLoaderService->getAssessmentName($progress[0]['assessmentId'] ?? '');
        $totalAssessments = count($progress);

        $report = "{$student->getFullName()} has completed {$assessmentName} assessment {$totalAssessments} times in total. Date and raw score given below:\n\n";

        foreach ($progress as $attempt) {
            $date = Carbon::createFromFormat('d/m/Y H:i:s', $attempt['date']);
            $formattedDate = $date->format('jS F Y');
            $report .= "Date: {$formattedDate}, Raw Score: {$attempt['rawScore']} out of {$attempt['totalQuestions']}\n";
        }

        if ($totalAssessments >= 2) {
            $firstScore = $progress[0]['rawScore'];
            $lastScore = $progress[count($progress) - 1]['rawScore'];
            $improvement = $lastScore - $firstScore;

            $report .= "\n{$student->getFullName()} got {$improvement} more correct in the recent completed assessment than the oldest";
        }

        return $report;
    }

    private function formatFeedbackReport(Student $student, StudentResponse $response): string
    {
        $completedDate = Carbon::createFromFormat('d/m/Y H:i:s', $response->completed);
        $formattedDate = $completedDate->format('jS F Y g:i A');

        $assessmentName = $this->dataLoaderService->getAssessmentName($response->assessmentId);
        $performance = $this->dataLoaderService->getStudentPerformanceByStrand($student->id);

        $report = "{$student->getFullName()} recently completed {$assessmentName} assessment on {$formattedDate}\n";
        $report .= "He got {$performance['totalCorrect']} questions right out of {$performance['totalQuestions']}. Feedback for wrong answers given below\n\n";

        $wrongAnswers = 0;
        foreach ($response->responses as $studentAnswer) {
            $question = $this->dataLoaderService->getQuestion($studentAnswer['questionId']);
            if (!$question || $question->isCorrectAnswer($studentAnswer['response'])) {
                continue;
            }

            $wrongAnswers++;
            $studentAnswerValue = $question->getOptionValue($studentAnswer['response']) ?? 'Unknown';
            $correctAnswerValue = $question->getOptionValue($question->getCorrectAnswer()) ?? 'Unknown';

            $report .= "Question: {$question->stem}\n";
            $report .= "Your answer: {$studentAnswer['response']} with value {$studentAnswerValue}\n";
            $report .= "Right answer: {$question->getCorrectAnswer()} with value {$correctAnswerValue}\n";
            $report .= "Hint: {$question->getHint()}\n\n";
        }

        if ($wrongAnswers === 0) {
            $report .= "No wrong answers found! Excellent work!\n";
        }

        return $report;
    }
}
