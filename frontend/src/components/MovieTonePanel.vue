<template>
  <div class="flex flex-col items-center relative overflow-hidden">
    <h3 class="font-headline-md text-headline-md mb-4 text-primary w-full text-center">Análisis de Tono</h3>
    <div class="relative w-40 h-40 mt-2">
      <div class="absolute inset-0 border-2 border-outline-variant/30 rounded-full"></div>
      <div class="absolute inset-4 border-2 border-outline-variant/30 rounded-full"></div>
      <div class="absolute inset-8 border-2 border-outline-variant/30 rounded-full"></div>
      <div class="absolute inset-0 w-full h-full" style="background: linear-gradient(0deg, transparent 49%, rgba(193, 201, 190, 0.3) 49%, rgba(193, 201, 190, 0.3) 51%, transparent 51%);"></div>
      <div class="absolute inset-0 w-full h-full" style="background: linear-gradient(90deg, transparent 49%, rgba(193, 201, 190, 0.3) 49%, rgba(193, 201, 190, 0.3) 51%, transparent 51%);"></div>
      <div class="absolute inset-0 m-auto w-24 h-24 bg-primary/30 rounded-[30%_70%_70%_30%/30%_30%_70%_70%] border border-primary/50 mix-blend-multiply" style="transform: rotate(45deg);"></div>
    </div>
    <div class="absolute top-1/2 left-0 -translate-y-1/2 font-label-sm text-label-sm text-on-surface-variant">Oscuro</div>
    <div class="absolute top-1/2 right-0 -translate-y-1/2 font-label-sm text-label-sm text-on-surface-variant">Luminoso</div>
    <div class="absolute top-10 left-1/2 -translate-x-1/2 font-label-sm text-label-sm text-on-surface-variant">Complejo</div>
    <div class="absolute bottom-2 left-1/2 -translate-x-1/2 font-label-sm text-label-sm text-on-surface-variant">Accesible</div>
  </div>
</template>