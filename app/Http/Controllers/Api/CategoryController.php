<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    
    /**
     * Lista de categorias disponíveis.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json(Category::options());
    }
}
