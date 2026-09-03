import { ref } from 'vue';
import axios from 'axios';

export function useSeguroIza() {
    const seguroStatus = ref(null);
    const isLoading = ref(false);
    const error = ref(null);

    const carregarStatus = async () => {
        try {
            isLoading.value = true;
            const { data } = await axios.get('/api/v1/motorista/seguros');
            seguroStatus.value = data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Erro ao carregar status';
        } finally {
            isLoading.value = false;
        }
    };

    const contratarPlano = async (plano) => {
        try {
            isLoading.value = true;
            await axios.post('/api/v1/motorista/seguros/contratar', { plano });
            await carregarStatus(); // Recarrega para mudar o status para 'pendente_emissao'
            return true;
        } catch (err) {
            error.value = err.response?.data?.error || 'Erro ao contratar seguro';
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    return { seguroStatus, isLoading, error, carregarStatus, contratarPlano };
}