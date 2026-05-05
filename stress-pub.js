const HTTP_ENDPOINT = 'http://localhost:8000/api/stress-test/publicar';
const TOTAL_REQUESTS = 10000; 
const CONCURRENCY = 500;      

async function sendRequest(id) {
    try {
        const response = await fetch(HTTP_ENDPOINT, { 
            method: 'POST',
            headers: { 'Accept': 'application/json' }
        });
        return { id, status: response.status };
    } catch (error) {
        return { id, status: 'ERROR' };
    }
}

async function runStressTest() {
    console.log(`\n🚀 Iniciando Teste de Stress (Publicação): ${TOTAL_REQUESTS} requisições.`);
    console.log(`🔥 Concorrência: ${CONCURRENCY} disparos simultâneos por lote.\n`);
    
    const startTime = performance.now();
    let successCount = 0;
    let errorCount = 0;

    for (let i = 0; i < TOTAL_REQUESTS; i += CONCURRENCY) {
        const batch = [];
        for (let j = 0; j < CONCURRENCY && (i + j) < TOTAL_REQUESTS; j++) {
            batch.push(sendRequest(i + j));
        }
        
        const results = await Promise.all(batch);
        results.forEach(r => r.status === 202 ? successCount++ : errorCount++);
        
        process.stdout.write(`\rProgresso: ${Math.min(i + CONCURRENCY, TOTAL_REQUESTS)} / ${TOTAL_REQUESTS}`);
    }

    const endTime = performance.now();
    const duration = (endTime - startTime) / 1000;

    console.log(`\n\n✅ Teste Concluído em ${duration.toFixed(2)} segundos.`);
    console.log(`🟢 Sucessos (HTTP 202 - Redis Queue): ${successCount}`);
    console.log(`🔴 Falhas (Erro ou Timeout): ${errorCount}`);
    console.log(`⚡ Throughput do Servidor: ${(TOTAL_REQUESTS / duration).toFixed(2)} requisições por segundo.`);
}

runStressTest();
