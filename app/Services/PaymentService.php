<?php

namespace App\Services;

use App\Models\User;
use App\Models\AdminEpisodeLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Process monthly payments for all admins (run on 25th)
     */
    public function processMonthlyPayments(int $year = null, int $month = null): array
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        
        $results = [
            'processed' => 0,
            'total_paid' => 0,
            'total_rollover' => 0,
            'details' => [],
        ];
        
        // Get all admins with pending payments
        $admins = User::admins()
            ->whereHas('adminEpisodeLogs', function ($query) {
                $query->where('status', AdminEpisodeLog::STATUS_APPROVED);
            })
            ->get();
        
        foreach ($admins as $admin) {
            $result = $this->processAdminPayment($admin, $year, $month);
            
            $results['processed']++;
            $results['total_paid'] += $result['paid'];
            $results['total_rollover'] += $result['rollover'];
            $results['details'][] = $result;
        }
        
        return $results;
    }

    /**
     * Process payments for selected admins only
     */
    public function processPaymentsForAdmins(array $adminIds, ?string $paymentProof = null): array
    {
        $year = now()->year;
        $month = now()->month;
        
        $results = [
            'processed' => 0,
            'total_paid' => 0,
            'total_rollover' => 0,
            'details' => [],
            'payment_proof' => $paymentProof,
        ];
        
        // Get selected admins
        $admins = User::whereIn('id', $adminIds)->get();
        
        foreach ($admins as $admin) {
            $result = $this->processAdminPayment($admin, $year, $month, $paymentProof);
            
            $results['processed']++;
            $results['total_paid'] += $result['paid'];
            $results['total_rollover'] += $result['rollover'];
            $results['details'][] = $result;
        }
        
        return $results;
    }
    
    /**
     * Process payment for a single admin
     */
    public function processAdminPayment(User $admin, int $year, int $month, ?string $paymentProof = null): array
    {
        $calc = $admin->calculateMonthlyPayment($year, $month);
        
        $result = [
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'role' => $admin->role,
            'level' => $admin->admin_level,
            'approved_this_month' => $calc['approved_this_month'] ?? 0,
            'rollover_from_previous' => $calc['rollover_from_previous'],
            'total_available' => $calc['total_available'],
            'limit' => $calc['limit'],
            'paid' => 0,
            'rollover' => 0,
            'status' => 'processed',
        ];
        
        try {
            DB::beginTransaction();
            
            // Mark APPROVED logs as paid up to the payable amount
            $amountToPay = $calc['payable'];
            $paidSoFar = 0;
            
            // First, apply from rollover balance
            if ($calc['rollover_from_previous'] > 0) {
                $fromRollover = min($calc['rollover_from_previous'], $amountToPay);
                $paidSoFar += $fromRollover;
            }
            
            // Then mark APPROVED logs as paid (not pending - pending must be approved first)
            $approvedLogs = $admin->adminEpisodeLogs()
                ->where('status', AdminEpisodeLog::STATUS_APPROVED)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->orderBy('created_at', 'asc')
                ->get();
            
            $remainingToPay = $amountToPay - $paidSoFar;
            $logsToMarkPaid = [];
            
            foreach ($approvedLogs as $log) {
                if ($remainingToPay >= $log->amount) {
                    $logsToMarkPaid[] = $log->id;
                    $remainingToPay -= $log->amount;
                    $paidSoFar += $log->amount;
                } else {
                    // Partial payment scenario - we'll leave this as approved for now
                    break;
                }
            }
            
            // Mark selected logs as paid
            if (!empty($logsToMarkPaid)) {
                AdminEpisodeLog::whereIn('id', $logsToMarkPaid)
                    ->update([
                        'status' => AdminEpisodeLog::STATUS_PAID,
                        'paid_at' => now(),
                        'payment_month' => Carbon::create($year, $month, 25),
                    ]);
            }
            
            // Update user's rollover balance
            $admin->update([
                'rollover_balance' => $calc['rollover_to_next'],
            ]);
            
            DB::commit();
            
            $result['paid'] = $calc['payable'];
            $result['rollover'] = $calc['rollover_to_next'];
            
            Log::info("Payment processed for {$admin->name}", $result);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = 'error';
            $result['error'] = $e->getMessage();
            
            Log::error("Payment processing failed for {$admin->name}: " . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Get payment summary for an admin
     */
    public function getAdminPaymentSummary(User $admin): array
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;
        
        return [
            'current_month' => $admin->calculateMonthlyPayment($currentYear, $currentMonth),
            'total_pending' => $admin->getTotalPendingEarnings(),
            'total_paid' => $admin->getTotalPaidEarnings(),
            'rollover_balance' => $admin->rollover_balance ?? 0,
            'monthly_limit' => $admin->getMonthlyLimit(),
            'admin_level' => $admin->getAdminLevelLabel(),
            'days_until_payday' => $admin->getDaysUntilPayday(),
            'evaluation_completed' => $admin->hasCompletedEvaluationPeriod(),
        ];
    }
    
    /**
     * Simulate payment calculation without actually processing
     */
    public function simulatePayment(User $admin, int $year = null, int $month = null): array
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        
        return $admin->calculateMonthlyPayment($year, $month);
    }
    
    /**
     * Get all admins eligible for payment this month (only approved logs)
     */
    public function getEligibleAdminsForPayment(): \Illuminate\Database\Eloquent\Collection
    {
        return User::admins()
            ->whereHas('adminEpisodeLogs', function ($query) {
                $query->where('status', AdminEpisodeLog::STATUS_APPROVED);
            })
            ->orWhere('rollover_balance', '>', 0)
            ->with(['adminEpisodeLogs' => function ($query) {
                $query->where('status', AdminEpisodeLog::STATUS_APPROVED);
            }])
            ->get();
    }
    
    /**
     * Manually adjust rollover balance
     */
    public function adjustRollover(User $admin, int $amount, string $reason = null): bool
    {
        $oldBalance = $admin->rollover_balance ?? 0;
        $newBalance = max(0, $oldBalance + $amount);
        
        $admin->update([
            'rollover_balance' => $newBalance,
            'admin_notes' => $admin->admin_notes . "\n[" . now()->format('Y-m-d H:i') . "] Rollover adjusted: " . 
                number_format($amount, 0, ',', '.') . " (Reason: " . ($reason ?? 'Manual adjustment') . ")",
        ]);
        
        Log::info("Rollover adjusted for {$admin->name}: {$oldBalance} -> {$newBalance}");
        
        return true;
    }
}
