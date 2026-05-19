<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // CORREÇÃO CIRÚRGICA: Extrai o nome da role corretamente, evitando quebra do Cache
        $audience = ($user && $user->role) ? $user->role->slug : 'todos'; 
        
        $cacheKey = "faqs:audience:{$audience}";

        $faqs = Cache::remember($cacheKey, 86400, function () use ($audience) {
            $data = Faq::select('id', 'category', 'question', 'answer')
                ->where('is_active', true)
                ->whereIn('audience', [$audience, 'todos'])
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get();

            // Garante que o retorno seja sempre um Objeto JSON, prevenindo o Crash no Frontend
            return $data->isEmpty() ? (object)[] : $data->groupBy('category');
        });

        return response()->json([
            'status' => 'success',
            'data' => $faqs
        ], 200);
    }
}