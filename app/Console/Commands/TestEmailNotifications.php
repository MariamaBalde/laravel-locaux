<?php

namespace App\Console\Commands;

use App\Events\OrderPlaced;
use App\Events\VendorCreated;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendeur;
use Illuminate\Console\Command;

class TestEmailNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {type=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email notifications (client, vendor, admin)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        $this->info('🧪 Testing email notifications...');

        if ($type === 'all' || $type === 'order') {
            $this->testOrderConfirmation();
        }

        if ($type === 'all' || $type === 'vendor') {
            $this->testVendorNotification();
        }

        if ($type === 'all' || $type === 'verification') {
            $this->testVendorVerification();
        }

        $this->info('✅ Notifications sent to queue!');
        $this->info('🚀 Run: php artisan queue:work');
    }

    /**
     * Test order confirmation email
     */
    private function testOrderConfirmation(): void
    {
        $this->info('');
        $this->info('📭 Testing: Order Confirmation (Client)');

        $order = Order::with('items.product', 'user')->first();

        if (! $order) {
            $this->error('❌ No orders found. Create one first.');

            return;
        }

        $this->info("   Order: {$order->order_number}");
        $this->info("   Customer: {$order->user->name} ({$order->user->email})");

        OrderPlaced::dispatch($order);

        $this->info('   ✅ Queued!');
    }

    /**
     * Test vendor new order email
     */
    private function testVendorNotification(): void
    {
        $this->info('');
        $this->info('🏪 Testing: Vendor New Order');

        $order = Order::with('items.product.vendeur.user')->first();

        if (! $order) {
            $this->error('❌ No orders found. Create one first.');

            return;
        }

        $vendorIds = $order->items->pluck('product.vendeur_id')->unique();

        if ($vendorIds->isEmpty()) {
            $this->error('❌ No vendors found in order items.');

            return;
        }

        foreach ($vendorIds as $vendeurId) {
            $vendeur = Vendeur::with('user')->find($vendeurId);
            if ($vendeur) {
                $this->info("   Vendor: {$vendeur->user->name} ({$vendeur->user->email})");
            }
        }

        OrderPlaced::dispatch($order);

        $this->info('   ✅ Queued!');
    }

    /**
     * Test vendor verification email
     */
    private function testVendorVerification(): void
    {
        $this->info('');
        $this->info('👤 Testing: Vendor Verification (Admin)');

        $vendors = Vendeur::with('user')->where('verified', false)->limit(1)->get();

        if ($vendors->isEmpty()) {
            $this->warn('   ⚠️  No unverified vendors found.');

            return;
        }

        $vendor = $vendors->first();
        $admins = User::where('role', 'admin')->limit(1)->get();

        if ($admins->isEmpty()) {
            $this->warn('   ⚠️  No admins found.');

            return;
        }

        $this->info("   Vendor: {$vendor->user->name}");
        $this->info("   Admin: {$admins->first()->name}");

        VendorCreated::dispatch($vendor);

        $this->info('   ✅ Queued!');
    }
}
