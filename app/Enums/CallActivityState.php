<?php

declare(strict_types=1);

namespace App\Enums;

enum CallActivityState: string
{
    case NoData = 'no_data';
    case NoReport = 'no_report';
    case NoCalls = 'no_calls';
    case Inactive = 'inactive';
    case Active = 'active';

    public function label(): string
    {
        return match ($this) {
            self::NoData => 'Sin datos',
            self::NoReport => 'Sin reporte',
            self::NoCalls => 'Sin llamadas registradas',
            self::Inactive => 'Inactivo',
            self::Active => 'Activo',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NoData => 'El monitoreo todavía no recibió su primer reporte.',
            self::NoReport => 'El servidor dejó de reportar dentro del plazo esperado.',
            self::NoCalls => 'El servidor reporta, pero aún no registra intentos salientes.',
            self::Inactive => 'El último intento saliente excede el umbral configurado.',
            self::Active => 'Se confirmó actividad saliente reciente.',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::NoData => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
            self::NoReport => 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300',
            self::NoCalls => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
            self::Inactive => 'bg-orange-100 text-orange-800 dark:bg-orange-950/50 dark:text-orange-300',
            self::Active => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
        };
    }
}
