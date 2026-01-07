<div class="space-y-4 text-sm">
    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-3">
        <div class="font-semibold text-gray-800 dark:text-gray-100">Ringkasan Pembayaran</div>
        <div class="flex justify-between text-gray-700 dark:text-gray-200">
            <span>Total dibayar:</span>
            <span class="font-bold text-green-600">Rp {{ number_format($summary['total_payable'], 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between text-gray-700 dark:text-gray-200">
            <span>Total rollover ke depan:</span>
            <span class="font-semibold text-orange-600">Rp {{ number_format($summary['total_rollover'], 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">
        <table class="min-w-full text-xs">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-200">
                <tr>
                    <th class="px-3 py-2 text-left">Admin</th>
                    <th class="px-3 py-2 text-left">Role</th>
                    <th class="px-3 py-2 text-left">Level</th>
                    <th class="px-3 py-2 text-right">Payable</th>
                    <th class="px-3 py-2 text-right">Rollover</th>
                    <th class="px-3 py-2 text-left">Metode</th>
                    <th class="px-3 py-2 text-left">Bank/Provider</th>
                    <th class="px-3 py-2 text-left">No Akun/Wallet</th>
                    <th class="px-3 py-2 text-left">Atas Nama</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($admins as $admin)
                <tr class="text-gray-800 dark:text-gray-100">
                    <td class="px-3 py-2">{{ $admin['name'] }}</td>
                    <td class="px-3 py-2">{{ $admin['role'] === 'admin_upload' ? 'Upload' : 'Sync' }}</td>
                    <td class="px-3 py-2">{{ $admin['level'] }}</td>
                    <td class="px-3 py-2 text-right font-semibold text-green-600">Rp {{ number_format($admin['payable'], 0, ',', '.') }}</td>
                    <td class="px-3 py-2 text-right text-orange-600">{{ $admin['rollover'] > 0 ? 'Rp ' . number_format($admin['rollover'], 0, ',', '.') : '-' }}</td>
                    <td class="px-3 py-2">{{ $admin['method'] ?? '-' }}</td>
                    <td class="px-3 py-2">{{ $admin['provider'] ?? '-' }}</td>
                    <td class="px-3 py-2">{{ $admin['number'] ?? '-' }}</td>
                    <td class="px-3 py-2">{{ $admin['holder'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
