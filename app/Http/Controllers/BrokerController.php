<?php

namespace App\Http\Controllers;

use App\Mail\BrokerAgreementMail;
use App\Mail\BrokerSignedAgreementMail;
use App\Models\Broker;
use App\Models\BrokerDoc;
use App\Models\OneTimeLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BrokerController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin|admin')
            ->except(['registerForm', 'register', 'uploadSignedAgreementForm', 'uploadSignedAgreement']);
    }

    public function index(Request $request)
    {
        $fullName = $request->input('fullName', '');
        $phone    = $request->input('phone', '');
        $email    = $request->input('email', '');
        $type     = $request->input('type', '');
        $from     = $request->input('from', '');
        $to       = $request->input('to', '');

        $brokers = Broker::query()
            ->when($fullName, fn($q) => $q->where('full_name', 'like', "%{$fullName}%"))
            ->when($phone,    fn($q) => $q->where('phone', 'like', "%{$phone}%"))
            ->when($email,    fn($q) => $q->where('email', 'like', "%{$email}%"))
            ->when($type,     fn($q) => $q->where('type', $type))
            ->when($from,     fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to,       fn($q) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(50)
            ->appends($request->query());

        return view('brokers.index', compact('brokers', 'fullName', 'phone', 'email', 'type', 'from', 'to'));
    }

    public function show($id)
    {
        $broker = Broker::with('docs')->findOrFail($id);

        $countriesById = DB::table('countries')->pluck('name', 'id');

        return view('brokers.show', compact('broker', 'countriesById'));
    }

    public function create()
    {
        return view('brokers.create');
    }

    public function registerForm(string $token)
    {
        $this->verifyToken($token, 'Broker');

        $countries = DB::table('countries')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();


        return view('brokers.register', compact('token', 'countries'));
    }

    public function register(Request $request, string $token)
    {
        // 1) Basic validation
        $baseData = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'email' => 'required|email|unique:brokers,email',
            'id_type' => 'required|string|in:ID,Passport',
            'id_number' => ['required', 'regex:/^[A-Za-z0-9\-]+$/', 'max:50'],
            'type' => 'required|string|in:Company,Individual',
            'nationality' => ['required', 'exists:countries,id'],
            'address' => 'required|string|max:255',

            'company_name' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255',
        ]);

        // 2) Type-based rules (match your UI)
        $type = $baseData['type'];

        $typeRules = [
            'company_name' => $type === 'Company' ? 'required|string|max:255' : 'nullable|string|max:255',
            'license_number' => $type === 'Company' ? 'required|string|max:255' : 'nullable|string|max:255',

            'trade_license' => $type === 'Company'
                ? 'required|file|mimes:pdf,jpg,jpeg,png|max:2048'
                : 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',

            'id_document' => $type === 'Individual'
                ? 'required|file|mimes:pdf,jpg,jpeg,png|max:2048'
                : 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];

        $validated = array_merge($baseData, $request->validate($typeRules));

        DB::beginTransaction();

        try {
            // ✅ MUST be inside transaction if verifyToken uses lockForUpdate()
            $otl = $this->verifyToken($token, 'Broker');
            $userType = $otl->user_type;

            do {
                $otp = random_int(100000, 999999);
                $hashedOtp = Hash::make($otp);
                $exists = Broker::where('otp', $hashedOtp)->exists();
            } while ($exists);

            // 4) Create broker
            $broker = Broker::create([
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'],
                'nationality' => $validated['nationality'],
                'type' => $validated['type'],
                'id_type' => $validated['id_type'],
                'id_number' => $validated['id_number'],
                'company_name' => $validated['company_name'] ?? null,
                'address' => $validated['address'],
                'license_number' => $validated['license_number'] ?? null,
                'otp' => $hashedOtp,
            ]);

            // 6) Upload docs
            $documents = [
                'trade_license' => 'trade_license',
                'id_document' => 'id_document',
            ];

            foreach ($documents as $inputName => $docType) {
                if ($request->hasFile($inputName)) {
                    $path = $request->file($inputName)->store('docs', 'local');

                    $broker->docs()->create([
                        'doc_type' => $docType,
                        'file_path' => $path,
                    ]);
                }
            }

            // 7) Mark OTL as used (polymorphic)
            $otl->linkable()->associate($broker);

            // اختيارياً (إذا مصرّ تستخدم expired_at كـ "وقت الاستخدام")
            $otl->expired_at = now();

            $otl->save();

            // 8) Generate agreement PDF + store + save doc record
            $pdf = PDF::loadView('pdf.broker_agreement', [
                'user' => $broker,
                'userType' => 'Broker',
            ], [
                'autoLangToFont' => true,
                'autoScriptToLang' => true,
                'directionality' => 'rtl',
            ]);

            $pdfContent = $pdf->output();
            $pdfName = "broker_agreement_{$broker->id}.pdf";

            Storage::disk('local')->put("agreements/{$pdfName}", $pdfContent);

            $broker->docs()->create([
                'doc_type' => 'agreement',
                'file_path' => "agreements/{$pdfName}",
            ]);

            DB::commit();

            try {
                Mail::to($validated['email'])->queue(new BrokerAgreementMail($broker, $pdfName, $otp));
            } catch (Throwable $mailEx) {
                report($mailEx);
            }

            return redirect()
                ->route('brokers.register.success')
                ->with('registration_success', true);

        } catch (Throwable $ex) {
            DB::rollBack();
            report($ex);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'general' => 'Something went wrong while completing the registration. Please try again.'
                ]);
        }
    }

    public function uploadSignedAgreementForm()
    {
        return view('brokers.submit-agreement');
    }

    public function uploadSignedAgreement(Request $request)
    {
        // 1) Validation
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'signed_agreement' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
        ]);

        DB::beginTransaction();

        try {
            // 2) Verify broker by email + OTP
            $broker = $this->verifyBroker(
                $validated['email'],
                $validated['otp']
            );

            // 3) Store signed agreement
            $file = $request->file('signed_agreement');

            $filename = 'signed_broker_agreement_' . $broker->id . '.' . $file->getClientOriginalExtension();

            $path = $file->storeAs(
                'agreements/signed',
                $filename,
                'local'
            );;

            $broker->docs()->create([
                'doc_type' => 'signed_agreement',
                'file_path' => $path,
            ]);

            // 4) Consume OTP (one-time)
            $broker->update([
                'otp' => null,
            ]);

            DB::commit();

            try {
                Mail::to($validated['email'])->queue(new BrokerSignedAgreementMail($broker));
            } catch (Throwable $mailEx) {
                report($mailEx);
            }

            // 5) Success response (web)
            return redirect()
                ->route('brokers.agreement.success')
                ->with('agreement_success', true);

        } catch (Throwable $ex) {
            DB::rollBack();
            report($ex);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'general' => 'Something went wrong while completing the registration. Please try again.'
                ]);
        }
    }

    /**
     * @throws Throwable
     */
    public function destroy($id)
    {
        if (!auth()->user()->hasAnyRole(['super-admin', 'admin'])) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $broker = Broker::with('docs')->find($id);

        if (!$broker) {
            return response()->json(['error' => 'No such broker.'], 404);
        }

        DB::transaction(function () use ($broker) {

            foreach ($broker->docs as $doc) {
                if ($doc->file_path && Storage::exists($doc->file_path)) {
                    Storage::delete($doc->file_path);
                }
            }

            $broker->docs()->delete();

            $broker->delete();
        });

        return response()->json(['OK' => 'Broker deleted successfully.'], 200);
    }

    public function download($docId)
    {
        $doc = BrokerDoc::findOrFail($docId);

        $path = $doc->file_path;

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download($path);
    }

    private function verifyToken(string $token, string $expectedUserType): OneTimeLink
    {
        $hashedIncoming = hash('sha256', $token);

        $otl = OneTimeLink::where('token', $hashedIncoming)
            ->lockForUpdate()
            ->first();

        if (!$otl) {
            abort(403, 'Invalid link');
        }

        if ($otl->linkable_id !== null) {
            abort(403, 'Link already used');
        }

        if ($otl->user_type !== $expectedUserType) {
            abort(403, 'Invalid link type');
        }

        return $otl;
    }

    private function verifyBroker(string $email, string $otp): Broker
    {
        $broker = Broker::where('email', $email)->first();

        if (!$broker) {
            abort(Response::HTTP_FORBIDDEN, 'Invalid email or OTP');
        }

        return $broker;
    }
}
