<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardList extends Model
{
    use HasFactory;

    protected $primaryKey = 'idList';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['idList', 'name', 'pos', 'idBoard'];
}
