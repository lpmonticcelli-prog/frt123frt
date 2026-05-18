<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 600px; background-color: #ffffff; margin: 0 auto; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; }
        .header { background-color: #111827; padding: 25px 30px; text-align: center; }
        .header h1 { color: #3b82f6; margin: 0; font-size: 24px; letter-spacing: 1px; }
        .content { padding: 30px; color: #374151; line-height: 1.6; }
        .token-box { background-color: #fef2f2; border: 1px solid #fca5a5; padding: 15px; border-radius: 6px; margin: 25px 0; word-break: break-all; text-align: center;}
        .token { font-family: monospace; font-size: 16px; color: #991b1b; font-weight: bold; }
        .code-block { background-color: #1f2937; color: #e5e7eb; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 13px; margin: 20px 0; overflow-x: auto; }
        .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>123fretei <span style="color:#ffffff; font-size:12px; vertical-align:middle; background:#dc2626; padding:2px 6px; border-radius:4px; margin-left:10px;">B2B</span></h1>
        </div>
        <div class="content">
            <p>Olá, equipe de TI da <strong>{{ $nomeParceiro }}</strong>.</p>
            <p>O nosso ambiente B2B está pronto para a integração. Abaixo estão as credenciais geradas exclusivamente para o acesso dos vossos servidores à plataforma 123fretei.</p>
            
            <div class="token-box">
                <p style="margin:0 0 5px 0; font-size:12px; color:#7f1d1d; text-transform:uppercase; font-weight:bold;">Bearer Token (Chave de Acesso)</p>
                <span class="token">{{ $token }}</span>
            </div>

            <p><strong>Detalhamento Técnico:</strong></p>
            <ul style="padding-left: 20px; font-size: 14px;">
                <li><strong>Endpoint de Homologação/Produção:</strong> <br><code>https://api.seusite.com.br/api/v1/partners/gr/analise/callback</code></li>
                <li><strong>Método:</strong> POST</li>
                <li><strong>Escopo de Segurança Liberado:</strong> <code>{{ $scope }}</code></li>
            </ul>

            <p><strong>Exemplo de Header HTTP exigido:</strong></p>
            <div class="code-block">
                Authorization: Bearer {{ $token }}<br>
                Accept: application/json
            </div>

            <p style="font-size: 13px; color: #ef4444; font-weight: bold;">⚠️ Atenção: Por políticas de segurança (Zero Trust), guarde este token num cofre seguro (Vault/.env). Qualquer tentativa de aceder a endpoints fora do escopo concedido será bloqueada automaticamente pelo nosso Firewall de Aplicação (WAF).</p>
        </div>
        <div class="footer">
            Este é um e-mail automático gerado pelo Gateway de APIs do 123fretei. <br>
            Não responda a este endereço. Em caso de dúvidas, contacte o seu gestor de contas.
        </div>
    </div>
</body>
</html>