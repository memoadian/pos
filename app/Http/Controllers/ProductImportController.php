<?php

namespace App\Http\Controllers;

use App\Exports\ProductsTemplateExport;
use App\Imports\ProductsImport;
use App\Models\Product;
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

        return view('products.import');
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

        if ($import->created === 0 && $import->updated === 0 && ! empty($import->errors)) {
            return back()->with('error', 'No se importó ningún producto. Revisa los errores del archivo.')
                ->with('importErrors', $import->errors);
        }

        $message = "Importación completa: {$import->created} producto(s) creado(s), {$import->updated} actualizado(s).";

        if (! empty($import->errors)) {
            $message .= ' '.count($import->errors).' fila(s) con errores fueron omitidas.';
        }

        return redirect()
            ->route('products.index')
            ->with('success', $message)
            ->with('importErrors', $import->errors);
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
