<?php
use App\Http\Controllers\Api\CursoController as ApiCursoController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('cursos', ApiCursoController::class)->names('api.cursos');

    // Correcta: solo los pedidos del usuario autenticado
    Route::get('/pedidos', fn (Request $r) => $r->user()->pedidos);

    // Control de acceso: solo busca entre los pedidos del usuario autenticado
    Route::get('/pedidos/{id}', fn (Request $r, $id) => $r->user()->pedidos()->findOrFail($id));

    // Validación: rechaza datos incompletos o fuera de rango
    Route::post('/pedidos', function (Request $r) {
        $datos = $r->validate([
            'descripcion' => 'required|string|max:255',
            'total' => 'required|numeric|min:0',
        ]);

        return $r->user()->pedidos()->create($datos);
    });

    Route::post('/logout', function (Request $r) {
    $r->user()->currentAccessToken()->delete();
    return response()->noContent();
});

});

// Login SIN límite de intentos (vulnerable)
Route::post('/login', function (Request $request) {
    $user = User::where('email', $request->email)->first();
    if (! $user || ! Hash::check($request->password, $user->password)) {
        Log::warning('Login fallido', ['email' => $request->email, 'ip' => $request->ip()]);
        return response()->json(['message' => 'Credenciales inválidas'], 401);
    }
    return ['token' => $user->createToken('app')->plainTextToken];
})->middleware('throttle:5,1');   // 5 intentos por minuto
