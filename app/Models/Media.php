<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';
    protected $guarded = [];

    public function url(): string
    {
        return route('media.show', $this->filename);
    }
}
