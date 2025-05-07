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

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function updatedAt()
    {
        return $this->updated_at->format('Y-m-d H:i:s');
    }

}
