<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 40px 20px; color: #1f2937; }
        .wrapper { max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .header { background-color: #0f172a; padding: 24px 32px; border-bottom: 1px solid #1e293b; display: flex; align-items: center; justify-content: space-between; }
        .header h1 { font-size: 18px; font-weight: 600; color: #f8fafc; margin: 0; font-family: monospace; }
        .badge { background-color: #2563eb; color: #ffffff; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 9999px; letter-spacing: 0.5px; text-transform: uppercase; }
        .body { padding: 32px; }
        .body p { font-size: 15px; line-height: 1.6; margin: 0 0 20px 0; }
        .data-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin-bottom: 24px; }
        .data-row { margin-bottom: 12px; }
        .data-row:last-child { margin-bottom: 0; }
        .label { display: block; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .value { font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 14px; color: #0f172a; word-break: break-all; }
        .token-highlight { background-color: #fff1f2; border: 1px solid #fecdd3; color: #e11d48; padding: 8px 12px; border-radius: 4px; display: inline-block; font-weight: 600; }
        .code-snippet { background-color: #1e293b; color: #e2e8f0; padding: 16px; border-radius: 6px; font-family: 'SFMono-Regular', Consolas, Menlo, monospace; font-size: 13px; overflow-x: auto; margin-top: 8px; line-height: 1.5; }
        .sec-ops { border-left: 4px solid #f59e0b; background-color: #fffbeb; padding: 16px; margin-top: 24px; border-radius: 0 6px 6px 0; }
        .sec-ops p { margin: 0; font-size: 13px; color: #92400e; }
        .footer { padding: 24px 32px; background-color: #f8fafc; border-top: 1px solid #e5e7eb; font-size: 13px; color: #64748b; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>[SYS] B2B_GATEWAY_PROVISIONING</h1>
            <span class="badge">Production</span>
        </div>
        
        <div class="body">
            <p>Engenharia <strong>{{ $nomeParceiro }}</strong>,</p>
            <p>As credenciais de integração para o ambiente de produção do <strong>123fretei</strong> foram provisionadas com sucesso. Seguem as especificações de acesso.</p>
            
            <div class="data-box">
                <div class="data-row">
                    <span class="label">Bearer Token (Auth)</span>
                    <span class="value token-highlight">{{ $token }}</span>
                </div>
                <div class="data-row" style="margin-top: 16px;">
                    <span class="label">Scope (ACL)</span>
                    <span class="value" style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 12px;">{{ $scope }}</span>
                </div>
            </div>

            <p style="font-weight: 600; font-size: 14px; margin-bottom: 8px;">Specs do Endpoint:</p>
            <div class="code-snippet">
POST {{ url('/api/v1/partners/gr/analise/callback') }}<br>
Content-Type: application/json<br>
Accept: application/json<br>
Authorization: Bearer {{ $token }}
            </div>

            <div class="sec-ops">
                <p><strong>Aviso de Segurança:</strong> O 123fretei não armazena a versão em texto plano desta credencial, impossibilitando sua recuperação em caso de perda. A custódia segura, o armazenamento em cofres de chaves corporativos e a rotação deste segredo são de responsabilidade exclusiva do cliente. O uso da API está sujeito aos limites de requisição (rate limits) vigentes.</p>
            </div>
        </div>

        <div class="footer">
            Automated message triggered by 123fretei API Gateway. <br>
            Em caso de dúvidas com o payload JSON, contatem o time de integrações.
        </div>
    </div>
</body>
</html>