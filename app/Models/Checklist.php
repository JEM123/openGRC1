<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    public function checklist_template()
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    public function checklist_responses()
    {
        return $this->hasMany(ChecklistResponse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
