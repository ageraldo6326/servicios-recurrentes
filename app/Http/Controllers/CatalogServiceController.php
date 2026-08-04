<?php

namespace App\Http\Controllers;

use App\Models\CatalogService;
use App\Http\Requests\CatalogServiceRequest;
use Illuminate\Http\Request;

class CatalogServiceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $services = CatalogService::withCount('contractedServices')->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))->orderBy('name')->paginate(15)->withQueryString();

        return view('catalog-services.index', compact('services', 'search'));
    }

    public function create()
    {
        return view('catalog-services.form', ['service' => new CatalogService(['is_active' => true])]);
    }

    public function store(CatalogServiceRequest $request)
    {
        CatalogService::create($request->validated());

        return redirect()->route('catalog-services.index')->with('success', 'Servicio creado.');
    }

    public function edit(CatalogService $catalogService)
    {
        return view('catalog-services.form', ['service' => $catalogService]);
    }

    public function update(CatalogServiceRequest $request, CatalogService $catalogService)
    {
        $catalogService->update($request->validated());

        return redirect()->route('catalog-services.index')->with('success', 'Servicio actualizado.');
    }
}
