<template>
  <div>
    <div v-if="isLoading" class="py-8 text-center text-on-surface-variant font-body-md text-body-md">
      Cargando actividad...
    </div>
    <template v-else>
      <div class="flex flex-col justify-center overflow-x-auto pb-2">
        <div class="flex gap-1">
          <div v-for="(week, wi) in weeks" :key="wi" class="flex flex-col gap-1">
            <div
              v-for="(day, di) in week"
              :key="di"
              :title="day ? `${day.date}: ${day.count} reseñas` : ''"
              class="h-8 w-8 rounded-[6px]"
              :style="{ background: day ? colorFor(day.count) : 'transparent', opacity: day ? 0.55 + Math.min(day.count, 4) * 0.11 : 0 }"
            />
          </div>
        </div>
        <div class="mt-3 flex justify-between text-label-sm text-outline">
          <span>{{ total }} reseñas este año</span>
          <span class="flex items-center gap-1">menos
            <span v-for="c in scale" :key="c" class="inline-block h-3 w-3 rounded-[3px]" :style="{ background: c }"></span>
            más</span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const props = defineProps({
  userId: { type: [Number, String], default: null },
});

const weeks = ref([]);
const total = ref(0);
const isLoading = ref(true);
const scale = ['#f5f9f4', '#dcefd9', '#bfe0bf', '#9dd19c', '#74bd77', '#4fa856', '#347d3d'];

const colorFor = (count) => {
  if (count <= 0) return scale[0];
  if (count === 1) return scale[2];
  if (count === 2) return scale[3];
  if (count <= 4) return scale[4];
  return scale[6];
};

onMounted(async () => {
  try {
    const params = { days: 365 };
    if (props.userId) params.user_id = props.userId;
    const { data } = await api.get('/api/analytics/activity', { params });
    const byDay = Object.fromEntries((data ?? []).map((d) => [d.day, d.count]));

    // Últimas 26 semanas (columnas) x 7 días, alineadas a lunes
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const monday = new Date(today);
    monday.setDate(today.getDate() - ((today.getDay() + 6) % 7) - 7 * 25);

    const cols = [];
    let sum = 0;
    for (let w = 0; w < 26; w++) {
      const week = [];
      for (let d = 0; d < 7; d++) {
        const date = new Date(monday);
        date.setDate(monday.getDate() + w * 7 + d);
        if (date > today) {
          week.push(null);
          continue;
        }
        const key = date.toISOString().slice(0, 10);
        const count = byDay[key] ?? 0;
        sum += count;
        week.push({ date: key, count });
      }
      cols.push(week);
    }
    weeks.value = cols;
    total.value = sum;
  } catch (e) {
    console.error('Error cargando actividad:', e);
  } finally {
    isLoading.value = false;
  }
});
</script>
