<?php

namespace App\Services;

use App\Models\Question;
use App\Models\StudentResponse;
use Throwable;
use RuntimeException;
use App\Models\Student;
use Illuminate\Support\Facades\Log;

class DataLoaderService
{
    private array $students = [];
    private array $questions = [];
    private array $studentResponses = [];
    private bool $dataLoaded = false;

    /**
     * Load Data
     * @return void
     */
    public function loadData(): void
    {
        if ($this->dataLoaded) {
            return;
        }

        $this->loadStudents();
        $this->loadQuestions();
        $this->loadStudentResponses();

        $this->dataLoaded = true;
    }

    /**
     * Load students data
     * @return array
     */
    public function loadStudents(): array
    {
        if (!empty($this->students)) {
            return $this->students;
        }

        $filePath = base_path('database/data/students.json');

        if (!file_exists($filePath)) {
            Log::error("DataLoaderService@loadStudent() -> File not found in {$filePath}");
            throw new RuntimeException("Students data file not found: {$filePath}");
        }

        $data = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error(
                'DataLoaderService@loadStudent() -> '
                . $filePath . ' file unable to decode due to ' . json_last_error_msg()
            );
            throw new RuntimeException('Failed to decode student JSON : ' . json_last_error_msg());
        }

        $errors = [];

        foreach ($data as $index => $studentData) {
            try {
                $student = Student::fromArray($studentData);
                $this->students[$student->id] = $student;
            } catch (Throwable $exception) {
                $errorMessage = "Invalid student data at index {$index}: " . $exception->getMessage();
                Log::error("DataLoaderService@loadStudent() -> " . $errorMessage, [
                    'student_data' => $studentData,
                    'trace' => $exception->getTraceAsString()
                ]);
                $errors[] = $errorMessage;
            }
        }

        if (!empty($errors)) {
            throw new RuntimeException("Some students failed to load:\n" . implode("\n", $errors));
        }

        return $this->students;
    }

    /**
     * Load Questions Data
     * @return array
     */
    public function loadQuestions(): array
    {
        if (!empty($this->questions)) {
            return $this->questions;
        }

        $filePath = base_path('database/data/questions.json');

        if (!file_exists($filePath)) {
            Log::error('DataLoaderService@loadQuestions() -> File not found in ' . $filePath);
            throw new \RuntimeException('Questions data file does not exist');
        }

        $data = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error(
                'DataLoaderService@loadStudent() -> '
                . $filePath . ' file unable to decode due to ' . json_last_error_msg()
            );
            throw new \RuntimeException('Failed to decode questions JSON : ' . json_last_error_msg());
        }

        $errors = [];

        foreach ($data as $index => $questionData) {
            try {
                $question = Question::fromArray($questionData);
                $this->questions[$question->id] = $question;

            } catch (Throwable $exception) {
                $errorMessage = "Invalid question data at index {$index}: " . $exception->getMessage();
                Log::error("DataLoaderService@loadStudent() -> " . $errorMessage, [
                    'student_data' => $questionData,
                    'trace' => $exception->getTraceAsString()
                ]);
                $errors[] = $errorMessage;
            }
        }

        if (!empty($errors)) {
            throw new RuntimeException("Some question failed to load:\n" . implode("\n", $errors));
        }

        return $this->questions;
    }

    /**
     * Load student responses data
     * @return array
     */
    public function loadStudentResponses(): array
    {
        if (!empty($this->studentResponses)) {
            return $this->studentResponses;
        }

        $students = $this->loadStudents();

        $filePath = base_path('database/data/student-responses.json');

        if (!file_exists($filePath)) {
            Log::error('DataLoaderService@loadStudentResponses() -> File not found in ' . $filePath);
            throw new \RuntimeException('Student responses data file does not exist');
        }

        $data = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error(
                'DataLoaderService@loadStudentResponses() -> '
                . $filePath . ' file unable to decode due to ' . json_last_error_msg()
            );
            throw new \RuntimeException('Failed to load student-responses JSON : ' . json_last_error_msg());
        }

        $errors = [];

        foreach ($data as $index => $studentResponseData) {
            try {
                $studentId = $studentResponseData['student']['id'] ?? null;

                if (!$studentId || !isset($students[$studentId])) {
                    Log::error('Student not found: ' . $studentId);
                    continue;
                }

                $studentResponse = StudentResponse::fromArray($studentResponseData, $students[$studentId]);
                $this->studentResponses[] = $studentResponse;
            } catch (\InvalidArgumentException $exception) {
                $errorMessage = "Invalid student response data at index {$index}: " . $exception->getMessage();
                Log::error("DataLoaderService@loadStudent() -> " . $errorMessage, [
                    'student_data' => $studentResponseData,
                    'trace' => $exception->getTraceAsString()
                ]);
                $errors[] = $errorMessage;
            }
        }

        if (!empty($errors)) {
            throw new RuntimeException("Some student responses are failed to load:\n" . implode("\n", $errors));
        }

        return $this->studentResponses;
    }

    /**
     * Get student by id
     * @param string $studentId
     * @return Student|null
     */
    public function getStudent(string $studentId): ?Student
    {
        $students = $this->loadStudents();
        return $students[$studentId] ?? null;
    }

    /**
     * Get latest completed student response
     * @param string $studentId
     * @return StudentResponse|null
     */
    public function getMostRecentCompletedResponse(string $studentId): ?StudentResponse
    {
        $completedResponses = $this->getCompletedResponsesForStudent($studentId);

        if (empty($completedResponses)) {
            return null;
        }

        // Sort by completed date descending (most recent first)
        usort($completedResponses, function (StudentResponse $a, StudentResponse $b) {
            $dateA = $a->completed ? strtotime($a->completed) : 0;
            $dateB = $b->completed ? strtotime($b->completed) : 0;
            return $dateB - $dateA;
        });

        return $completedResponses[0];
    }

    /**
     * Get completed response for given student id
     * @param string $studentId
     * @return array
     */
    public function getCompletedResponsesForStudent(string $studentId): array
    {
        $responses = $this->loadStudentResponses();

        return array_filter($responses, function (StudentResponse $studentResponse) use ($studentId) {
            return $studentResponse->student->id === $studentId && $studentResponse->isCompleted();
        });

    }

    /**
     * Get student performance by strand
     * @param string $studentId
     * @return array
     */
    public function getStudentPerformanceByStrand(string $studentId): array
    {
        $recentResponse = $this->getMostRecentCompletedResponse($studentId);

        if (!$recentResponse) {
            return [];
        }

        $strandPerformance = [];
        $totalCorrect = 0;
        $totalQuestions = 0;

        foreach ($recentResponse->responses as $studentAnswer) {
            $question = $this->getQuestion($studentAnswer['questionId']);
            if (!$question) {
                continue;
            }

            $strand = $question->strand;
            $isCorrect = $question->isCorrectAnswer($studentAnswer['response']);

            if (!isset($strandPerformance[$strand])) {
                $strandPerformance[$strand] = [
                    'total' => 0,
                    'correct' => 0,
                    'percentage' => 0
                ];
            }

            $strandPerformance[$strand]['total']++;
            $strandPerformance[$strand]['correct'] += $isCorrect ? 1 : 0;

            $totalQuestions++;
            $totalCorrect += $isCorrect ? 1 : 0;
        }

        // Calculate percentage
        foreach ($strandPerformance as $strand => &$stats) {
            $stats['percentage'] = $stats['total'] > 0
                ? round((($stats['correct'] / $stats['total']) * 100), 2)
                : 0;
        }

        return [
            'strandPerformance' => $strandPerformance,
            'totalQuestions' => $totalQuestions,
            'totalCorrect' => $totalCorrect,
            'overallPercentage' => $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 2) : 0,
        ];
    }

    /**
     * Get question by id
     * @param string $questionId
     * @return Question|null
     */
    public function getQuestion(string $questionId): ?Question
    {
        $questions = $this->loadQuestions();
        return $questions[$questionId] ?? null;
    }

    /**
     * Get student progress by student id
     * @param string $studentId
     * @return array
     */
    public function getStudentProgress(string $studentId): array
    {
        $completedResponses = $this->getCompletedResponsesForStudent($studentId);

        // Sort by completion date ascending (oldest first)
        // the earliest completed response will be first, the latest will be last.
        usort($completedResponses, function (StudentResponse $a, StudentResponse $b) {
            return $a->getCompletedTimestamp() - $b->getCompletedTimestamp();
        });

        $progress = [];
        foreach ($completedResponses as $response) {
            $progress[] = [
                'date' => $response->completed,
                'rawScore' => $response->getRawScore(),
                'totalQuestions' => $response->getTotalQuestions(),
                'percentage' => $response->getCompletionRate(),
                'assessmentId' => $response->assessmentId
            ];
        }

        return $progress;
    }

    /**
     * Get assessment name from assessment data file
     * @param string $assessmentId
     * @return string
     */
    public function getAssessmentName(string $assessmentId): string
    {
        $filePath = base_path('database/data/assessments.json');

        if (!file_exists($filePath)) {
            Log::error('Assessments data file not exists: ' . $filePath);
            return 'Assessment';
        }

        $data = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Assessments data file unable to decode due to : ' . json_last_error_msg());
            return 'Assessment';
        }

        foreach ($data as $assessment) {
            if ($assessment['id'] === $assessmentId) {
                return $assessment['name'] ?? 'Assessment';
            }
        }

        return 'Assessment';
    }
}
