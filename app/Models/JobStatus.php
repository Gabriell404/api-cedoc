<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobStatus extends Model
{
    use HasFactory;

    protected $table = 'job_statuses';

    protected $fillable = [
        'job_id', 'type', 'queue', 'attempts', 'progress_now', 'progress_max', 'status', 'input', 'output', 'started_at', 'finished_at'
    ];

    public static function setProgressMax($progress)
    {
        static::update([
            'progress_max' => $progress
        ]);
    }

    public static function setProgressNow(int $progress)
    {
        static::update([
            'progress_now' => $progress
        ]);
    }

    public static function prepareStatus($type)
    {
        static::create([
            'type' => $type,
        ]);
    }

    public static function finished()
    {
        static::update([
            'finished_at' => now()
        ]);
    }
}
