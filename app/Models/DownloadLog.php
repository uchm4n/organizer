<?php

namespace App\Models;

use Database\Factories\DownloadLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $filename
 * @property string $ip
 * @property string $user_agent
 * @property string|null $referer
 * @property array<string, mixed>|null $extra
 * @property Carbon|null $created_at
 */
class DownloadLog extends Model
{
    /** @use HasFactory<DownloadLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['filename', 'ip', 'user_agent', 'referer', 'extra'];

    protected $hidden = ['ip', 'user_agent', 'referer'];

    protected function casts(): array
    {
        return [
            'extra' => 'array',
        ];
    }
}
