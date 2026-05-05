<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      
      <div class="px-6 py-5 border-b border-gray-200 bg-gray-900 flex justify-between items-center">
        <div>
          <h3 class="text-xl font-black text-white flex items-center">
            <span class="relative flex h-3 w-3 mr-3">
              <span v-if="isTransmitting" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3" :class="isTransmitting ? 'bg-green-500' : 'bg-red-500'"></span>
            </span>
            Painel de Viagem Ativa
          </h3>
          <p class="text-sm text-gray-400 mt-1">Carga ID: {{ cargaId }}</p>
        </div>
        <button @click="voltar" class="text-sm font-bold text-gray-300 hover:text-white border border-gray-600 px-3 py-1.5 rounded transition-colors">
          Voltar aos Fretes
        </button>
      </div>

      <div class="p-6">
        <div :class="['p-4 rounded-lg border-l-4 font-bold flex items-center', isTransmitting ? 'bg-green-50 border-green-500 text-green-800' : 'bg-red-50 border-red-500 text-red-800']">
          <svg v-if="isTransmitting" class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
          <svg v-else class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
          
          <div>
            <div class="text-base">{{ isTransmitting ? 'Transmissão GPS Ativa' : 'Sinal GPS Perdido / Desconectado' }}</div>
            <div class="text-xs font-normal mt-1 opacity-80">
              {{ isTransmitting ? 'A sua localização e rotação (vetor) estão a ser enviadas.' : 'Aguardando conexão com o satélite e servidor.' }}
            </div>
          </div>
        </div>

        <div class="mt-6 grid grid-cols-2 md:grid-cols-3 gap-4">
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Latitude</span>
            <span class="text-lg font-mono text-gray-900">{{ currentLat?.toFixed(6) || '---.------' }}</span>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Longitude</span>
            <span class="text-lg font-mono text-gray-900">{{ currentLng?.toFixed(6) || '---.------' }}</span>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 col-span-2 md:col-span-1">
            <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Rotação (Azimute)</span>
            <span class="text-lg font-mono text-gray-900">{{ currentHeading ? currentHeading.toFixed(1) + '°' : 'Calculando...' }}</span>
          </div>
        </div>
        
        <div class="mt-6 text-xs text-gray-500 text-center font-medium">
          Mantenha esta página aberta ou o ecrã do telemóvel ativo enquanto conduz.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const cargaId = ref(route.params.id);
const isTransmitting = ref(false);
const currentLat = ref(null);
const currentLng = ref(null);
const currentHeading = ref(0); // Novo estado para o ângulo

let ws = null;
let geoWatcherId = null;

// Lógica Matemática de Azimute (Caso o telemóvel não mande o Heading)
let lastLat = null;
let lastLng = null;

const toRadians = (degrees) => degrees * Math.PI / 180;
const toDegrees = (radians) => radians * 180 / Math.PI;

const calculateBearing = (startLat, startLng, destLat, destLng) => {
  startLat = toRadians(startLat);
  startLng = toRadians(startLng);
  destLat = toRadians(destLat);
  destLng = toRadians(destLng);

  const y = Math.sin(destLng - startLng) * Math.cos(destLat);
  const x = Math.cos(startLat) * Math.sin(destLat) -
            Math.sin(startLat) * Math.cos(destLat) * Math.cos(destLng - startLng);
  
  let brng = Math.atan2(y, x);
  brng = toDegrees(brng);
  return (brng + 360) % 360; // Converte para o eixo de 0 a 360 graus
};

const connectWebSocket = () => {
  ws = new WebSocket('ws://localhost:8080/ws/driver');

  ws.onopen = () => {
    startTracking();
  };

  ws.onclose = () => {
    isTransmitting.value = false;
    setTimeout(connectWebSocket, 5000);
  };

  ws.onerror = (err) => {
    ws.close();
  };
};

const startTracking = () => {
  if (!navigator.geolocation) {
    alert('Geolocalização não é suportada.');
    return;
  }

  geoWatcherId = navigator.geolocation.watchPosition(
    (position) => {
      currentLat.value = position.coords.latitude;
      currentLng.value = position.coords.longitude;
      
      // 1. Tenta pegar o ângulo nativo do hardware (se estiver em movimento)
      if (position.coords.heading !== null && !isNaN(position.coords.heading)) {
        currentHeading.value = position.coords.heading;
      } 
      // 2. Se falhar, calcula matematicamente com base na diferença espacial
      else if (lastLat !== null && lastLng !== null && (lastLat !== currentLat.value || lastLng !== currentLng.value)) {
        currentHeading.value = calculateBearing(lastLat, lastLng, currentLat.value, currentLng.value);
      }

      // Salva as coordenadas para o próximo cálculo trigonométrico
      lastLat = currentLat.value;
      lastLng = currentLng.value;

      if (ws && ws.readyState === WebSocket.OPEN) {
        // Injeta a variável 'heading' no JSON que o Go vai retransmitir
        const payload = {
          driver_id: authStore.user?.id,
          carga_id: parseInt(cargaId.value),
          lat: currentLat.value,
          lng: currentLng.value,
          heading: currentHeading.value 
        };
        ws.send(JSON.stringify(payload));
        isTransmitting.value = true;
      }
    },
    (error) => {
      isTransmitting.value = false;
    },
    {
      enableHighAccuracy: true,
      maximumAge: 0, // Força a leitura fresca do satélite
      timeout: 10000
    }
  );
};

const voltar = () => {
  router.push({ name: 'MeusFretes' }); // Correção do nome da rota no botão 'Sair' do Motorista
};

onMounted(() => {
  connectWebSocket();
});

onBeforeUnmount(() => {
  if (geoWatcherId !== null) navigator.geolocation.clearWatch(geoWatcherId);
  if (ws) ws.close();
});
</script>