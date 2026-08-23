<?php

namespace App\Http\Controllers;

use App\Exports\ProductsTemplateExport;
use App\Imports\ProductsImport;
use App\Models\Department;
use App\Models\Product;
use App\Models\SaleType;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportController extends Controller
{
    /**
     * Show the bulk import form
     */
    public function create()
    {
        $this->authorize('create', Product::class);

        // Los nombres de Departamento y Tipo Venta del archivo tienen que coincidir
        // con los del sistema: listarlos aqui evita la mitad de los errores de captura.
        return view('products.import', [
            'departments' => Department::orderBy('name')->pluck('name'),
            'saleTypes' => SaleType::orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * Process the uploaded spreadsheet
     */
    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'Debes seleccionar un archivo',
            'file.mimes' => 'El archivo debe ser un Excel (.xlsx, .xls) o CSV',
            'file.max' => 'El archivo no puede pesar más de 10MB',
        ]);

        $import = new ProductsImport;
        Excel::import($import, $request->file('file'));

        // Con errores se regresa al formulario (y no al listado) para poder mostrar
        // el detalle fila por fila y volver a subir el archivo ya corregido.
        if ($import->errorRows > 0) {
            $imported = $import->created + $import->updated;

            $message = $imported === 0
                ? 'No se importó ningún producto: las '.$import->errorRows.' fila(s) del archivo tienen errores.'
                : "Se importaron {$import->created} producto(s) nuevo(s) y se actualizaron {$import->updated}. "
                    .$import->errorRows.' fila(s) con errores fueron omitidas.';

            return redirect()
                ->route('products.import.create')
                ->with($imported === 0 ? 'error' : 'success', $message)
                ->with('importErrors', $import->errors)
                ->with('importErrorRows', $import->errorRows)
                ->with('importErrorSummary', $import->errorSummary);
        }

        return redirect()
            ->route('products.index')
            ->with('success', "Importación completa: {$import->created} producto(s) creado(s), {$import->updated} actualizado(s).");
    }

    /**
     * Download the import template
     */
    public function template()
    {
        $this->authorize('create', Product::class);

        return Excel::download(new ProductsTemplateExport, 'plantilla-productos.xlsx');
    }
}
