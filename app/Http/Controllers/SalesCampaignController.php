<?php

namespace App\Http\Controllers;

use App\Models\GeneralSettings;
use App\Models\SalesCampaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
     * @param string $status
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('salesCamps.index')->with([
            'salesCamps' => SalesCampaign::all(),
            'sales' => User::role(['sale', 'tele-sale'])->get(),
            'use_camps' => boolval(GeneralSettings::whereName('use_camps')->first()->value),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
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
