<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanPenindakanBukti extends Model
{
    use HasFactory;

    protected $fillable = [
        'laporan_penindakan_id',
        'nama_file',
        'path',
        'tipe',
        'diunggah_oleh',
    ];

    public function laporanPenindakan(): BelongsTo
    {
        return $this->belongsTo(LaporanPenindakan::class);
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }
}
