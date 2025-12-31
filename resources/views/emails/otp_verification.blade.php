@component('mail::message')
# Verifikasi Email NipNime

Halo, {{ $user->name }}!

Kode OTP verifikasi email Anda adalah:

# <span style="font-size:2em; letter-spacing: 0.2em;">{{ $otp }}</span>

Kode ini berlaku selama 15 menit.

Jika Anda tidak meminta kode ini, abaikan email ini.

Terima kasih,
Tim NipNime
@endcomponent
