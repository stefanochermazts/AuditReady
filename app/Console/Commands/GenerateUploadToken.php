<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\JwtTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Generate Upload Token Command
 * 
 * This command generates a JWT token for external upload API.
 * Usage: php artisan upload:token {user_email} {tenant_id} [--expires=3600]
 */
class GenerateUploadToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:token 
                            {user_email : Email of the user with External Uploader role}
                            {tenant_id : Tenant UUID}
                            {--expires=3600 : Token expiration in seconds (default: 1 hour)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate JWT token for external upload API';

    /**
     * Execute the console command.
     */
    public function handle(JwtTokenService $jwtService): int
    {
        $userEmail = $this->argument('user_email');
        $tenantId = $this->argument('tenant_id');
        $expiration = (int) $this->option('expires');

        // Validate tenant ID format (should be UUID)
        $validator = Validator::make(['tenant_id' => $tenantId], [
            'tenant_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            $this->error('Invalid tenant ID format. Must be a valid UUID.');
            return Command::FAILURE;
        }

        // Find user
        $user = User::where('email', $userEmail)->first();

        if (!$user) {
            $this->error("User with email '{$userEmail}' not found.");
            return Command::FAILURE;
        }

        // Verify user has External Uploader role
        if (!$user->hasRole('External Uploader')) {
            $this->error("User '{$userEmail}' does not have External Uploader role.");
            $this->info('Assign the role using: php artisan user:assign-role {user_email} "External Uploader"');
            return Command::FAILURE;
        }

        try {
            // Generate token
            $token = $jwtService->generateToken($user, $tenantId, $expiration);

            $this->info('Token generated successfully!');
            $this->newLine();
            $this->line('Token:');
            $this->line($token);
            $this->newLine();
            $this->line('Usage:');
            $this->line('  curl -X POST ' . config('app.url') . '/api/external/evidences \\');
            $this->line('    -H "Authorization: Bearer ' . $token . '" \\');
            $this->line('    -H "X-Tenant-ID: ' . $tenantId . '" \\');
            $this->line('    -F "file=@/path/to/file.pdf" \\');
            $this->line('    -F "audit_id=1"');
            $this->newLine();
            $this->warn('⚠️  Store this token securely. It expires in ' . ($expiration / 60) . ' minutes.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate token: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
