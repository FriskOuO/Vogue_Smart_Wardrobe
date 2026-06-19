<?php

namespace Tests\Feature;

use App\Models\AiEmbedding;
use App\Models\Clothing;
use App\Models\OutfitLog;
use App\Models\StylistHistory;
use App\Models\User;
use App\Models\WearLog;
use App\Services\DemoDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seed_creates_reusable_demo_account_and_records(): void
    {
        $this->artisan('vogueai:demo-data seed')
            ->expectsOutput('VogueAI demo data seed')
            ->assertExitCode(0);

        $this->artisan('vogueai:demo-data seed')
            ->assertExitCode(0);

        $user = User::where('email', DemoDataService::DEMO_EMAIL)->firstOrFail();

        $this->assertSame('VogueAI Demo User', $user->name);
        $this->assertSame(3, Clothing::where('user_id', $user->id)->count());
        $this->assertSame(3, AiEmbedding::where('user_id', $user->id)->count());
        $this->assertSame(3, WearLog::where('user_id', $user->id)->count());
        $this->assertSame(1, StylistHistory::where('user_id', $user->id)->count());
        $this->assertSame(1, OutfitLog::where('user_id', $user->id)->count());
    }

    public function test_demo_data_cleanup_removes_only_demo_account_scope(): void
    {
        app(DemoDataService::class)->seed();
        $otherUser = User::factory()->create([
            'email' => 'not-demo@example.test',
            'role' => 'user',
        ]);
        $otherClothing = Clothing::create([
            'user_id' => $otherUser->id,
            'name' => 'Non Demo Jacket',
            'image_path' => 'clothes/non-demo-jacket.jpg',
            'image_url' => '/storage/clothes/non-demo-jacket.jpg',
            'category' => 'outerwear',
            'color' => 'black',
            'ai_status' => 'degraded',
            'ai_mode' => 'mock',
        ]);

        $this->artisan('vogueai:demo-data cleanup')
            ->expectsOutput('VogueAI demo data cleanup')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', [
            'email' => DemoDataService::DEMO_EMAIL,
        ]);
        $this->assertSame(1, Clothing::count());
        $this->assertSame(0, AiEmbedding::count());
        $this->assertSame(0, WearLog::count());
        $this->assertSame(0, StylistHistory::count());
        $this->assertSame(0, OutfitLog::count());
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'email' => 'not-demo@example.test',
        ]);
        $this->assertDatabaseHas('clothes', [
            'id' => $otherClothing->id,
            'user_id' => $otherUser->id,
            'name' => 'Non Demo Jacket',
        ]);
    }
}
