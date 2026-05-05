<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['category' => 'emissao_nf', 'audience' => 'todos', 'question' => 'Como emitir a NF-e e o CT-e pelo app?', 'answer' => 'Acesse o menu "Minhas Cargas", selecione a carga em andamento e clique no botão "Gerar Documentos Fiscais". O PDF será gerado automaticamente após a validação da SEFAZ.'],
            ['category' => 'gr', 'audience' => 'motorista', 'question' => 'Quais são as exigências de Gerenciamento de Risco (GR)?', 'answer' => 'O veículo deve possuir rastreador homologado ativo e o motorista deve preencher o checklist de viagem antes de iniciar a rota. Paradas não programadas geram alerta na central.'],
            ['category' => 'recebimento', 'audience' => 'motorista', 'question' => 'Qual o prazo para o recebimento do frete?', 'answer' => 'O saldo é liberado na sua carteira virtual 24 horas após a confirmação de entrega via canhoto digital assinado.'],
            ['category' => 'voucher', 'audience' => 'todos', 'question' => 'Como utilizar o Voucher de abastecimento?', 'answer' => 'Apresente o QR Code do Voucher diretamente na rede de postos conveniados. O valor será abatido do saldo final do seu frete.'],
            ['category' => 'loja', 'audience' => 'todos', 'question' => 'Como comprar EPIs na Loja 123fretei?', 'answer' => 'Acesse a aba Loja no menu principal. Você pode usar o seu saldo de recebíveis para adquirir botas, coletes e acessórios com desconto corporativo.'],
            ['category' => 'parceiros', 'audience' => 'embarcador', 'question' => 'Como integrar meu ERP ao sistema de Parceiros?', 'answer' => 'Solicite a chave de API (Token) no painel administrativo e consulte a documentação técnica no portal do desenvolvedor para habilitar a transmissão de lotes.'],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::create(array_merge($faq, ['sort_order' => $index]));
        }
    }
}