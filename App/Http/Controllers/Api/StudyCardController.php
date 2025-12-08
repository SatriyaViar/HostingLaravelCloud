<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudyCardRequest;
use App\Http\Requests\UpdateStudyCardRequest;
use App\Http\Resources\StudyCardResource;
use App\Contracts\Services\StudyCardServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StudyCardController extends Controller
{
    protected StudyCardServiceInterface $service;

    public function __construct(StudyCardServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of study cards for authenticated user
     * 
     * @group Study Cards
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $studyCards = $this->service->getUserStudyCards($userId);

            return response()->json([
                'success' => true,
                'message' => 'Study cards retrieved successfully',
                'data' => StudyCardResource::collection($studyCards),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve study cards',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created study card
     * 
     * @group Study Cards
     */
    public function store(StoreStudyCardRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;

            // Handle file upload jika ada
            if ($request->hasFile('material_file')) {
                $file = $request->file('material_file');
                
                // Store file ke storage/app/public/study-materials
                $path = $file->store('study-materials', 'public');
                
                $data['material_file_url'] = Storage::url($path);
                $data['material_file_name'] = $file->getClientOriginalName();
                $data['material_file_type'] = $file->getMimeType();
                $data['material_file_size'] = $file->getSize();
                $data['material_type'] = 'file';
            }

            $studyCard = $this->service->createStudyCard($data);

            return response()->json([
                'success' => true,
                'message' => 'Study card created successfully',
                'data' => new StudyCardResource($studyCard),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create study card',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified study card
     * 
     * @group Study Cards
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $studyCard = $this->service->getStudyCardById($id);

            if (!$studyCard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Study card not found',
                ], 404);
            }

            // Check ownership
            if ($studyCard->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Study card retrieved successfully',
                'data' => new StudyCardResource($studyCard),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve study card',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified study card
     * 
     * @group Study Cards
     */
    public function update(UpdateStudyCardRequest $request, int $id): JsonResponse
    {
        try {
            $studyCard = $this->service->getStudyCardById($id);

            if (!$studyCard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Study card not found',
                ], 404);
            }

            // Check ownership
            if ($studyCard->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            $data = $request->validated();

            // Handle new file upload
            if ($request->hasFile('material_file')) {
                // Delete old file if exists
                if ($studyCard->material_file_url) {
                    $oldPath = str_replace('/storage/', 'public/', $studyCard->material_file_url);
                    Storage::delete($oldPath);
                }

                $file = $request->file('material_file');
                $path = $file->store('study-materials', 'public');
                
                $data['material_file_url'] = Storage::url($path);
                $data['material_file_name'] = $file->getClientOriginalName();
                $data['material_file_type'] = $file->getMimeType();
                $data['material_file_size'] = $file->getSize();
                $data['material_type'] = 'file';
            }

            $updatedStudyCard = $this->service->updateStudyCard($id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Study card updated successfully',
                'data' => new StudyCardResource($updatedStudyCard),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update study card',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified study card
     * 
     * @group Study Cards
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $studyCard = $this->service->getStudyCardById($id);

            if (!$studyCard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Study card not found',
                ], 404);
            }

            // Check ownership
            if ($studyCard->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            // Delete file if exists
            if ($studyCard->material_file_url) {
                $path = str_replace('/storage/', 'public/', $studyCard->material_file_url);
                Storage::delete($path);
            }

            $this->service->deleteStudyCard($id);

            return response()->json([
                'success' => true,
                'message' => 'Study card deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete study card',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate quiz from study card using AI
     * Jika quiz sudah ada, langsung return quiz tersebut kecuali force_regenerate=true
     * 
     * @group Study Cards
     */
    public function generateQuiz(Request $request, int $id): JsonResponse
    {
        // Increase execution time for AI generation
        set_time_limit(120); // 2 minutes max
        
        try {
            $studyCard = $this->service->getStudyCardById($id);

            if (!$studyCard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Study card not found',
                ], 404);
            }

            // Check ownership
            if ($studyCard->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            // Get QuizService
            $quizService = app(\App\Contracts\Services\QuizServiceInterface::class);
            
            // ✅ Cek apakah quiz sudah ada untuk study card ini
            $existingQuiz = $quizService->getQuizzesByStudyCard($id)->first();
            $forceRegenerate = $request->boolean('force_regenerate', false);
            
            // Jika quiz sudah ada dan tidak force regenerate, return quiz yang sudah ada
            if ($existingQuiz && !$forceRegenerate) {
                // Load questions with answers
                $existingQuiz->load('questions.answers');

                // Format questions for frontend
                $questions = $existingQuiz->questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'points' => $question->points,
                        'explanation' => $question->explanation,
                        'answers' => $question->answers->map(function ($answer) {
                            return [
                                'id' => $answer->id,
                                'answer_text' => $answer->answer_text,
                                'is_correct' => $answer->is_correct,
                            ];
                        })->toArray(),
                    ];
                })->toArray();

                return response()->json([
                    'success' => true,
                    'message' => 'Quiz already exists. Use force_regenerate=true to generate new quiz.',
                    'from_cache' => true,
                    'data' => [
                        'id' => $existingQuiz->id,
                        'title' => $existingQuiz->title,
                        'total_questions' => $existingQuiz->total_questions,
                        'study_card_id' => $existingQuiz->study_card_id,
                        'created_at' => $existingQuiz->created_at->toISOString(),
                        'questions' => $questions,
                    ],
                ]);
            }

            // Generate quiz baru menggunakan AI
            $questionCount = $request->input('question_count', 10); // Default 10 soal
            
            $quiz = $quizService->generateQuizFromAI($id, [
                'num_questions' => $questionCount,
                'duration_minutes' => $request->input('duration_minutes', 30),
            ]);

            // Load questions with answers
            $quiz->load('questions.answers');

            // Format questions for frontend
            $questions = $quiz->questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => $question->points,
                    'explanation' => $question->explanation,
                    'answers' => $question->answers->map(function ($answer) {
                        return [
                            'id' => $answer->id,
                            'answer_text' => $answer->answer_text,
                            'is_correct' => $answer->is_correct,
                        ];
                    })->toArray(),
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Quiz generated successfully',
                'from_cache' => false,
                'data' => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'total_questions' => $quiz->total_questions,
                    'study_card_id' => $quiz->study_card_id,
                    'created_at' => $quiz->created_at->toISOString(),
                    'questions' => $questions,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Generate Quiz Error', [
                'study_card_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate quiz',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all existing quizzes for a study card
     * 
     * @group Study Cards
     */
    public function getQuizzes(Request $request, int $id): JsonResponse
    {
        try {
            $studyCard = $this->service->getStudyCardById($id);

            if (!$studyCard) {
                return response()->json([
                    'success' => false,
                    'message' => 'Study card not found',
                ], 404);
            }

            // Check ownership
            if ($studyCard->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            // Get QuizService
            $quizService = app(\App\Contracts\Services\QuizServiceInterface::class);
            
            // Get all quizzes for this study card
            $quizzes = $quizService->getQuizzesByStudyCard($id);

            // Format quizzes
            $formattedQuizzes = $quizzes->map(function ($quiz) {
                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'total_questions' => $quiz->total_questions,
                    'duration_minutes' => $quiz->duration_minutes,
                    'generated_by_ai' => $quiz->generated_by_ai,
                    'ai_model' => $quiz->ai_model,
                    'created_at' => $quiz->created_at->toISOString(),
                    'updated_at' => $quiz->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Quizzes retrieved successfully',
                'data' => $formattedQuizzes,
                'count' => $quizzes->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quizzes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific quiz with questions and answers
     * 
     * @group Quizzes
     */
    public function getQuiz(Request $request, int $id): JsonResponse
    {
        try {
            // Get QuizService
            $quizService = app(\App\Contracts\Services\QuizServiceInterface::class);
            
            $quiz = $quizService->getQuizById($id);

            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found',
                ], 404);
            }

            // Check ownership through study card
            $studyCard = $this->service->getStudyCardById($quiz->study_card_id);
            if ($studyCard->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            // Load questions with answers
            $quiz->load('questions.answers');

            // Format questions for frontend
            $questions = $quiz->questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'order_number' => $question->order_number,
                    'points' => $question->points,
                    'explanation' => $question->explanation,
                    'answers' => $question->answers->map(function ($answer) {
                        return [
                            'id' => $answer->id,
                            'answer_text' => $answer->answer_text,
                            'is_correct' => $answer->is_correct,
                            'order_number' => $answer->order_number,
                        ];
                    })->toArray(),
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Quiz retrieved successfully',
                'data' => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'total_questions' => $quiz->total_questions,
                    'duration_minutes' => $quiz->duration_minutes,
                    'study_card_id' => $quiz->study_card_id,
                    'shuffle_questions' => $quiz->shuffle_questions,
                    'shuffle_answers' => $quiz->shuffle_answers,
                    'show_correct_answers' => $quiz->show_correct_answers,
                    'generated_by_ai' => $quiz->generated_by_ai,
                    'ai_model' => $quiz->ai_model,
                    'created_at' => $quiz->created_at->toISOString(),
                    'questions' => $questions,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quiz',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}