<div class="space-y-4">
    {{-- Info Box --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">📋 Aturan Pembayaran (Pasal 3)</h3>
        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
            <li>• <strong>Jadwal:</strong> Gaji dibayarkan tanggal 25 setiap bulan</li>
            <li>• <strong>Limit Admin Sync Level 1:</strong> Max Rp 300.000/bulan</li>
            <li>• <strong>Limit Admin Upload Level 1:</strong> Max Rp 500.000/bulan</li>
            <li>• <strong>Rollover:</strong> Kelebihan limit tidak hangus, dibayar bulan depan</li>
            <li>• <strong>Kenaikan Level:</strong> Setelah evaluasi 3 bulan, limit bisa dinaikkan</li>
        </ul>
    </div>
    
    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Admin</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Level</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Limit</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pending</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Rollover</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dapat Dibayar</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sisa</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @php
                    $totalPayable = 0;
                    $totalRollover = 0;
                @endphp
                @foreach($admins as $admin)
                    @php
                        $calc = $admin->calculateMonthlyPayment(now()->year, now()->month);
                        $totalPayable += $calc['payable'];
                        $totalRollover += $calc['rollover_to_next'];
                    @endphp
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ $admin->name }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 text-xs rounded-full {{ $admin->role === 'admin_upload' ? 'bg-blue-100 text-blue-800' : 'bg-cyan-100 text-cyan-800' }}">
                                {{ $admin->role === 'admin_upload' ? 'Upload' : 'Sync' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 text-xs rounded-full {{ $admin->admin_level === 0 ? 'bg-green-100 text-green-800' : ($admin->admin_level === 2 ? 'bg-purple-100 text-purple-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ $admin->getAdminLevelLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-500 dark:text-gray-400">
                            {{ $calc['limit'] ? 'Rp ' . number_format($calc['limit'], 0, ',', '.') : '∞' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                            Rp {{ number_format($calc['unpaid_this_month'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ $calc['rollover_from_previous'] > 0 ? 'text-orange-600' : 'text-gray-400' }}">
                            {{ $calc['rollover_from_previous'] > 0 ? 'Rp ' . number_format($calc['rollover_from_previous'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold text-green-600">
                            Rp {{ number_format($calc['payable'], 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ $calc['rollover_to_next'] > 0 ? 'text-orange-600 font-medium' : 'text-gray-400' }}">
                            {{ $calc['rollover_to_next'] > 0 ? 'Rp ' . number_format($calc['rollover_to_next'], 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-100 dark:bg-gray-800">
                <tr>
                    <td colspan="6" class="px-4 py-3 text-sm font-bold text-right text-gray-900 dark:text-white">
                        TOTAL:
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold text-green-600">
                        Rp {{ number_format($totalPayable, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold text-orange-600">
                        Rp {{ number_format($totalRollover, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    {{-- Countdown --}}
    @php
        $daysUntilPayday = now()->day <= 25 ? 25 - now()->day : now()->daysInMonth - now()->day + 25;
    @endphp
    <div class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">
        @if($daysUntilPayday == 0)
            🎉 <strong>Hari ini adalah hari gajian!</strong> (Tanggal 25)
        @else
            📅 <strong>{{ $daysUntilPayday }} hari lagi</strong> sampai hari gajian (Tanggal 25)
        @endif
    </div>
</div>
