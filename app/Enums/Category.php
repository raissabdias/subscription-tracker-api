<?php

namespace App\Enums;

enum Category: string
{
    case Entertainment = 'Entretenimento';
    case Services = 'Serviços';
    case Health = 'Saúde';
    case Education = 'Educação';
    case Work = 'Trabalho';
    case Home = 'Casa';
    case Transport = 'Transporte';
    case Others = 'Outros';

    public static function options(): array
    {
        return array_map(fn($case) => [
            'label' => $case->value,
            'value' => $case->value,
        ], self::cases());
    }
}