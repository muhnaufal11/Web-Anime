<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to;
    public $mailable;
    public $tries = 3; // Coba 3x sebelum fail
    public $timeout = 60; // Timeout 60 detik

    /**
     * Create a new job instance.
     */
    public function __construct($to, $mailable)
    {
        $this->to = $to;
        $this->mailable = $mailable;
    }

    /**
     * Execute the job dengan failover system.
     */
    public function handle()
    {
        // Daftar provider email berurutan (utama -> backup)
        $mailers = ['smtp', 'smtp_backup', 'smtp_backup2'];
        
        foreach ($mailers as $index => $mailer) {
            try {
                // Cek apakah mailer tersedia di config
                if (!config("mail.mailers.{$mailer}")) {
                    Log::warning("Mailer {$mailer} not configured, skipping...");
                    continue;
                }

                // Coba kirim email dengan mailer ini
                Mail::mailer($mailer)->to($this->to)->send($this->mailable);
                
                // Jika berhasil, log dan keluar
                if ($index > 0) {
                    Log::warning("✅ Email sent using BACKUP mailer: {$mailer} for {$this->to}");
                } else {
                    Log::info("✅ Email sent using primary mailer for {$this->to}");
                }
                
                return; // Berhasil, stop disini
                
            } catch (\Exception $e) {
                // Log error dari provider yang gagal
                $errorMsg = $e->getMessage();
                Log::error("❌ Failed to send email with {$mailer}: {$errorMsg}");
                
                // Cek apakah ini error quota limit
                if (str_contains($errorMsg, 'daily email sending quota') || 
                    str_contains($errorMsg, 'quota exceeded') ||
                    str_contains($errorMsg, '550')) {
                    Log::warning("⚠️ Quota limit detected on {$mailer}, trying backup...");
                }
                
                // Jika ini bukan provider terakhir, lanjut ke backup berikutnya
                if ($index < count($mailers) - 1) {
                    Log::info("🔄 Switching to next backup mailer...");
                    continue;
                }
                
                // Jika semua provider gagal, throw exception
                Log::critical("🚨 ALL email providers FAILED for: {$this->to}");
                throw $e; // Lempar exception agar job bisa di-retry atau failed
            }
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        Log::critical("💀 SendOtpEmailJob FAILED after all retries for: {$this->to}");
        Log::critical("Error: " . $exception->getMessage());
        
        // Bisa tambahkan notifikasi ke admin disini
        // Atau simpan ke database untuk monitoring
    }
}
