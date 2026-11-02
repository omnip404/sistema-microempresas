<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\CompraService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registro y anulación de compras a proveedores (RF07). La lógica de negocio
 * está en CompraService; el controlador solo valida la entrada y responde.
 */
class CompraController extends Controller
{
    public function __construct(private CompraService $compras) {}

    public function index()
    {
        $compras = Compra::with('usuario', 'proveedor')->latest('fecha')->latest('id')->paginate(20);

        return view('compras.index', compact('compras'));
    }

    public function create()
    {
        return view('compras.create', [
            'proveedores' => Proveedor::where('activo', true)->orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'proveedor_id' => ['required', 'integer', Rule::exists('proveedores', 'id')->where('activo', true)],
            'nro_comprobante' => ['nullable', 'string', 'max:30'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')->where('activo', true)],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.costo_unitario' => ['required', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Agregue al menos un producto a la compra.',
            'items.*.cantidad.min' => 'La cantidad de cada producto debe ser al menos 1.',
            'items.*.costo_unitario.min' => 'El costo unitario no puede ser negativo.',
        ], [
            'proveedor_id' => 'proveedor',
            'nro_comprobante' => 'número de comprobante',
        ]);

        $compra = $this->compras->registrar(
            $request->user(), (int) $datos['proveedor_id'], $datos['items'], $datos['nro_comprobante'] ?? null
        );

        return redirect()->route('compras.index')
            ->with('ok', "Compra N° {$compra->id} registrada por Bs ".number_format($compra->total, 2, ',', '.'));
    }

    public function anular(Request $request, Compra $compra)
    {
        $this->compras->anular($compra, $request->user());

        return back()->with('ok', "Compra N° {$compra->id} anulada.");
    }
}
