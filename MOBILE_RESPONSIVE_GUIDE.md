# Mobilni Responsive Dizajn - WizFlussi

## Pregled

Ovaj dokument sadrži smjernice za implementaciju mobilnog responsive dizajna.

## Implementirane Komponente

### 1. useDevice Composable
Lokacija: `resources/js/composables/useDevice.js`

Koristi se za detekciju tipa uređaja:
```javascript
import { useDevice } from '@/composables/useDevice';

const { isMobile, isTablet, isDesktop } = useDevice();
```

## Ključne Izmjene po Stranicama

### Dashboard (Pregled)
**Desktop:**
- Grid layout sa stat karticama (4 kolone)
- Puna tabela sa svim kolonama
- Filteri u jednom redu

**Mobile:**
- Stack layout (1 kolona)
- Kartice umjesto tabele
- Filteri u dropdown meniju
- Swipe za akcije

### Payments (Plaćeno)
**Mobile:**
- Lista kartica umjesto tabele
- Tap za detalje
- Bottom sheet za akcije

### Suppliers (Dobavljači)
**Mobile:**
- Lista view po defaultu
- Expandable poslovnice
- Floating action button za dodavanje

### Plans (Planovi)
**Mobile:**
- Kartice u stack layoutu
- Swipe za brisanje
- Simplified header

## Tailwind Breakpoints

```css
sm: 640px   /* Small devices */
md: 768px   /* Tablets */
lg: 1024px  /* Laptops */
xl: 1280px  /* Desktops */
2xl: 1536px /* Large screens */
```

## Responsive Patterns

### 1. Tabele → Kartice
```vue
<!-- Desktop: Table -->
<table class="hidden md:table">
  <!-- table content -->
</table>

<!-- Mobile: Cards -->
<div class="md:hidden space-y-2">
  <div v-for="item in items" class="card">
    <!-- card content -->
  </div>
</div>
```

### 2. Grid → Stack
```vue
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  <!-- Auto responsive -->
</div>
```

### 3. Sidebar Navigation
```vue
<!-- Desktop: Sidebar -->
<aside class="hidden lg:block">
  <!-- sidebar -->
</aside>

<!-- Mobile: Bottom nav or hamburger -->
<nav class="lg:hidden fixed bottom-0">
  <!-- mobile nav -->
</nav>
```

### 4. Modals → Bottom Sheets
```vue
<!-- Desktop: Center modal -->
<div class="hidden md:block fixed inset-0">
  <div class="max-w-2xl mx-auto">
    <!-- modal -->
  </div>
</div>

<!-- Mobile: Bottom sheet -->
<div class="md:hidden fixed inset-x-0 bottom-0">
  <!-- bottom sheet -->
</div>
```

## Komponente za Mobilni Prikaz

### MobileCard.vue
```vue
<template>
  <div class="bg-white rounded-lg shadow p-4 border">
    <slot />
  </div>
</template>
```

### MobileList.vue
```vue
<template>
  <div class="space-y-2">
    <slot />
  </div>
</template>
```

### BottomSheet.vue
```vue
<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50">
      <div @click="$emit('close')" class="absolute inset-0 bg-black/50"></div>
      <div class="absolute inset-x-0 bottom-0 bg-white rounded-t-2xl p-4">
        <slot />
      </div>
    </div>
  </Teleport>
</template>
```

### FloatingActionButton.vue
```vue
<template>
  <button class="fixed bottom-4 right-4 w-14 h-14 bg-blue-500 text-white rounded-full shadow-lg">
    <slot />
  </button>
</template>
```

## Touch Gestures

### Swipe Actions
```javascript
let touchStartX = 0;
let touchEndX = 0;

const handleTouchStart = (e) => {
  touchStartX = e.changedTouches[0].screenX;
};

const handleTouchEnd = (e) => {
  touchEndX = e.changedTouches[0].screenX;
  handleSwipe();
};

const handleSwipe = () => {
  if (touchEndX < touchStartX - 50) {
    // Swipe left
  }
  if (touchEndX > touchStartX + 50) {
    // Swipe right
  }
};
```

## Optimizacije za Mobilne Uređaje

### 1. Touch Targets
- Minimum 44x44px za touch targets
- Spacing između elemenata min 8px

### 2. Font Sizes
```css
/* Mobile first */
text-base: 16px  /* Prevents iOS zoom */
text-sm: 14px
text-xs: 12px
```

### 3. Loading States
- Skeleton screens za bolje UX
- Lazy loading slika
- Infinite scroll umjesto paginacije

### 4. Performance
- Reduce bundle size
- Code splitting po rutama
- Lazy load komponente

## Implementacija po Prioritetu

### Faza 1 (Kritično)
1. ✅ useDevice composable
2. Dashboard mobilni prikaz
3. Login/Auth stranice
4. Sidebar → Mobile navigation

### Faza 2 (Važno)
5. Payments mobilni prikaz
6. Suppliers mobilni prikaz
7. Plans mobilni prikaz
8. Settings mobilni prikaz

### Faza 3 (Nice to have)
9. Touch gestures
10. Bottom sheets
11. Pull to refresh
12. Offline mode

## Testiranje

### Devices za testiranje:
- iPhone SE (375px)
- iPhone 12/13 (390px)
- iPhone 14 Pro Max (430px)
- iPad (768px)
- iPad Pro (1024px)

### Chrome DevTools
1. F12 → Toggle device toolbar
2. Testiraj sve breakpoints
3. Testiraj landscape/portrait
4. Testiraj touch events

## Primjer: Dashboard Mobile

```vue
<template>
  <MainLayout>
    <!-- Mobile Header -->
    <div class="lg:hidden sticky top-0 bg-white border-b px-4 py-3">
      <h1 class="text-lg font-semibold">{{ __('payments_overview') }}</h1>
    </div>

    <!-- Desktop Header -->
    <Header :title="__('payments_overview')" class="hidden lg:block" />

    <div class="p-4 lg:p-6 space-y-4 lg:space-y-6">
      <!-- Stats - Responsive Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <StatCard v-for="stat in stats" :key="stat.title" v-bind="stat" />
      </div>

      <!-- Filters - Mobile: Drawer, Desktop: Inline -->
      <div v-if="isMobile">
        <button @click="showFilters = true" class="btn-primary w-full">
          <FunnelIcon class="h-5 w-5" />
          Filteri
        </button>
      </div>
      <div v-else class="filters-row">
        <!-- Desktop filters -->
      </div>

      <!-- Table/Cards -->
      <div class="hidden lg:block">
        <PaymentTable :payments="payments" />
      </div>
      
      <div class="lg:hidden space-y-2">
        <PaymentCard 
          v-for="payment in payments" 
          :key="payment.id"
          :payment="payment"
          @click="openDetails(payment)"
        />
      </div>
    </div>

    <!-- Mobile FAB -->
    <FloatingActionButton v-if="isMobile" @click="openNewPayment">
      <PlusIcon class="h-6 w-6" />
    </FloatingActionButton>
  </MainLayout>
</template>

<script setup>
import { useDevice } from '@/composables/useDevice';

const { isMobile, isDesktop } = useDevice();
</script>
```

## CSS Utilities za Mobile

```css
/* Dodaj u app.css */

/* Hide scrollbar on mobile */
@media (max-width: 768px) {
  .hide-scrollbar::-webkit-scrollbar {
    display: none;
  }
  .hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
  }
}

/* Safe area for iOS notch */
.safe-area-top {
  padding-top: env(safe-area-inset-top);
}

.safe-area-bottom {
  padding-bottom: env(safe-area-inset-bottom);
}

/* Touch-friendly spacing */
.touch-target {
  min-height: 44px;
  min-width: 44px;
}

/* Mobile card */
.mobile-card {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 p-4;
}

/* Mobile list item */
.mobile-list-item {
  @apply flex items-center justify-between py-3 border-b border-gray-100 last:border-0;
}
```

## Sljedeći Koraci

1. Implementiraj useDevice u sve stranice
2. Kreiraj mobilne komponente (MobileCard, BottomSheet, FAB)
3. Ažuriraj Dashboard za mobilni prikaz
4. Ažuriraj ostale stranice po prioritetu
5. Testiraj na pravim uređajima
6. Optimizuj performance

## Napomene

- Koristi `md:` prefix za tablet/desktop
- Koristi `lg:` prefix za desktop only
- Mobile-first pristup (bez prefixa = mobile)
- Testiraj na pravim uređajima, ne samo u browseru
