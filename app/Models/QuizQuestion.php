<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'order_number',
        'points',
        'explanation',
        'is_bank_question',
        'topic',
        'difficulty_level',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'is_bank_question' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserQuizAnswer::class);
    }
}