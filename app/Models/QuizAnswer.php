<?php

namespace App\Models;

use App\Traits\PostgresBooleanCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnswer extends Model
{
    use PostgresBooleanCast;
    protected $fillable = [
        'quiz_question_id',
        'answer_text',
        'is_correct',
        'order_number',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}