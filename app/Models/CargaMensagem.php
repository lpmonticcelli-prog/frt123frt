<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargaMensagem extends Model
{
    protected $table = 'carga_mensagens';
    protected $fillable = ['carga_id', 'remetente_id', 'remetente_tipo', 'mensagem', 'lida'];
}