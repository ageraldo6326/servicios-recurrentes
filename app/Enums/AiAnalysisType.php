<?php

declare(strict_types=1);

namespace App\Enums;

enum AiAnalysisType: string
{
    case Summary = 'summary';
    case Financial = 'financial';
    case Risks = 'risks';
    case Recommendations = 'recommendations';
    case NextActions = 'next_actions';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Summary => 'Resumen',
            self::Financial => 'Análisis financiero',
            self::Risks => 'Riesgos y alertas',
            self::Recommendations => 'Recomendaciones de negocio',
            self::NextActions => 'Próximas acciones',
            self::Custom => 'Análisis personalizado',
        };
    }
}
