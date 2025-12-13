<?php

namespace Kaninstein\LaravelPagarme\Console;

use Illuminate\Console\Command;
use Kaninstein\LaravelPagarme\Facades\Pagarme;

/**
 * Setup Webhooks Command
 *
 * Configura webhooks do Pagar.me automaticamente
 *
 * Usage:
 * php artisan pagarme:setup-webhooks
 * php artisan pagarme:setup-webhooks --url=https://mysite.com/webhooks/pagarme
 * php artisan pagarme:setup-webhooks --events=order.paid,charge.paid
 */
class SetupWebhooksCommand extends Command
{
    /**
     * Command signature
     *
     * @var string
     */
    protected $signature = 'pagarme:setup-webhooks
                            {--url= : URL do webhook (opcional, usa PAGARME_WEBHOOK_URL do .env)}
                            {--events=* : Eventos específicos para configurar}
                            {--list : Listar webhooks existentes}
                            {--clean : Remover todos os webhooks antes de criar novos}';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Configura webhooks do Pagar.me automaticamente';

    /**
     * Eventos recomendados para e-commerce
     *
     * @var array
     */
    protected array $recommendedEvents = [
        // Pedidos
        'order.paid' => 'Pedido pago com sucesso',
        'order.payment_failed' => 'Falha no pagamento do pedido',
        'order.canceled' => 'Pedido cancelado',
        'order.closed' => 'Pedido fechado',

        // Cobranças
        'charge.paid' => 'Cobrança paga',
        'charge.payment_failed' => 'Falha no pagamento da cobrança',
        'charge.refunded' => 'Cobrança estornada',
        'charge.chargedback' => 'Chargeback recebido',
        'charge.pending' => 'Cobrança pendente',
        'charge.underpaid' => 'Cobrança paga a menor',
        'charge.overpaid' => 'Cobrança paga a maior',

        // Antifraude
        'charge.antifraud_approved' => 'Aprovado pelo antifraude',
        'charge.antifraud_reproved' => 'Reprovado pelo antifraude',
        'charge.antifraud_manual' => 'Análise manual no antifraude',
    ];

    /**
     * Todos os eventos disponíveis
     *
     * @var array
     */
    protected array $allEvents = [
        'customer.created', 'customer.updated',
        'card.created', 'card.updated', 'card.deleted', 'card.expired',
        'address.created', 'address.updated', 'address.deleted',
        'order.paid', 'order.payment_failed', 'order.created', 'order.canceled',
        'order.closed', 'order.updated',
        'order_item.created', 'order_item.updated', 'order_item.deleted',
        'charge.created', 'charge.updated', 'charge.paid', 'charge.payment_failed',
        'charge.refunded', 'charge.pending', 'charge.processing',
        'charge.underpaid', 'charge.overpaid', 'charge.partial_canceled',
        'charge.chargedback',
        'charge.antifraud_approved', 'charge.antifraud_reproved',
        'charge.antifraud_manual', 'charge.antifraud_pending',
        'subscription.created', 'subscription.canceled',
        'invoice.created', 'invoice.updated', 'invoice.paid',
        'invoice.payment_failed', 'invoice.canceled',
    ];

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $this->info('🔔 Configuração de Webhooks Pagar.me');
        $this->newLine();

        // Listar webhooks existentes
        if ($this->option('list')) {
            return $this->listWebhooks();
        }

        // Verificar URL do webhook
        $webhookUrl = $this->option('url') ?? config('pagarme.webhook.url') ?? env('PAGARME_WEBHOOK_URL');

        if (empty($webhookUrl)) {
            $this->error('❌ URL do webhook não configurada!');
            $this->info('Configure PAGARME_WEBHOOK_URL no .env ou use --url=https://...');
            return 1;
        }

        $this->line("📍 URL do webhook: <fg=cyan>$webhookUrl</>");
        $this->newLine();

        // Limpar webhooks existentes se solicitado
        if ($this->option('clean')) {
            if ($this->confirm('⚠️  Remover TODOS os webhooks existentes?', false)) {
                $this->cleanWebhooks();
            }
        }

        // Selecionar eventos
        $events = $this->selectEvents();

        if (empty($events)) {
            $this->warn('⚠️  Nenhum evento selecionado.');
            return 1;
        }

        // Criar webhooks
        return $this->createWebhooks($webhookUrl, $events);
    }

    /**
     * Listar webhooks existentes
     */
    protected function listWebhooks(): int
    {
        try {
            $this->info('📋 Webhooks existentes:');
            $this->newLine();

            $webhooks = Pagarme::webhooks()->list();

            if (empty($webhooks['data'])) {
                $this->warn('Nenhum webhook configurado.');
                return 0;
            }

            $rows = [];
            foreach ($webhooks['data'] as $webhook) {
                $rows[] = [
                    substr($webhook['id'], 0, 20),
                    $webhook['event'] ?? 'N/A',
                    $webhook['status'] ?? 'N/A',
                    substr($webhook['url'], 0, 50),
                ];
            }

            $this->table(
                ['ID', 'Evento', 'Status', 'URL'],
                $rows
            );

            $this->info("\nTotal: " . count($webhooks['data']) . ' webhooks');

            return 0;
        } catch (\Exception $e) {
            $this->error('Erro ao listar webhooks: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Selecionar eventos para configurar
     */
    protected function selectEvents(): array
    {
        // Eventos específicos via opção
        if ($this->option('events')) {
            return $this->option('events');
        }

        // Menu interativo
        $choice = $this->choice(
            'Quais eventos deseja configurar?',
            [
                'recommended' => '✅ Eventos recomendados (' . count($this->recommendedEvents) . ' eventos)',
                'all' => '📦 Todos os eventos (' . count($this->allEvents) . ' eventos)',
                'custom' => '🎯 Selecionar manualmente',
            ],
            'recommended'
        );

        return match ($choice) {
            'recommended' => array_keys($this->recommendedEvents),
            'all' => $this->allEvents,
            'custom' => $this->selectCustomEvents(),
            default => array_keys($this->recommendedEvents),
        };
    }

    /**
     * Selecionar eventos customizados
     */
    protected function selectCustomEvents(): array
    {
        $this->info('Eventos disponíveis:');
        $this->newLine();

        $groups = [
            'Pedidos' => ['order.paid', 'order.payment_failed', 'order.canceled', 'order.closed'],
            'Cobranças' => ['charge.paid', 'charge.payment_failed', 'charge.refunded', 'charge.chargedback'],
            'Antifraude' => ['charge.antifraud_approved', 'charge.antifraud_reproved'],
            'Assinaturas' => ['subscription.created', 'subscription.canceled'],
        ];

        $selectedEvents = [];

        foreach ($groups as $group => $events) {
            if ($this->confirm("Incluir eventos de $group?", true)) {
                $selectedEvents = array_merge($selectedEvents, $events);
            }
        }

        return $selectedEvents;
    }

    /**
     * Criar webhooks
     */
    protected function createWebhooks(string $url, array $events): int
    {
        $this->info("🚀 Criando webhooks para " . count($events) . " eventos...");
        $this->newLine();

        $bar = $this->output->createProgressBar(count($events));
        $bar->start();

        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($events as $event) {
            try {
                Pagarme::webhooks()->create([
                    'url' => $url,
                    'events' => [$event],
                ]);

                $created++;
            } catch (\Exception $e) {
                $failed++;
                $errors[$event] = $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumo
        if ($created > 0) {
            $this->info("✅ $created webhooks criados com sucesso!");
        }

        if ($failed > 0) {
            $this->warn("⚠️  $failed webhooks falharam:");
            foreach ($errors as $event => $error) {
                $this->line("  - $event: $error");
            }
        }

        $this->newLine();
        $this->info('💡 Dica: Use php artisan pagarme:setup-webhooks --list para ver todos os webhooks');

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Remover todos os webhooks
     */
    protected function cleanWebhooks(): void
    {
        try {
            $this->info('🧹 Removendo webhooks existentes...');

            $webhooks = Pagarme::webhooks()->list();

            if (empty($webhooks['data'])) {
                $this->info('Nenhum webhook para remover.');
                return;
            }

            $bar = $this->output->createProgressBar(count($webhooks['data']));
            $bar->start();

            foreach ($webhooks['data'] as $webhook) {
                try {
                    Pagarme::webhooks()->delete($webhook['id']);
                } catch (\Exception $e) {
                    // Ignorar erros ao deletar
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('✅ Webhooks removidos!');
            $this->newLine();
        } catch (\Exception $e) {
            $this->error('Erro ao limpar webhooks: ' . $e->getMessage());
        }
    }
}
