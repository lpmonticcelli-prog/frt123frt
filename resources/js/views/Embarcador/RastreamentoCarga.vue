<template>
  <div class="space-y-6 h-[calc(100vh-100px)] flex flex-col">
    <div class="bg-gray-900 rounded-lg shadow-sm px-6 py-4 flex justify-between items-center shrink-0">
      <div>
        <h3 class="text-xl font-black text-white flex items-center">
          <span class="relative flex h-3 w-3 mr-3">
            <span v-if="isConnected" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3" :class="isConnected ? 'bg-green-500' : 'bg-red-500'"></span>
          </span>
          Auditoria de Rota em Tempo Real
        </h3>
        <p class="text-sm text-gray-400 mt-1">
          Carga: <span class="text-white font-bold">{{ cargaInfo?.produto || '...' }}</span> 
          | Destino: <span class="text-blue-400 font-bold">{{ cargaInfo?.cidade_destino }}/{{ cargaInfo?.uf_destino }}</span>
        </p>
      </div>
      
      <div class="flex items-center space-x-4">
        <div v-if="lastUpdate" class="text-xs text-gray-400 text-right">
          Sinal Recebido:<br>
          <strong class="text-white">{{ lastUpdate }}</strong>
        </div>
        <button @click="voltar" class="text-sm font-bold text-gray-300 hover:text-white border border-gray-600 px-4 py-2 rounded transition-colors">
          Sair do Mapa
        </button>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 flex-grow relative overflow-hidden">
      <div v-if="!lastUpdate" class="absolute inset-0 z-20 bg-gray-900 flex flex-col items-center justify-center text-white">
        <div class="relative w-24 h-24 mb-4">
          <div class="absolute inset-0 border-4 border-blue-500/20 rounded-full"></div>
          <div class="absolute inset-0 border-4 border-blue-500 rounded-full border-t-transparent animate-spin"></div>
        </div>
        <p class="text-lg font-black tracking-widest">LOCALIZANDO VEÍCULO</p>
        <p class="text-gray-400 text-sm mt-2">Conectando ao rastreador da carga #{{ cargaId }}</p>
      </div>
      
      <div id="map" class="w-full h-full z-10"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, markRaw } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

const route = useRoute();
const router = useRouter();

const cargaId = ref(route.params.id);
const cargaInfo = ref(null);
const isConnected = ref(false);
const lastUpdate = ref(null);

let ws = null;
let map = null;
let truckMarker = null;

// Busca detalhes da carga para saber o destino
const fetchCargaDetails = async () => {
  try {
    const response = await axios.get(`/api/cargas/${cargaId.value}`);
    cargaInfo.value = response.data;
  } catch (error) {
    console.error("Erro ao carregar detalhes da carga");
  }
};

const initMap = () => {
  map = markRaw(new maplibregl.Map({
    container: 'map',
    style: 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json',
    center: [-47.9292, -15.7801], // Brasilia inicial
    zoom: 4,
    pitch: 45,
    antialias: true
  }));

  map.addControl(new maplibregl.NavigationControl(), 'top-right');

  map.on('load', () => {
    // Camada da Linha de Rota (Polyline)
    map.addSource('route', {
      'type': 'geojson',
      'data': {
        'type': 'Feature',
        'properties': {},
        'geometry': {
          'type': 'LineString',
          'coordinates': [] // Será preenchido com o histórico de posições
        }
      }
    });

    map.addLayer({
      'id': 'route',
      'type': 'line',
      'source': 'route',
      'layout': { 'line-join': 'round', 'line-cap': 'round' },
      'paint': {
        'line-color': '#2563eb',
        'line-width': 5,
        'line-opacity': 0.6
      }
    });
  });
};

const createTruckMarkerElement = () => {
  const el = document.createElement('div');
  el.className = 'truck-marker-container';
  const arrow = document.createElement('div');
  arrow.className = 'truck-arrow';
  el.appendChild(arrow);
  return el;
};

// Histórico para desenhar a linha
const routeCoordinates = [];

const connectWebSocket = () => {
  ws = new WebSocket('ws://localhost:8080/ws/shipper');

  ws.onopen = () => {
    isConnected.value = true;
    ws.send(JSON.stringify({ carga_id: parseInt(cargaId.value) }));
  };

  ws.onmessage = (event) => {
    try {
      const data = JSON.parse(event.data);
      const lat = parseFloat(data.lat);
      const lng = parseFloat(data.lng);
      const heading = parseFloat(data.heading || 0);
      
      if (isNaN(lat) || isNaN(lng)) return;

      const coordinates = [lng, lat];
      routeCoordinates.push(coordinates);

      const now = new Date();
      lastUpdate.value = now.toLocaleTimeString('pt-PT');

      if (!truckMarker) {
        // PRIMEIRA LOCALIZAÇÃO: IR DIRETO
        truckMarker = new maplibregl.Marker({ 
          element: createTruckMarkerElement(),
          rotation: heading,
          rotationAlignment: 'map'
        })
          .setLngLat(coordinates)
          .addTo(map);

        // Pulo imediato para a posição
        map.jumpTo({
          center: coordinates,
          zoom: 17
        });
      } else {
        truckMarker.setLngLat(coordinates);
        truckMarker.setRotation(heading);
        
        // Atualiza a linha no mapa
        if (map.getSource('route')) {
          map.getSource('route').setData({
            'type': 'Feature',
            'geometry': { 'type': 'LineString', 'coordinates': routeCoordinates }
          });
        }

        map.panTo(coordinates, { duration: 1500 });
      }
    } catch (error) {
      console.error("Erro GPS:", error);
    }
  };

  ws.onclose = () => {
    isConnected.value = false;
    setTimeout(connectWebSocket, 5000);
  };
};

const voltar = () => { router.push({ name: 'EmbarcadorDashboard' }); };

onMounted(async () => {
  await fetchCargaDetails();
  initMap();
  connectWebSocket();
});

onBeforeUnmount(() => {
  if (ws) ws.close();
  if (map) map.remove();
});
</script>

<style>
.truck-marker-container {
  width: 45px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.5s ease-out;
}
.truck-arrow {
  width: 0; height: 0;
  border-left: 12px solid transparent;
  border-right: 12px solid transparent;
  border-bottom: 28px solid #2563eb;
  filter: drop-shadow(0 4px 3px rgba(0,0,0,0.3));
}
</style>