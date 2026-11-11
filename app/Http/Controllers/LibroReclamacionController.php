<?php

namespace App\Http\Controllers;

use App\Models\LibroReclamacion;
use App\Support\DocumentNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

/**
 * Libro de Reclamaciones virtual (Figma "Libro de Reclamaciones"): formulario → revisión → registro.
 * El borrador vive en la sesión entre el formulario y la confirmación; solo "Confirmar envío" crea la hoja,
 * así un fallo al guardar conserva los datos ("No se pudo enviar") y no consume número de hoja.
 */
class LibroReclamacionController extends Controller
{
    private const DRAFT = 'claim_draft';

    private const LAST = 'claim_last_id';

    public function create(): View
    {
        return view('web.claims.form');
    }

    /** Valida el formulario y muestra la hoja para revisarla antes de enviarla. */
    public function review(Request $request): RedirectResponse
    {
        // Campo trampa: los bots lo llenan, las personas no lo ven. No se guarda nada.
        if (filled($request->input('website'))) {
            return redirect()->route('web.claims.create');
        }

        $required = 'Completa este campo para continuar.';

        $data = $request->validate([
            'consumer_name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'in:DNI,CE,RUC,Pasaporte'],
            'document_number' => ['required', 'string', 'max:12', DocumentNumber::rule($request->input('document_type'))],
            'address' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'order_number' => ['nullable', 'string', 'max:80'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_document_type' => ['nullable', 'in:DNI,CE,RUC,Pasaporte'],
            'guardian_document_number' => ['nullable', 'required_with:guardian_name', 'string', 'max:12', DocumentNumber::rule($request->input('guardian_document_type'))],
            'guardian_contact' => ['nullable', 'required_with:guardian_name', 'string', 'max:255'],
            'claim_type' => ['required', 'in:reclamo,queja'],
            'order_product' => ['required', 'string', 'max:255'],
            'claimed_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'claim_description' => ['required', 'string', 'max:3000'],
            'expected_solution' => ['required', 'string', 'max:2000'],
            'supporting_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'required' => $required,
            'required_with' => $required,
            'document_number.regex' => DocumentNumber::message($request->input('document_type')),
            'guardian_document_number.regex' => DocumentNumber::message($request->input('guardian_document_type')),
            'supporting_document.max' => 'El archivo no debe pesar más de 5 MB.',
            'supporting_document.mimes' => 'Adjunta un PDF o una imagen (JPG, PNG o WebP).',
        ]);

        $this->discardDraftFile();

        $data['supporting_document'] = $request->file('supporting_document')?->store('reclamos/borradores');
        $data['supporting_document_name'] = $request->file('supporting_document')?->getClientOriginalName();
        $request->session()->put(self::DRAFT, $data);

        return redirect()->route('web.claims.review');
    }

    public function showReview(Request $request): View|RedirectResponse
    {
        $draft = $request->session()->get(self::DRAFT);

        return $draft ? view('web.claims.review', ['draft' => $draft]) : redirect()->route('web.claims.create');
    }

    /** "Editar mi solicitud": vuelve al formulario con lo ya escrito (el adjunto se vuelve a elegir). */
    public function edit(Request $request): RedirectResponse
    {
        $draft = $request->session()->get(self::DRAFT);

        if (! $draft) {
            return redirect()->route('web.claims.create');
        }

        $this->discardDraftFile();

        return redirect()->route('web.claims.create')->withInput(collect($draft)->except(['supporting_document', 'supporting_document_name'])->all());
    }

    /** Registra la hoja. Si algo falla no se genera número de hoja y el borrador se conserva. */
    public function store(Request $request): RedirectResponse
    {
        $draft = $request->session()->get(self::DRAFT);

        if (! $draft) {
            return redirect()->route('web.claims.create');
        }

        try {
            $claim = DB::transaction(function () use ($draft): LibroReclamacion {
                $claim = LibroReclamacion::create([
                    'brand' => LibroReclamacion::BRAND,
                    'consumer_name' => $draft['consumer_name'],
                    'document_type' => $draft['document_type'],
                    'document_number' => $draft['document_number'],
                    'address' => $draft['address'],
                    'email' => $draft['email'],
                    'phone' => $draft['phone'] ?? null,
                    'is_minor' => filled($draft['guardian_name'] ?? null),
                    'guardian_name' => $draft['guardian_name'] ?? null,
                    'guardian_document_type' => filled($draft['guardian_name'] ?? null) ? ($draft['guardian_document_type'] ?? 'DNI') : null,
                    'guardian_document_number' => $draft['guardian_document_number'] ?? null,
                    'guardian_contact' => $draft['guardian_contact'] ?? null,
                    'order_number' => $draft['order_number'] ?? null,
                    'order_product' => $draft['order_product'],
                    'claim_product' => $draft['order_product'],
                    'claimed_amount' => $draft['claimed_amount'] ?? null,
                    'claim_type' => $draft['claim_type'],
                    'claim_description' => $draft['claim_description'],
                    'expected_solution' => $draft['expected_solution'],
                ]);

                $claim->sheet_number = LibroReclamacion::sheetNumberFor($claim);

                if (! empty($draft['supporting_document']) && Storage::exists($draft['supporting_document'])) {
                    $target = 'reclamos/'.$claim->sheet_number.'/'.basename($draft['supporting_document']);
                    Storage::move($draft['supporting_document'], $target);
                    $claim->supporting_document = $target;
                }

                $claim->save();

                return $claim;
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('web.claims.error');
        }

        $request->session()->forget(self::DRAFT);
        $request->session()->put(self::LAST, $claim->id);

        return redirect()->route('web.claims.registered');
    }

    public function error(Request $request): View|RedirectResponse
    {
        return $request->session()->has(self::DRAFT) ? view('web.claims.error') : redirect()->route('web.claims.create');
    }

    public function registered(Request $request): View|RedirectResponse
    {
        $claim = $this->lastClaim($request);

        return $claim ? view('web.claims.registered', ['claim' => $claim]) : redirect()->route('web.claims.create');
    }

    /** La constancia solo la ve quien acaba de registrar la hoja (lleva datos personales y el número es correlativo). */
    public function receipt(Request $request): View|RedirectResponse
    {
        $claim = $this->lastClaim($request);

        return $claim ? view('web.claims.receipt', ['claim' => $claim]) : redirect()->route('web.claims.create');
    }

    private function lastClaim(Request $request): ?LibroReclamacion
    {
        $id = $request->session()->get(self::LAST);

        return $id ? LibroReclamacion::find($id) : null;
    }

    private function discardDraftFile(): void
    {
        $path = session(self::DRAFT.'.supporting_document');

        if ($path) {
            Storage::delete($path);
        }
    }
}
