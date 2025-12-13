<?php

/**
 * Exemplo de implementação de webhook handler
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kaninstein\LaravelPagarme\Facades\Pagarme;
use Illuminate\Support\Facades\Log;

class PagarmeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Obter dados do webhook
        $event = $request->input('type');
        $data = $request->input('data');

        Log::info('Pagarme Webhook received', [
            'event' => $event,
            'data' => $data
        ]);

        // Processar diferentes tipos de eventos
        match ($event) {
            'order.paid' => $this->handleOrderPaid($data),
            'order.canceled' => $this->handleOrderCanceled($data),
            'charge.paid' => $this->handleChargePaid($data),
            'charge.refunded' => $this->handleChargeRefunded($data),
            'charge.chargeback' => $this->handleChargeback($data),
            default => Log::warning('Unhandled webhook event', ['event' => $event])
        };

        return response()->json(['status' => 'ok']);
    }

    protected function handleOrderPaid(array $data)
    {
        $orderId = $data['id'];

        // Buscar informações completas do pedido
        $order = Pagarme::orders()->get($orderId);

        // Processar pagamento confirmado
        Log::info('Order paid', ['order_id' => $orderId]);

        // Aqui você pode:
        // - Atualizar status do pedido no seu banco
        // - Enviar email de confirmação
        // - Acionar processamento de envio
        // - etc.
    }

    protected function handleOrderCanceled(array $data)
    {
        $orderId = $data['id'];

        Log::info('Order canceled', ['order_id' => $orderId]);

        // Processar cancelamento
    }

    protected function handleChargePaid(array $data)
    {
        $chargeId = $data['id'];

        Log::info('Charge paid', ['charge_id' => $chargeId]);
    }

    protected function handleChargeRefunded(array $data)
    {
        $chargeId = $data['id'];

        Log::info('Charge refunded', ['charge_id' => $chargeId]);
    }

    protected function handleChargeback(array $data)
    {
        $chargeId = $data['id'];

        Log::alert('Chargeback received', ['charge_id' => $chargeId]);

        // Processar chargeback
    }
}
