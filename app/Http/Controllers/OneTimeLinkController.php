<?php

namespace App\Http\Controllers;

use App\Mail\BrokerAgreementMail;
use App\Mail\OneTimeLinkMail;
use App\Models\Broker;
use App\Models\OneTimeLink;
use App\Models\User;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Response;

class OneTimeLinkController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} requested OTL listing.");

        if ($user->getRoleNames()[0] !== 'super-admin') {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $limit = min((int)$request->get('limit', 10), 100);

        $otls = OneTimeLink::with('user')
            ->latest()      // defaults to ordering by created_at DESC
            ->paginate($limit);

        return view('notifications.index', compact('otls'));
    }

    /**
     * @throws RandomException
     */
    public function generateLink(Request $request)
    {
        $user = $request->user();
        Log::info("User {$user->id} is generating a one-time link.");

        // If you have permission logic:
        if ($user->getRoleNames()[0] !== 'super-admin') {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            // 'user_type' => 'required|string|in:Broker,Contractor',
            'email' => 'required|email|max:255|unique:brokers,email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput($request->all());
        }

        $data = $validator->validated();

        // Generate a random token
        do {
            $token = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $token);
            $exists = OneTimeLink::where('token', $hashedToken)->exists();
        } while ($exists);

        $otl = OneTimeLink::create([
            'token' => $hashedToken,
            'user_type' => 'Broker', // "Broker" or "Contractor"
            'expired_at' => null, // link is valid until used
            'generated_by' => $user->id,
        ]);

        //Email
        Mail::to($request->email)->queue(new OneTimeLinkMail($otl, $token));

        session()->flash('successMsg', 'One-time link successfully generated and shared by email.');

        return redirect()->route('brokers.index');
    }
}
