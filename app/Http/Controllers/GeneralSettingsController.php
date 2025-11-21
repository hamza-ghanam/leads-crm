<?php

namespace App\Http\Controllers;

use App\Models\GeneralSettings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GeneralSettingsController extends Controller
{
    /**
     * Create a new OrderController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:super-admin']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $rules = [
            'settings_names' => ['required', 'array', 'min:1'],
            'settings_names.*' => ['required', 'string', Rule::in(GeneralSettings::pluck('name')->toArray())],
            'settings_values' => ['nullable', 'array', 'min:1'],
        ];

        $messages = [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field should be text.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput($request->all());
        }

        foreach ($request->settings_names as $key => $settings_name) {
            $setting = GeneralSettings::whereName($settings_name)->first();

            if (!$setting) {
                return back()
                    ->withErrors(['msg' => 'No such setting!'])
                    ->withInput($request->all());
            }

            switch ($setting->type) {
                case 'bool':
                    $setting->value = !boolval($setting->value);
                    break;
            }

            $setting->save();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Setting successfully saved.']);
        }

        return back()->with('successMsg', 'Setting successfully saved.');
    }
}
