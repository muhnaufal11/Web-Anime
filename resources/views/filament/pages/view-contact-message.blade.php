<x-filament::page>
    <div class="space-y-6">
        <!-- Message Info Card -->
        <x-filament::card>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama</p>
                    <p class="text-base font-semibold">{{ $record->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</p>
                    <p class="text-base font-semibold">{{ $record->email }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Subjek</p>
                    <p class="text-base font-semibold">{{ $record->subject }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                    @if($record->is_closed)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            Ditutup
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-300">
                            Aktif
                        </span>
                    @endif
                </div>
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Pesan</p>
                <p class="text-base">{{ $record->created_at->format('d M Y H:i') }}</p>
            </div>
        </x-filament::card>

        <!-- Chat Messages -->
        <x-filament::card>
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <x-heroicon-o-chat-alt-2 class="w-5 h-5 text-primary-500"/>
                Percakapan
            </h3>

            <div class="space-y-4 max-h-[500px] overflow-y-auto p-2" id="admin-chat-messages">
                <!-- Pesan Awal dari User -->
                <div class="flex justify-start">
                    <div class="max-w-[80%]">
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-bl-md px-4 py-3">
                            <p class="whitespace-pre-wrap text-gray-900 dark:text-white">{{ $record->message }}</p>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $record->created_at->format('d M Y H:i') }} • {{ $record->name }} (User)</p>
                    </div>
                </div>

                <!-- Balasan lama dari field reply (backward compat) -->
                @if($record->reply && $replies->isEmpty())
                    <div class="flex justify-end">
                        <div class="max-w-[80%]">
                            <div class="bg-primary-500 text-white rounded-2xl rounded-br-md px-4 py-3">
                                <p class="whitespace-pre-wrap">{{ $record->reply }}</p>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 text-right">{{ $record->replied_at ? $record->replied_at->format('d M Y H:i') : '' }} • Admin</p>
                        </div>
                    </div>
                @endif

                <!-- Replies dari contact_replies -->
                @foreach($replies as $reply)
                    @if($reply->is_admin)
                        <!-- Admin Reply -->
                        <div class="flex justify-end">
                            <div class="max-w-[80%]">
                                <div class="bg-primary-500 text-white rounded-2xl rounded-br-md px-4 py-3">
                                    <p class="whitespace-pre-wrap">{{ $reply->message }}</p>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 text-right">
                                    {{ $reply->created_at->format('d M Y H:i') }} • 
                                    @if($reply->admin)
                                        {{ $reply->admin->name }} (Admin)
                                    @else
                                        Admin
                                    @endif
                                </p>
                            </div>
                        </div>
                    @else
                        <!-- User Reply -->
                        <div class="flex justify-start">
                            <div class="max-w-[80%]">
                                <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-bl-md px-4 py-3">
                                    <p class="whitespace-pre-wrap text-gray-900 dark:text-white">{{ $reply->message }}</p>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ $reply->created_at->format('d M Y H:i') }} • {{ $record->name }} (User)</p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            @if($record->is_closed)
                <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg text-center">
                    <p class="text-gray-600 dark:text-gray-300">
                        <x-heroicon-o-lock-closed class="w-5 h-5 inline mr-1"/>
                        Percakapan ditutup pada {{ $record->closed_at ? $record->closed_at->format('d M Y H:i') : '' }}
                    </p>
                </div>
            @endif
        </x-filament::card>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('admin-chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });
    </script>
</x-filament::page>
