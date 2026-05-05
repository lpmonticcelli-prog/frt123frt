// [INJEÇÃO CLÍNICA] Interceptor Dinâmico de Rotas Órfãs
window.axios.interceptors.request.use(config => {
    if (config.url && config.url.includes('cargas?page=')) {
        // Se a rota está solta (sem o domínio do embarcador ou motorista)
        if (!config.url.includes('/embarcador/') && !config.url.includes('/motorista/')) {
            // Corrige URLs absolutas
            config.url = config.url.replace('/api/v1/cargas', '/api/v1/embarcador/cargas');
            // Corrige URLs relativas
            config.url = config.url.replace('cargas?page=', 'embarcador/cargas?page=');
            
            console.warn('🛡️ [Anti-Fragilidade] Rota órfã detectada e corrigida para:', config.url);
        }
    }
    return config;
});
