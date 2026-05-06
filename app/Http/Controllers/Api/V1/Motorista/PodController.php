<?php

namespace App\Http\Controllers\Api\V1\Motorista;

use App\Http\Controllers\Controller;
use App\Models\Carga;
use Illuminate\Http\Request;

class PodController extends Controller
{
    public function gerarUrlUpload(Request $request, Carga $carga)
    {
        if ($carga->motorista_id !== $request->user()->motorista->id) abort(403);
        
        // Em produção, isso gera uma Pre-Signed URL para a AWS S3
        return response()->json([
            'upload_url' => url('/api/v1/upload-mock'), 
            'file_path' => "pod/carga_{$carga->id}/" . time() . ".jpg"
        ]);
    }

    public function confirmarEntrega(Request $request, Carga $carga)
    {
        $request->validate([
            'file_path' => 'required|string',
            'latitude_entrega' => 'nullable|numeric',
            'longitude_entrega' => 'nullable|numeric'
        ]);

        if ($carga->motorista_id !== $request->user()->motorista->id) abort(403);

        $carga->update([
            'status' => 'em_auditoria',
            'foto_canhoto' => $request->file_path,
            'foto_carga' => $request->file_path,
        ]);

        return response()->json(['message' => 'Comprovantes enviados com sucesso. Carga enviada para auditoria.']);
    }
}