<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ExplainerService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test unitari per ExplainerService::explain().
 *
 * explain() restituisce array{stato, diagnosi, azione}.
 *
 * Copertura template statici (EXPLAINER_USE_LLM=false, default):
 *   - green  (pending<10, errori=0)  → stato "Tutto OK"
 *   - yellow (fallback semaforo)     → stato "Attenzione"
 *   - red    (fallback semaforo)     → stato "Critico", azione con supporto tecnico
 *
 * Copertura regole numeriche primarie:
 *   - pending>500 && errori>10       → stato "Critico" (regola 1)
 *   - pending>50  && errori>3        → stato "Attenzione" (regola 2)
 *
 * Copertura LLM (EXPLAINER_USE_LLM=true, Http::fake):
 *   - risposta Gemini valida      → stato contiene testo LLM
 *   - risposta HTTP non-2xx       → fallback template
 *   - risposta Gemini testo vuoto → fallback template
 *
 * explain() opera su un array snapshot — nessun DB necessario.
 */
class ExplainerServiceTest extends TestCase
{
    private ExplainerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExplainerService;
    }

    // ── Template statici (EXPLAINER_USE_LLM=false, default) ───────

    #[Test]
    public function explain_green_returns_tutto_ok_message(): void
    {
        $result = $this->service->explain(['status' => 'green', 'pending' => 3, 'errors' => 0]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stato', $result);
        $this->assertArrayHasKey('diagnosi', $result);
        $this->assertArrayHasKey('azione', $result);
        $this->assertStringContainsString('Tutto OK', $result['stato']);
        $this->assertStringContainsString('Nessuna azione', $result['azione']);
    }

    #[Test]
    public function explain_yellow_fallback_returns_attenzione_message(): void
    {
        // pending=15, errors=0: nessuna regola numerica → fallback semaforo yellow
        $result = $this->service->explain(['status' => 'yellow', 'pending' => 15, 'errors' => 0]);

        $this->assertIsArray($result);
        $this->assertStringContainsString('Attenzione', $result['stato']);
        $this->assertNotEmpty($result['diagnosi']);
        $this->assertNotEmpty($result['azione']);
    }

    #[Test]
    public function explain_red_fallback_returns_critico_message(): void
    {
        // pending=0, errors=12: nessuna regola numerica → fallback semaforo red
        $result = $this->service->explain(['status' => 'red', 'pending' => 0, 'errors' => 12]);

        $this->assertIsArray($result);
        $this->assertStringContainsString('Critico', $result['stato']);
        $this->assertStringContainsString('supporto tecnico', $result['azione']);
    }

    // ── Regole numeriche primarie ──────────────────────────────────

    #[Test]
    public function explain_critico_rule_fires_when_both_thresholds_exceeded(): void
    {
        // pending>500 && errors>10 → regola 1 "critico"
        $result = $this->service->explain(['status' => 'red', 'pending' => 600, 'errors' => 15]);

        $this->assertStringContainsString('Critico', $result['stato']);
        $this->assertStringContainsString('600', $result['stato']);
        $this->assertStringContainsString('worker', $result['azione']);
    }

    #[Test]
    public function explain_attenzione_rule_fires_when_both_thresholds_exceeded(): void
    {
        // pending>50 && errors>3 → regola 2 "attenzione"
        $result = $this->service->explain(['status' => 'yellow', 'pending' => 60, 'errors' => 5]);

        $this->assertStringContainsString('Attenzione', $result['stato']);
        $this->assertStringContainsString('60', $result['stato']);
        $this->assertStringContainsString('supporto tecnico', $result['azione']);
    }

    // ── LLM abilitato (EXPLAINER_USE_LLM=true, Http::fake) ────────

    #[Test]
    public function explain_with_llm_returns_llm_stato_when_api_succeeds(): void
    {
        config(['invoice.explainer_use_llm' => true]);
        config([
            'ai.gemini_api_key' => 'test-key',
            'ai.model' => 'gemini-1.5-flash',
            'ai.gemini_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
            'ai.max_tokens' => 512,
            'ai.temperature' => 0.3,
        ]);

        Http::fake(['*' => Http::response([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'La situazione è sotto controllo. Nessuna azione richiesta.']]]],
            ],
        ], 200)]);

        $result = $this->service->explain(['status' => 'green', 'pending' => 0, 'errors' => 0]);

        $this->assertIsArray($result);
        $this->assertStringContainsString('sotto controllo', $result['stato']);
        $this->assertNotEmpty($result['diagnosi']);
        $this->assertNotEmpty($result['azione']);
        Http::assertSentCount(1);
    }

    #[Test]
    public function explain_with_llm_falls_back_to_template_on_http_error(): void
    {
        config(['invoice.explainer_use_llm' => true]);
        config([
            'ai.gemini_api_key' => 'invalid-key',
            'ai.model' => 'gemini-1.5-flash',
            'ai.gemini_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
            'ai.max_tokens' => 512,
            'ai.temperature' => 0.3,
        ]);

        Http::fake(['*' => Http::response(['error' => 'API key not valid'], 403)]);

        $result = $this->service->explain(['status' => 'green', 'pending' => 3, 'errors' => 0]);

        // Fallback al template statico
        $this->assertStringContainsString('Tutto OK', $result['stato']);
        Http::assertSentCount(1);
    }

    #[Test]
    public function explain_with_llm_falls_back_to_template_on_malformed_response(): void
    {
        config(['invoice.explainer_use_llm' => true]);
        config([
            'ai.gemini_api_key' => 'test-key',
            'ai.model' => 'gemini-1.5-flash',
            'ai.gemini_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
            'ai.max_tokens' => 512,
            'ai.temperature' => 0.3,
        ]);

        // Risposta 200 ma testo vuoto → explainWithLlm() restituisce null → fallback
        Http::fake(['*' => Http::response([
            'candidates' => [
                ['content' => ['parts' => [['text' => '']]]],
            ],
        ], 200)]);

        $result = $this->service->explain(['status' => 'red', 'pending' => 0, 'errors' => 12]);

        // Fallback al template statico (red fallback)
        $this->assertStringContainsString('Critico', $result['stato']);
        Http::assertSentCount(1);
    }
}
