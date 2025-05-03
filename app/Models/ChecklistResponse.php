<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistResponse extends Model
{
    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
