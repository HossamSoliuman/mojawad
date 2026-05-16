<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\{Auth,Hash};
class RegisteredUserController extends Controller {
    public function create() { return view('auth.register'); }
    public function store(RegisterRequest $request) {
        $user=User::create(['name'=>$request->name,'email'=>$request->email,'password'=>Hash::make($request->password)]);
        $user->assignRole('user');
        event(new Registered($user));
        Auth::login($user);
        return redirect(route('home'));
    }
}
