<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    // Tambahkan baris ini untuk mengizinkan kolom key dan value diisi
    protected $fillable = [
        'key',
        'value',
    ];
}