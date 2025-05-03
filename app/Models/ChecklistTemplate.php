<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistTemplate extends Model
{
    public function checklist_template_items()
    {
        return $this->hasMany(ChecklistTemplateItem::class);
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
