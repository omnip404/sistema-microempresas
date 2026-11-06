<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registro y anulación de ventas (RF06). La lógica de negocio está en VentaService;
 * el controlador solo valida la entrada y responde (inyección de dependencias).
 */
class VentaController extends Controller
{
    public function __construct(private VentaService $ventas) {}

    public function index()
    {
        $ventas = Venta::with('usuario', 'cliente')->latest('fecha')->paginate(20);

        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        return view('ventas.create', [
            'productos' => Producto::where('activo', true)->where('stock', '>', 0)->orderBy('nombre')->get(),
            'clientes' => Cliente::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')->where('activo', true)],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'Agregue al menos un producto a la venta.',
        ]);

        $venta = $this->ventas->registrar($request->user(), $datos['items'], $datos['cliente_id'] ?? null);

        return redirect()->route('ventas.create')
            ->with('ok', "Venta N° {$venta->id} registrada por Bs ".number_format($venta->total, 2, ',', '.'))
            ->with('comprobante', route('ventas.comprobante', $venta));
    }

    /** Comprobante imprimible (RF06.6): el Administrador o el vendedor que hizo la venta. */
    public function comprobante(Request $request, Venta $venta)
    {
        $usuario = $request->user();

        abort_unless(
            $usuario->tieneRol(Rol::ADMINISTRADOR) || $venta->usuario_id === $usuario->id,
            403,
            'No tiene permiso para ver este comprobante.'
        );

        $venta->load('usuario', 'cliente', 'detalles.producto');

        return view('ventas.comprobante', compact('venta'));
    }

    public function anular(Request $request, Venta $venta)
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'max:200']]);

        $this->ventas->anular($venta, $datos['motivo'], $request->user());

        return back()->with('ok', "Venta N° {$venta->id} anulada.");
    }
}
