<?php

namespace App\Jobs;

use App\Models\Carga;
use App\Models\User;
use App\Contracts\RiskManagementInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessarAuditoriaTransatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $cargaId;
    public $motoristaId;
    public $tries = 3; 

    public function __construct(int $cargaId, int $motoristaId)
    {
        $this->cargaId = $cargaId;
        $this->motoristaId = $motoristaId;
    }

    public function handle(RiskManagementInterface $grService)
    {
        try {
            $carga = Carga::find($this->cargaId);
            $motorista = User::find($this->motoristaId);

            if (!$carga || !$motorista) return;

            // DICA: Ajuste "doc_cnh_url" para o nome exato da coluna no seu banco de dados
            $caminhoCnh = $motorista->doc_cnh_url ?? null; 
            if (!$caminhoCnh || !Storage::exists($caminhoCnh)) {
                 throw new \Exception("Documento CNH não encontrado.");
            }

            $conteudoCnh = Storage::get($caminhoCnh);
            $mimeTypeCnh = Storage::mimeType($caminhoCnh);

            // OCR
            $dadosCnh = $grService->extrairDadosOcr('cnh', $conteudoCnh, $mimeTypeCnh);

            $cpfLimpo = preg_replace('/[^0-9]/', '', $dadosCnh['cpf'] ?? $motorista->cpf);

            $payloadMotorista = [
                'documento'    => $cpfLimpo,
                'nome'         => $dadosCnh['nome'] ?? $motorista->name,
                'mae'          => $dadosCnh['nome_da_mae'] ?? '',
                'pai'          => $dadosCnh['nome_do_pai'] ?? '',
                'data_nasc'    => $dadosCnh['data_nascimento'] ?? '2000-01-01',
                'cnh'          => preg_replace('/[^0-9]/', '', $dadosCnh['cnh_numero'] ?? ''),
                'cnh_cat'      => $dadosCnh['cnh_categoria'] ?? 'B',
                'cnh_uf'       => $dadosCnh['cnh_uf'] ?? 'SP',
                'cnh_first'    => $dadosCnh['data_primeira_cnh'] ?? '2020-01-01',
                'cnh_validate' => $dadosCnh['cnh_validade'] ?? '2030-01-01',
                'cnh_emissao'  => $dadosCnh['data_emissao_cnh'] ?? '2020-01-01',
                'telefone'     => preg_replace('/[^0-9]/', '', $motorista->telefone ?? '11999999999'),
            ];

            // Veículo: Ajuste os nomes das colunas conforme o seu model de Motorista/Veiculo
            $payloadVeiculos = [];
            if ($motorista->veiculo_placa) {
                $payloadVeiculos[] = [
                    'placa'      => preg_replace('/[^a-zA-Z0-9]/', '', $motorista->veiculo_placa),
                    'renavam'    => preg_replace('/[^0-9]/', '', $motorista->veiculo_renavam),
                    'uf'         => strtoupper($motorista->veiculo_uf ?? 'SP'),
                    'check_antt' => true,
                    'doc_antt'   => '',
                    'proprietario' => [
                        'tipo' => 'PF',
                        'documento' => $cpfLimpo,
                        'nome' => $payloadMotorista['nome'],
                        'mae' => $payloadMotorista['mae'],
                        'pai' => $payloadMotorista['pai'],
                        'data_nasc' => $payloadMotorista['data_nasc'],
                    ]
                ];
            }

            // Consultar
            $resultadoConsulta = $grService->consultarRisco($payloadMotorista, $payloadVeiculos);

            // Montagem da URL da Biometria
            $empresaId = config('services.transat.empresa_id');
            $urlBiometria = null;
            if(!empty($resultadoConsulta['url'])) {
                $urlBiometria = "https://gr.app.br/validacao/f/{$empresaId}/" . $resultadoConsulta['url'];
            }

            $carga->update([
                'status' => 'em_analise_gr',
                'gr_referencia' => $resultadoConsulta['referencia'],
                'gr_biometria_url' => $urlBiometria
            ]);

        } catch (\Exception $e) {
            Log::critical('[JOB TRANSAT ERRO]', ['msg' => $e->getMessage()]);
            if (isset($carga)) {
                $carga->update(['status' => 'erro_analise_gr']);
            }
            throw $e;
        }
    }
}