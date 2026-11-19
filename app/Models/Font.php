<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An uploaded .woff2 font file an admin can assign to a part of the site (see App\Services\Fonts). */
class Font extends Model
{
    protected $fillable = ['label', 'filename', 'original_name', 'size', 'uploaded_by'];

    public function family(): string
    {
        return 'cf-'.$this->id;
    }
}
