<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{

    /**
     * Create a new OrderController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        parent::hasPermission('list users');

        $fullName = $request->input('fullName', '');
        $email    = $request->input('email', '');
        $role     = $request->input('role', '');
        $status   = $request->input('status', '');

        $users = User::withTrashed()
            ->when($fullName, fn($q) => $q->where('name', 'like', "%{$fullName}%"))
            ->when($email,    fn($q) => $q->where('email', 'like', "%{$email}%"))
            ->when($role,     fn($q) => $q->whereHas('roles', fn($q) => $q->where('name', $role)))
            ->when($status === 'deleted',  fn($q) => $q->whereNotNull('deleted_at'))
            ->when($status === 'permitted', fn($q) => $q->whereNull('deleted_at')->where('status', 'permitted'))
            ->when($status === 'banned',    fn($q) => $q->whereNull('deleted_at')->where('status', 'banned'))
            ->orderBy('deleted_at', 'ASC')
            ->orderBy('created_at', 'DESC')
            ->get();

        foreach ($users as $user) {
            $user->role = count($user->roles) > 0 ? $user->roles[0]->name : '-';
        }

        $roles = Role::orderBy('name')->pluck('name');

        return view('users.index', compact('users', 'roles', 'fullName', 'email', 'role', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        parent::hasPermission('add user');

        $roles = Role::all();

        $salesManagers = User::role('sales-manager')->get();
        return view('users.create')->with(['roles' => $roles, 'salesManagers' => $salesManagers]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        parent::hasPermission('add user');

        $emailExist = User::where('email', $request->email)->first();
        $emailExistOrig = $emailExist ? $emailExist->email : '';

        if ($emailExist) {
            $emailExist->email = $emailExist->email . '_old';
            $emailExist->save();
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'integer'],
            'manager' => ['nullable', 'integer'],
        ];

        $messages = [
            'required' => 'The :attribute field is required.',
            'integer' => 'The :attribute field should be integer.',
            'string' => 'The :attribute field should be string.',
            'gt:0' => 'The :attribute field should be positive.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            if ($emailExist) {
                $emailExist->email = $emailExistOrig;
                $emailExist->save();
            }

            return back()->withErrors($validator->errors())->withInput($request->all());
        }

        $createdUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'manager_id' => $request->manager
        ]);

        $role = Role::find($request->role);

        if (!$role) {
            if ($emailExist) {
                $emailExist->email = $emailExistOrig;
                $emailExist->save();
            }

            return back()->withErrors(['msg' => 'Role is not exists'])->withInput($request->all());
        }

        $createdUser->assignRole($role->name);
        $createdUser->save();

        if ($createdUser) {
            return redirect()->route('users.all');
        }

        if ($emailExist) {
            $emailExist->email = $emailExistOrig;
            $emailExist->save();
        }

        return back()->withErrors(['msg' => 'Unable to create user.'])->withInput($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        parent::hasPermission('show user');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        parent::hasPermission('edit user');

        $user = User::withTrashed()->find($id);

        if (!$user) {
            return back()->withErrors(['msg' => 'User is not exists']);
        }

        $user->role = $user->roles->pluck('id')->toArray()[0];
        $salesManagers = User::role('sales-manager')->get();

        return view('users.edit')->with(['user' => $user, 'roles' => Role::all(), 'salesManagers' => $salesManagers]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        parent::hasPermission('edit user');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'integer'],
            'manager' => ['nullable', 'integer'],
        ];

        $messages = [
            'required' => 'The :attribute field is required.',
            'integer' => 'The :attribute field should be integer.',
            'string' => 'The :attribute field should be string.',
            'gt:0' => 'The :attribute field should be positive.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput($request->all());
        }

        $email = User::where('id', '!=', $id)->whereEmail($request->email)->first();

        if ($email) {
            return back()->withErrors(['msg' => 'The email has already been taken.']);
        }

        $user = User::withTrashed()->find($id);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->manager_id = $request->manager;

        $role = Role::find($request->role);

        if (!$role) {
            return back()->withErrors(['msg' => 'Role does not exist!'])->withInput($request->all());
        }

        $user->roles()->detach();
        $user->forgetCachedPermissions();

        $user->assignRole($role->name);

        if (isset($request->password) and $request->password !== '') {
            $user->password = Hash::make($request->password);
        }

        if (!$request->ban_check) {
            $user->status = 'banned';
        } else {
            $user->status = 'permitted';
        }

        if (!$request->delete_check) {
            User::destroy($id);
        } else {
            User::onlyTrashed()->where('id', $id)->restore();
        }

        $user->save();
        return redirect()->route('users.edit', $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroyRestore($id)
    {
        parent::hasPermission('delete user');

        $user = User::withTrashed()->find($id);

        if (!$user) {
            return response()->json(['error' => 'No such user.'], 404);
        }

        if (!$user->deleted_at) {
            User::destroy($id);
        } else {
            $user = $user->restore();
        }

        return response()->json(['OK' => 'Deleted. ' . $id], 200);
    }

    /**
     * Ban a user.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function toggleBan($id)
    {
        parent::hasPermission('edit user');

        $user = User::find($id);

        if (!$user) {
            return back()->withErrors(['msg' => 'User is not exists']);
        }

        if ($user->status == 'permitted') {
            $user->status = 'banned';
        } else {
            $user->status = 'permitted';
        }

        $user->save();

        return redirect()->route('users.all');
    }
}
