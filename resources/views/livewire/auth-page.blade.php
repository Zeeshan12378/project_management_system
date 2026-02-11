<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
new class extends Component
{
    // Mode: login or register
    public string $mode = 'login';

    // Shared fields
    public string $email = '';
    public string $password = '';

    // Register fields
    public string $name = '';
    public string $password_confirmation = '';

    /**
     * Real-time validation rules
     */
    public function rules()
    {
        if ($this->mode === 'register') {
            return [
                'name' => 'required|min:3',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed',
            ];
        }

        return [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ];
    }

    /**
     * Real-time validation
     */
    public function updated($property)
    {
        $this->validateOnly($property);
    }

    /**
     * Switch mode
     */
    public function switchMode($mode)
    {
        $this->resetValidation();
        $this->mode = $mode;
    }

    /**
     * Register
     */
    public function register()
    {
        $validated = $this->validate();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /**
     * Login
     */
    public function login()
    {
        $validated = $this->validate();

        if (!Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ])) {
            $this->addError('email', 'Invalid credentials.');
            return;
        }

        request()->session()->regenerate();

        return redirect()->route('dashboard');
    }
};
?>

<div class="flex justify-center items-center min-h-screen">

    <div class="w-full max-w-md bg-white shadow-lg rounded-xl p-6">

        <!-- Tabs -->
        <div class="flex justify-between mb-6">
            <button
                wire:click="switchMode('login')"
                class="w-1/2 py-2 font-bold rounded-l-lg
                {{ $mode === 'login' ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">
                Login
            </button>

            <button
                wire:click="switchMode('register')"
                class="w-1/2 py-2 font-bold rounded-r-lg
                {{ $mode === 'register' ? 'bg-green-600 text-white' : 'bg-gray-200' }}">
                Signup
            </button>
        </div>

        <!-- Login Form -->
        @if($mode === 'login')
            <form wire:submit.prevent="login" class="space-y-4">

                <div>
                    <label>Email</label>
                    <input type="email" wire:model.live="email"
                        class="w-full border p-2 rounded">

                    @error('email') <p class="text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label>Password</label>
                    <input type="password" wire:model.live="password"
                        class="w-full border p-2 rounded">

                    @error('password') <p class="text-red-500">{{ $message }}</p> @enderror
                </div>

                <button class="w-full bg-blue-600 text-white py-2 rounded">
                    Login
                </button>
            </form>
        @endif

        <!-- Register Form -->
        @if($mode === 'register')
            <form wire:submit.prevent="register" class="space-y-4">

                <div>
                    <label>Name</label>
                    <input type="text" wire:model.live="name"
                        class="w-full border p-2 rounded">

                    @error('name') <p class="text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label>Email</label>
                    <input type="email" wire:model.live="email"
                        class="w-full border p-2 rounded">

                    @error('email') <p class="text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label>Password</label>
                    <input type="password" wire:model.live="password"
                        class="w-full border p-2 rounded">

                    @error('password') <p class="text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label>Confirm Password</label>
                    <input type="password" wire:model.live="password_confirmation"
                        class="w-full border p-2 rounded">
                </div>

                <button class="w-full bg-green-600 text-white py-2 rounded">
                    Signup
                </button>
            </form>
        @endif

    </div>
</div>

