<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Status: string implements HasColor, HasLabel
{
    case SUCCESS = 'success';
    case FAILURE = 'failure';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SUCCESS => 'Success',
            self::FAILURE => 'Failure',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SUCCESS => 'success',
            self::FAILURE => 'danger',
        };
    }
} 