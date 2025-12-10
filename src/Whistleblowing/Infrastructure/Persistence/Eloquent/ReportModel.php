<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent Model: ReportModel
 * 
 * Modelo de Eloquent para persistencia en la base de datos.
 * Este modelo pertenece a la capa de Infrastructure, NO al Domain.
 */
class ReportModel extends Model
{
    use HasFactory;

    protected $table = 'reports';

    protected $fillable = [
        'id',
        'title',
        'description',
        'status',
        'reporter_id',
        'access_code_hash',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Relación: Un reporte tiene muchos mensajes
     */
    public function messages(): HasMany
    {
        return $this->hasMany(MessageModel::class, 'report_id', 'id')
                    ->orderBy('created_at', 'asc');
    }
}
