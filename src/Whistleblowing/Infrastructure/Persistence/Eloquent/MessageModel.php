<?php

declare(strict_types=1);

namespace Src\Whistleblowing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent Model: MessageModel
 * 
 * Modelo de Eloquent para persistencia de mensajes en la base de datos.
 * Este modelo pertenece a la capa de Infrastructure, NO al Domain.
 */
class MessageModel extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'id',
        'report_id',
        'content',
        'author',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    /**
     * Relación: Un mensaje pertenece a un reporte
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(ReportModel::class, 'report_id', 'id');
    }
}
