<?php

namespace App\Traits;

trait HasActivityStatus
{
    public function status(string $status): self
    {
        $this->status = $status;
        $this->save();
        
        return $this;
    }
} 