<?php

namespace Src\Whistleblowing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
