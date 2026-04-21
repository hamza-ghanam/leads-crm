<?php

namespace App\Http\Controllers;

use App\Models\GeneralSettings;
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

    public function index()
    {
        $settings = GeneralSettings::orderBy('id')->get();
        $settingsByName = $settings->keyBy('name');

        $working_days = [];
        if (!empty($settingsByName['working_days']->value ?? null)) {
            $working_days = json_decode(
                $settingsByName['working_days']->value,
                true
            ) ?? [];
        }

        return view('settings.general', compact(
            'settings',
            'settingsByName',
            'working_days'
        ));
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
        $allowedNames = GeneralSettings::pluck('name')->toArray();
        $allowedDays = ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];

        $rules = [
            'settings_values' => ['required', 'array', 'min:1'],
            'settings_values.*' => ['nullable'],
        ];

        foreach (GeneralSettings::get(['name', 'type']) as $setting) {
            $key = "settings_values.{$setting->name}";

            if ($setting->type === 'time') {
                $rules[$key] = ['nullable', 'date_format:H:i'];

            } elseif ($setting->type === 'bool') {
                $rules[$key] = ['nullable', Rule::in(['0', '1', 0, 1, true, false])];

            } elseif ($setting->type === 'json') {
                $rules[$key] = ['nullable', 'array'];

                if ($setting->name === 'working_days') {
                    $rules["{$key}.*"] = ['required', Rule::in($allowedDays)];
                }

            } else {
                $rules[$key] = ['nullable', 'string'];
            }
        }

        $incomingKeys = array_keys($request->input('settings_values', []));
        $unknownKeys = array_diff($incomingKeys, $allowedNames);
        if (!empty($unknownKeys)) {
            return back()
                ->withErrors(['msg' => 'Invalid setting key(s): ' . implode(', ', $unknownKeys)])
                ->withInput();
        }

        $messages = [
            'settings_values.required' => 'Settings are required.',
            'settings_values.array' => 'Settings values must be an array.',
            'date_format' => 'Time must be in HH:MM format.',
            'in' => 'Invalid value.',
            'string' => 'The value must be text.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput($request->all());
        }

        $settingsMap = GeneralSettings::whereIn('name', $incomingKeys)->get()->keyBy('name');

        foreach ($incomingKeys as $name) {
            $setting = $settingsMap->get($name);
            if (!$setting) {
                continue;
            }

            $value = $request->input("settings_values.$name");

            switch ($setting->type) {
                case 'bool':
                    $setting->value = ($value == '1') ? '1' : '0';
                    break;

                case 'time':
                    $setting->value = $value;
                    break;

                case 'json':
                    $values = array_values(array_unique(
                        $request->input("settings_values.$name", [])
                    ));
                    $setting->value = json_encode($values, JSON_THROW_ON_ERROR);

                    break;

                default:
                    $setting->value = is_null($value) ? null : (string)$value;
                    break;
            }

            $setting->save();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Settings successfully saved.']);
        }

        return back()->with('successMsg', 'Setting successfully saved.');
    }
}
