<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\AppException;
use App\Services\ChatService;
use App\Services\TrafficLightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function chat_restituisce_risposta_ai(): void
    {
        $this->mock(TrafficLightService::class, function ($mock): void {
            $mock->shouldReceive('snapshot')->once()->andReturn([
                'status' => 'green', 'pending' => 0, 'errors' => 0, 'last_updated' => now()->toDateTimeString(),
            ]);
        });
        $this->mock(ChatService::class, function ($mock): void {
            $mock->shouldReceive('ask')->once()->andReturn('Le fatture sono tutte in regola.');
        });

        $this->postJson('/chat', ['question' => 'Come va la coda?'])
            ->assertStatus(200)
            ->assertJsonStructure(['reply'])
            ->assertJson(['reply' => 'Le fatture sono tutte in regola.']);
    }

    #[Test]
    public function chat_restituisce_422_se_domanda_vuota(): void
    {
        $this->postJson('/chat', ['question' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['question']);
    }

    #[Test]
    public function chat_restituisce_422_se_domanda_troppo_lunga(): void
    {
        $this->postJson('/chat', ['question' => str_repeat('a', 501)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['question']);
    }

    #[Test]
    public function chat_restituisce_503_se_servizio_ai_fallisce(): void
    {
        $this->mock(TrafficLightService::class, function ($mock): void {
            $mock->shouldReceive('snapshot')->once()->andReturn([
                'status' => 'red', 'pending' => 60, 'errors' => 12, 'last_updated' => now()->toDateTimeString(),
            ]);
        });
        $this->mock(ChatService::class, function ($mock): void {
            $mock->shouldReceive('ask')->once()->andThrow(new AppException('test: timeout'));
        });

        $this->postJson('/chat', ['question' => 'Cosa sta succedendo?'])
            ->assertStatus(503)
            ->assertJsonStructure(['error']);
    }

    #[Test]
    public function chat_service_ritorna_fallback_template_dopo_tre_429(): void
    {
        Http::fake(['*' => Http::response([], 429)]);

        $snapshot = ['status' => 'green', 'pending' => 2, 'errors' => 0];
        $result = app(ChatService::class)->ask($snapshot, 'Come va?');

        $this->assertStringContainsString('Tutto funziona correttamente', $result);
    }

    #[Test]
    public function chat_service_riprova_dopo_429_e_restituisce_risposta_ai(): void
    {
        Config::set('ai.provider', 'gemini');

        Http::fake([
            '*' => Http::sequence()
                ->push([], 429)
                ->push(['candidates' => [['content' => ['parts' => [['text' => 'Risposta AI']]]]]], 200),
        ]);

        $snapshot = ['status' => 'green', 'pending' => 2, 'errors' => 0];
        $result = app(ChatService::class)->ask($snapshot, 'Come va?');

        $this->assertSame('Risposta AI', $result);
    }

    #[Test]
    public function chat_controller_ritorna_200_con_fallback_dopo_tre_429(): void
    {
        Http::fake(['*' => Http::response([], 429)]);

        $this->mock(TrafficLightService::class, function ($mock): void {
            $mock->shouldReceive('snapshot')->once()->andReturn([
                'status' => 'yellow', 'pending' => 15, 'errors' => 4, 'last_updated' => now()->toDateTimeString(),
            ]);
        });

        $response = $this->postJson('/chat', ['question' => 'Cosa sta succedendo?']);

        $response->assertStatus(200)
            ->assertJsonStructure(['reply']);

        $this->assertStringContainsString('Attenzione', $response->json('reply'));
    }

    // ── OpenRouter ───────────────────────────────────────────────────

    #[Test]
    public function chat_service_openrouter_restituisce_risposta_ai(): void
    {
        Config::set('ai.provider', 'openrouter');

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => 'Risposta da OpenRouter']]],
            ], 200),
        ]);

        $snapshot = ['status' => 'green', 'pending' => 1, 'errors' => 0];
        $result = app(ChatService::class)->ask($snapshot, 'Come va?');

        $this->assertSame('Risposta da OpenRouter', $result);
    }

    #[Test]
    public function chat_service_openrouter_fallback_su_errore_http(): void
    {
        Config::set('ai.provider', 'openrouter');

        Http::fake(['openrouter.ai/*' => Http::response([], 500)]);

        $snapshot = ['status' => 'red', 'pending' => 55, 'errors' => 12];
        $result = app(ChatService::class)->ask($snapshot, 'Cosa succede?');

        // Non lancia eccezione — restituisce template M0 rosso
        $this->assertStringContainsString('PROBLEMA CRITICO', $result);
    }

    #[Test]
    public function chat_service_openrouter_riprova_su_429_e_poi_risponde(): void
    {
        Config::set('ai.provider', 'openrouter');

        Http::fake([
            'openrouter.ai/*' => Http::sequence()
                ->push([], 429)
                ->push(['choices' => [['message' => ['content' => 'Risposta dopo retry']]]], 200),
        ]);

        $snapshot = ['status' => 'green', 'pending' => 2, 'errors' => 0];
        $result = app(ChatService::class)->ask($snapshot, 'Come va?');

        $this->assertSame('Risposta dopo retry', $result);
    }
}
