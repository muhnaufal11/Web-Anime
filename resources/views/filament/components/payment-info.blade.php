<div class="space-y-2 text-sm">
    <div class="grid grid-cols-2 gap-2">
        <div class="text-gray-500">Belum dibayar bulan ini:</div>
        <div class="font-medium">Rp {{ number_format($unpaid ?? $pending ?? 0, 0, ',', '.') }}</div>
        
        <div class="text-gray-500">Rollover bulan lalu:</div>
        <div class="font-medium {{ $rollover > 0 ? 'text-orange-600' : '' }}">Rp {{ number_format($rollover, 0, ',', '.') }}</div>
        
        <div class="text-gray-500">Total tersedia:</div>
        <div class="font-semibold">Rp {{ number_format($total, 0, ',', '.') }}</div>
        
        <div class="text-gray-500">Limit bulanan:</div>
        <div class="font-medium">{{ $limit }}</div>
    </div>
    
    <hr class="border-gray-200">
    
    <div class="grid grid-cols-2 gap-2">
        <div class="text-gray-500">Dapat dicairkan:</div>
        <div class="font-bold text-green-600">Rp {{ number_format($payable, 0, ',', '.') }}</div>
        
        @if($nextRollover > 0)
        <div class="text-gray-500">Rollover ke bulan depan:</div>
        <div class="font-medium text-orange-600">Rp {{ number_format($nextRollover, 0, ',', '.') }}</div>
        @endif
    </div>
    
    <div class="mt-2 text-xs text-gray-400">
        @if($daysUntilPayday == 0)
            🎉 Hari ini adalah hari gajian! (Tanggal 25)
        @else
            📅 {{ $daysUntilPayday }} hari lagi sampai hari gajian (Tanggal 25)
        @endif
    </div>
</div>
