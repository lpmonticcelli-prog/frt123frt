<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Embarcador;
use App\Models\Motorista;
use App\Models\Carga;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class HomologacaoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando geração de dados em massa com cenários realistas...');
        
        $faker = Faker::create('pt_BR');
        $senhaPadrao = Hash::make('password');

        DB::transaction(function () use ($faker, $senhaPadrao) {
            
            // 1. ESTRUTURA DE ROLES
            $roles = [
                'admin' => Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrador Root']),
                'manager' => Role::firstOrCreate(['slug' => 'manager'], ['name' => 'Gerente Operacional']),
                'compliance' => Role::firstOrCreate(['slug' => 'compliance'], ['name' => 'Compliance e Risco']),
                'suporte_n1' => Role::firstOrCreate(['slug' => 'suporte_n1'], ['name' => 'Suporte N1']),
                'motorista' => Role::firstOrCreate(['slug' => 'motorista'], ['name' => 'Motorista']),
                'embarcador' => Role::firstOrCreate(['slug' => 'embarcador'], ['name' => 'Embarcador'])
            ];

            // 2. CONTAS DE STAFF FIXAS
            $staff = [];
            $staff['admin'] = User::create(['name' => 'Wesley Dev (Admin)', 'email' => 'dev@123fretei.com.br', 'phone' => '11999999999', 'password' => $senhaPadrao, 'role_id' => $roles['admin']->id, 'status' => 'active']);
            $staff['manager'] = User::create(['name' => 'Gerente Operações', 'email' => 'gerente@123fretei.com.br', 'phone' => '11900000001', 'password' => $senhaPadrao, 'role_id' => $roles['manager']->id, 'status' => 'active']);
            $staff['compliance'] = User::create(['name' => 'Auditoria Risco', 'email' => 'risco@123fretei.com.br', 'phone' => '11900000002', 'password' => $senhaPadrao, 'role_id' => $roles['compliance']->id, 'status' => 'active']);
            $staff['n1'] = User::create(['name' => 'Atendente N1', 'email' => 'n1@123fretei.com.br', 'phone' => '11900000003', 'password' => $senhaPadrao, 'role_id' => $roles['suporte_n1']->id, 'status' => 'active']);

            // Gerar mais 5 atendentes N1 dinâmicos para simular equipe
            $equipeN1 = [$staff['n1']];
            for ($i = 0; $i < 5; $i++) {
                $equipeN1[] = User::create(['name' => "Suporte " . $faker->firstName, 'email' => "suporte{$i}@123fretei.com.br", 'phone' => $faker->numerify('119########'), 'password' => $senhaPadrao, 'role_id' => $roles['suporte_n1']->id, 'status' => 'active']);
            }

            $this->command->info('Gerando 30 Embarcadores...');
            $embarcadores = [];
            for ($i = 0; $i < 30; $i++) {
                $user = User::create(['name' => $faker->company, 'email' => $faker->unique()->companyEmail, 'phone' => $faker->numerify('119########'), 'password' => $senhaPadrao, 'role_id' => $roles['embarcador']->id, 'status' => 'active']);
                $embarcadores[] = Embarcador::create(['user_id' => $user->id, 'cnpj' => $faker->numerify('##############'), 'razao_social' => $user->name]);
            }

            $this->command->info('Gerando 100 Motoristas...');
            $motoristas = [];
            for ($i = 0; $i < 100; $i++) {
                $user = User::create(['name' => $faker->name, 'email' => $faker->unique()->safeEmail, 'phone' => $faker->numerify('119########'), 'password' => $senhaPadrao, 'role_id' => $roles['motorista']->id, 'status' => 'active']);
                $motoristas[] = Motorista::create(['user_id' => $user->id, 'cpf' => $faker->numerify('###########'), 'cnh' => $faker->numerify('###########'), 'validade_cnh' => $faker->dateTimeBetween('now', '+3 years')->format('Y-m-d'), 'rntrc' => $faker->numerify('########')]);
            }

            $this->command->info('Gerando 300 Cargas (Ecossistema Operacional)...');
            $cargas = [];
            $statusCarga = ['publicada', 'aceita', 'em_transito', 'coletada', 'entregue', 'finalizada', 'em_disputa', 'cancelada'];
            $produtos = ['Eletrônicos', 'Alimentos Secos', 'Cimento', 'Peças Automotivas', 'Medicamentos', 'Sementes', 'Bebidas', 'Bobinas de Aço'];
            $carrocerias = ['Baú', 'Sider', 'Grade Baixa', 'Graneleiro', 'Refrigerado'];
            $veiculos = ['Toco', 'Truck', 'Carreta LS', 'Bitrem', 'Vanderléia', 'Fiorino'];

            for ($i = 0; $i < 300; $i++) {
                $status = $faker->randomElement($statusCarga);
                $embarcador = $faker->randomElement($embarcadores);
                $motorista = in_array($status, ['publicada', 'cancelada']) ? null : $faker->randomElement($motoristas)->id;

                $cargas[] = Carga::create([
                    'embarcador_id' => $embarcador->id,
                    'motorista_id' => $motorista,
                    'produto' => $faker->randomElement($produtos),
                    'especie' => $faker->randomElement(['Caixas', 'Paletes', 'Sacos', 'Granel']),
                    'peso_kg' => $faker->numberBetween(500, 30000),
                    'tipo_veiculo' => $faker->randomElement($veiculos),
                    'tipo_carroceria' => $faker->randomElement($carrocerias),
                    'cidade_origem' => $faker->city,
                    'uf_origem' => $faker->stateAbbr,
                    'cidade_destino' => $faker->city,
                    'uf_destino' => $faker->stateAbbr,
                    'valor_frete' => $faker->randomFloat(2, 500, 8000),
                    'data_coleta' => $faker->dateTimeBetween('-30 days', '+15 days')->format('Y-m-d'),
                    'status' => $status,
                    'created_at' => $faker->dateTimeBetween('-30 days', 'now')
                ]);
            }

            $this->command->info('Injetando Tickets Operacionais Realistas...');

            $cenarios = [
                'Disputa de Frete' => [
                    ['assunto' => 'Motorista não compareceu na coleta', 'msg' => 'O veículo estava agendado para as 08:00. Tento ligar para o motorista e cai direto na caixa postal. Preciso de substituição urgente.', 'prioridade' => 'urgente', 'autor' => 'embarcador'],
                    ['assunto' => 'Carga com excesso de peso', 'msg' => 'A nota fiscal informa 14 toneladas, mas a balança da portaria marcou 16.5. O meu eixo não suporta e serei multado na balança rodoviária.', 'prioridade' => 'urgente', 'autor' => 'motorista'],
                    ['assunto' => 'Avaria na mercadoria descarregada', 'msg' => 'O motorista acabou de finalizar o descarregamento. Duas paletes tombaram no trajeto e 40 caixas estão inutilizadas. Seguem fotos em anexo. Solicito bloqueio do pagamento.', 'prioridade' => 'urgente', 'autor' => 'embarcador'],
                    ['assunto' => 'Endereço de destino incorreto', 'msg' => 'O endereço que está no aplicativo é de um bairro residencial. O cliente final informou que o galpão de recebimento fica em outro município.', 'prioridade' => 'alta', 'autor' => 'motorista'],
                    ['assunto' => 'Cancelamento na porta da fábrica', 'msg' => 'Rodei 80km vazio para chegar na coleta e o gerente de pátio informou que a carga já foi embarcada em outro caminhão ontem à noite.', 'prioridade' => 'urgente', 'autor' => 'motorista'],
                ],
                'Financeiro' => [
                    ['assunto' => 'Divergência na Fatura de Fechamento', 'msg' => 'A fatura FAT-202604-0012 veio com valor cobrado a maior. O frete FRT-992 foi cancelado e está sendo cobrado no relatório.', 'prioridade' => 'alta', 'autor' => 'embarcador'],
                    ['assunto' => 'Repasse PIX não creditado', 'msg' => 'Finalizei a viagem ontem às 15h, o aplicativo informou que o pagamento seria imediato, mas não caiu na minha conta.', 'prioridade' => 'alta', 'autor' => 'motorista'],
                    ['assunto' => 'Emissão de NFe pendente', 'msg' => 'A contabilidade está cobrando a nota fiscal referente à taxa de agenciamento do mês passado. Onde faço o download?', 'prioridade' => 'normal', 'autor' => 'embarcador'],
                    ['assunto' => 'Desconto indevido na tarifa', 'msg' => 'O valor negociado na plataforma era R$ 3.500, mas o depósito caiu com R$ 200 a menos alegando quebra de mercadoria sem provas.', 'prioridade' => 'alta', 'autor' => 'motorista'],
                    ['assunto' => 'Alteração de dados bancários', 'msg' => 'Troquei de banco. Como atualizo a minha chave PIX para os próximos recebimentos?', 'prioridade' => 'baixa', 'autor' => 'motorista'],
                ],
                'Problema no App' => [
                    ['assunto' => 'Aplicativo fechando sozinho (Crash)', 'msg' => 'Toda vez que tento tirar a foto do canhoto assinado (POD), a tela fica preta e o app fecha.', 'prioridade' => 'alta', 'autor' => 'motorista'],
                    ['assunto' => 'Erro 500 ao publicar nova carga', 'msg' => 'Estou tentando cadastrar um lote de 15 fretes, mas ao clicar em salvar o painel retorna erro interno do servidor.', 'prioridade' => 'urgente', 'autor' => 'embarcador'],
                    ['assunto' => 'Localização GPS travada', 'msg' => 'A viagem consta como "em andamento", mas a minha localização no mapa parou de atualizar há 4 horas.', 'prioridade' => 'normal', 'autor' => 'motorista'],
                    ['assunto' => 'Não consigo dar o aceite no frete', 'msg' => 'Clico no botão verde para pegar a carga e aparece a mensagem "Token expirado", mesmo tendo feito login agora.', 'prioridade' => 'alta', 'autor' => 'motorista'],
                    ['assunto' => 'Mural de cargas não atualiza', 'msg' => 'A lista de fretes disponíveis está igual desde ontem. Não aparecem novas oportunidades.', 'prioridade' => 'baixa', 'autor' => 'motorista'],
                ],
                'Cadastro' => [
                    ['assunto' => 'Renovação de CNH rejeitada', 'msg' => 'Enviei a foto da minha CNH nova três vezes e o sistema recusa dizendo que está ilegível.', 'prioridade' => 'normal', 'autor' => 'motorista'],
                    ['assunto' => 'Inclusão de filial no CNPJ', 'msg' => 'Precisamos faturar os fretes por uma nova filial. Como adiciono um segundo CNPJ na mesma conta?', 'prioridade' => 'normal', 'autor' => 'embarcador'],
                    ['assunto' => 'Atualização de placa do cavalo mecânico', 'msg' => 'Vendi o caminhão antigo e comprei um novo. Onde troco o documento do veículo?', 'prioridade' => 'baixa', 'autor' => 'motorista'],
                    ['assunto' => 'Conta bloqueada sem motivo aparente', 'msg' => 'Fui acessar o sistema hoje cedo e diz que meu perfil está sob análise de compliance.', 'prioridade' => 'alta', 'autor' => 'motorista'],
                    ['assunto' => 'Dificuldade com reconhecimento facial', 'msg' => 'A câmera do meu celular está com o vidro trincado e não consigo passar pela prova de vida.', 'prioridade' => 'normal', 'autor' => 'motorista'],
                ],
                'Dúvida Técnica' => [
                    ['assunto' => 'Documentação do Webhook', 'msg' => 'Onde encontro a documentação da API para integrar a atualização de status direto com o nosso ERP SAP?', 'prioridade' => 'baixa', 'autor' => 'embarcador'],
                    ['assunto' => 'Cálculo de cubagem automático', 'msg' => 'O sistema calcula o peso cubado automaticamente se eu inserir as dimensões das caixas?', 'prioridade' => 'baixa', 'autor' => 'embarcador'],
                    ['assunto' => 'Dúvida sobre roteirizador', 'msg' => 'As rotas sugeridas incluem restrição de circulação (ZMRC) em São Paulo?', 'prioridade' => 'normal', 'autor' => 'motorista'],
                    ['assunto' => 'Exportação de relatórios em Excel', 'msg' => 'Consigo baixar o extrato financeiro em formato CSV ou apenas em PDF?', 'prioridade' => 'baixa', 'autor' => 'embarcador'],
                    ['assunto' => 'Multiplos utilizadores na mesma conta', 'msg' => 'É possível criar um login separado apenas para o meu analista financeiro acessar as faturas?', 'prioridade' => 'normal', 'autor' => 'embarcador'],
                ]
            ];

            $respostasSuporte = [
                'Entendi a situação. Estou acionando o setor responsável para intervir imediatamente. Por favor, aguarde na linha.',
                'Recebemos a sua solicitação. O protocolo foi encaminhado para a equipe de auditoria e retornaremos em até 2 horas.',
                'Para prosseguirmos com a tratativa, preciso que você envie uma foto do documento ou da tela com o erro, por favor.',
                'Realizei uma atualização forçada no seu perfil. Por favor, deslogue do aplicativo, limpe o cache e tente novamente.',
                'Compreendo o transtorno. Já bloqueamos o pagamento cautelarmente enquanto analisamos os fatos descritos.',
            ];

            $statusTicket = ['aberto', 'em_atendimento', 'aguardando_cliente', 'resolvido'];
            
            // Separar IDs reais do banco de dados
            $idsEmbarcadores = array_map(fn($e) => $e['user_id'], $embarcadores);
            $idsMotoristas = array_map(fn($m) => $m['user_id'], $motoristas);
            $idsCargas = array_map(fn($c) => $c['id'], $cargas);

            foreach ($cenarios as $categoria => $listaCenarios) {
                // Força a criação de 10 tickets por categoria
                for ($i = 0; $i < 10; $i++) {
                    $cenario = $faker->randomElement($listaCenarios);
                    
                    $clienteId = $cenario['autor'] === 'embarcador' 
                        ? $faker->randomElement($idsEmbarcadores) 
                        : $faker->randomElement($idsMotoristas);
                    
                    $status = $faker->randomElement($statusTicket);
                    $staffId = ($status === 'aberto') ? null : $faker->randomElement($equipeN1)->id;
                    $cargaId = $faker->boolean(50) ? $faker->randomElement($idsCargas) : null;

                    $ticket = Ticket::create([
                        'user_id' => $clienteId,
                        'staff_id' => $staffId,
                        'carga_id' => $cargaId,
                        'assunto' => $cenario['assunto'],
                        'categoria' => $categoria,
                        'prioridade' => $cenario['prioridade'],
                        'status' => $status,
                        'created_at' => $faker->dateTimeBetween('-10 days', 'now')
                    ]);

                    // Mensagem Inicial (Cliente)
                    TicketMessage::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $clienteId,
                        'mensagem' => $cenario['msg'],
                        'created_at' => $ticket->created_at
                    ]);

                    // Resposta do Suporte (Se o ticket não estiver aberto)
                    if ($status !== 'aberto' && $staffId) {
                        TicketMessage::create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $staffId,
                            'mensagem' => 'Olá! ' . $faker->randomElement($respostasSuporte),
                            'created_at' => $faker->dateTimeBetween($ticket->created_at, 'now')
                        ]);

                        // Replica do cliente (Se o status indicar que o chat andou)
                        if ($status === 'em_atendimento' || $status === 'resolvido') {
                            TicketMessage::create([
                                'ticket_id' => $ticket->id,
                                'user_id' => $clienteId,
                                'mensagem' => 'Certo, fico no aguardo da resolução.',
                                'created_at' => $faker->dateTimeBetween($ticket->created_at, 'now')
                            ]);
                        }
                    }
                }
            }
        });

        $this->command->info('✅ Geração em massa concluída. Ecossistema pronto para análise.');
    }
}