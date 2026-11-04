<?php

namespace App\Http\Controllers;

use App\Models\LibroReclamacion;
use App\Support\DocumentNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibroReclamacionController extends Controller
{
    public function create(): View
    {
        return view('web.libro-reclamaciones');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'consumer_name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'in:DNI,CE,RUC,Pasaporte'],
            'document_number' => ['required', 'string', 'max:12', DocumentNumber::rule($request->input('document_type'))],
            'address' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'is_minor' => ['required', 'boolean'],
            'guardian_name' => ['nullable', 'required_if:is_minor,1', 'string', 'max:255'],
            'guardian_document_type' => ['nullable', 'required_if:is_minor,1', 'in:DNI,CE,RUC,Pasaporte'],
            'guardian_document_number' => ['nullable', 'required_if:is_minor,1', 'string', 'max:12', DocumentNumber::rule($request->input('guardian_document_type'))],
            'receipt_type' => ['required', 'in:Boleta,Factura,Ticket,Otro'],
            'order_number' => ['required', 'string', 'max:80'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'purchase_channel' => ['required', 'string', 'max:80'],
            'claimed_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'order_product' => ['required', 'string', 'max:255'],
            'order_description' => ['nullable', 'string', 'max:2000'],
            'claim_type' => ['required', 'in:reclamo,queja'],
            'expected_solution' => ['required', 'string', 'max:2000'],
            'claim_product' => ['required', 'string', 'max:255'],
            'claim_description' => ['required', 'string', 'max:3000'],
        ], [
            'document_number.regex' => DocumentNumber::message($request->input('document_type')),
            'guardian_document_number.regex' => DocumentNumber::message($request->input('guardian_document_type')),
        ]);

        LibroReclamacion::create($validated);

        return redirect()
            ->route('web.claims.create')
            ->with('claim_submitted', true);
    }
}
