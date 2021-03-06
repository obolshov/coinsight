<?php

declare(strict_types=1);

namespace App\Domain\Markets\Models;

use Illuminate\Database\Eloquent\Model;

final class News extends Model
{
    protected $table = 'news';

    protected $dates = [
        'published_at',
    ];
}
