<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    use HasUlids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public static $statusList = [
        self::STATUS_PENDING,
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED
    ];

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    public static $priorityList = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH
    ];

     protected $fillable = [
        'title',
        'assignee',
        'due_date',
        'time_tracked',
        'status',
        'priority'
     ];
}
