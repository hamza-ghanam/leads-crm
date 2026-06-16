<?php

namespace App\Http\Controllers;

use App\Models\GeneralSettings;
use App\Models\SalesCampaign;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalesCampaignController extends Controller
{
    /**
     * Create a new OrderController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth','role:super-admin']);
    }

    /**
     * Display a listing of the resource.
     * @return Factory|\Illuminate\Contracts\View\View|View
     */
    public function index()
    {
        $key = 'use_camps';
        $campAssignValue = optional(
            GeneralSettings::whereName($key)->first()
        )->value ?? '0';

        return view('salesCamps.index')->with([
            'salesCamps' => SalesCampaign::with('user')->get(),
            'sales' => User::role(['sale', 'tele-sale'])->get(),
            'camp_assign_key' => $key,
            'camp_assign_value' => $campAssignValue, // 0 or 1
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id)
    {
        $salesCamp = SalesCampaign::find($id);

        if (!$salesCamp) {
            return response()->json(['error' => 'No such record.'], 404);
        }

        SalesCampaign::destroy($id);

        session(['successMsg' => 'Record has been deleted!']);

        return response()->json(['OK' => 'Deleted. ' . $id], 200);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $rules = [
            'sales' => ['required', 'integer', Rule::in(User::role(['sale', 'tele-sale'])
                ->pluck('id')
                ->toArray())],
            'campaign_name' => ['required', 'string', 'max:255'],
        ];

        $messages = [
            'required' => 'The :attribute field is required.',
            'min' => 'Select at least one lead.',
            'gt:0' => 'The :attribute field should be positive.',
            'integer' => 'The :attribute field should be integer.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput($request->all());
        }

        $salesCamp = SalesCampaign::where('user_id', $request->sales)
            ->where('campaign_name', $request->campaign_name)
            ->get();

        if (count($salesCamp) > 0) {
            return back()->withErrors(['msg' => 'This user is already assigned to this campaign.']);
        }

        $salesCamp = SalesCampaign::create([
            'user_id' => $request->sales,
            'campaign_name' => $request->campaign_name,
        ]);

        $salesCamp = $salesCamp->save();

        if ($salesCamp) {
            return back()->with('successMsg', 'User successfully assigned to campaign.');
        } else {
            return back()->withErrors(['msg' => 'An error occurred (E-SC-001).']);
        }
    }
}
