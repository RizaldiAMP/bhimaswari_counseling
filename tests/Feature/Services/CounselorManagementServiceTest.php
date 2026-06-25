<?php

namespace Tests\Feature\Services;

use App\Models\CounselorProfile;
use App\Models\User;
use App\Services\CounselorManagementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CounselorManagementServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_counselor_and_profile_in_single_flow(): void
    {
        $service = app(CounselorManagementService::class);

        $user = $service->createCounselor([
            'name' => 'Dr. Test Counselor',
            'email' => 'counselor@example.com',
            'whatsapp' => '+6281234567890',
            'password' => 'secret-password',
            'practitioner_type' => 'counselor',
            'full_title' => 'Dr. Test Counselor, M.Psi',
            'sipp_number' => 'SIPP-123',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'counselor@example.com',
            'role' => 'counselor',
        ]);

        $this->assertTrue(Hash::check('secret-password', $user->password));

        $profile = CounselorProfile::where('user_id', $user->id)->first();

        $this->assertNotNull($profile);
        $this->assertSame('counselor', $profile->practitioner_type);
        $this->assertSame('Dr. Test Counselor, M.Psi', $profile->full_title);
    }
}
