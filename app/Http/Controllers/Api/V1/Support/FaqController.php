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
        // Identifica quem está chamando. Se não tiver role (ex: visitante), cai em 'todos'
        $audience = $request->user()->role ?? 'todos'; 
        
        // TTL de 24h = 86400 segundos. Chave segmentada por perfil.
        $cacheKey = "faqs:audience:{$audience}";

        $faqs = Cache::remember($cacheKey, 86400, function () use ($audience) {
            return Faq::select('id', 'category', 'question', 'answer')
                ->where('is_active', true)
                ->whereIn('audience', [$audience, 'todos'])
                ->orderBy('category')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('category');
        });

        return response()->json([
            'status' => 'success',
            'data' => $faqs
        ], 200);
    }
}