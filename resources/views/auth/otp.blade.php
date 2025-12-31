@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-10 p-6 bg-white rounded shadow">
    <h2 class="text-2xl font-bold mb-4">Verifikasi Email</h2>
    <p class="mb-4">Masukkan kode OTP yang dikirim ke email Anda.</p>
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-2 rounded mb-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-2 rounded mb-2">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 text-red-700 p-2 rounded mb-2">
            {{ $errors->first() }}
        </div>
    @endif
    <form method="POST" action="{{ route('auth.otp.verify') }}">
        @csrf
        <div class="mb-4">
            <input type="text" name="otp" maxlength="6" pattern="[0-9]{6}" required autofocus class="w-full border rounded p-2" placeholder="Kode OTP">
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded">Verifikasi</button>
    </form>
    <form method="POST" action="{{ route('auth.otp.resend') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-blue-600 underline">Kirim Ulang OTP</button>
    </form>
</div>
@endsection
