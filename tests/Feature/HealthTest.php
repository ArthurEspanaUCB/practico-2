<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_ok_or_degraded(): void
    {
        $res = $this->getJson('/api/health');

        // En CI controlaremos DB para que sea OK.
        // En local, si no hay DB responderá 503, por lo que permitimos ambos estados para este test genérico.
        // Sin embargo, queremos asertar estructura.

        if ($res->status() === 503) {
            $res->assertStatus(503);
            $res->assertJsonPath('status', 'degraded');
        } else {
            $res->assertStatus(200);
            $res->assertJsonPath('status', 'ok');
        }

        $res->assertJsonStructure([
            'status',
            'app' => ['env', 'debug'],
            'db' => ['ok', 'latency_ms', 'driver'],
        ]);
    }
}
