# iOS i Safari Kompatibilnost - WizFlussi

## Pregled

Projekat je ažuriran za potpunu kompatibilnost sa iOS uređajima (iPhone, iPad) i Safari browserom, uključujući starije verzije.

## Podržane Verzije

### iOS / Safari
- **iOS 12+** (iPhone 5s i noviji)
- **Safari 12+**
- **iPadOS 12+**

### Ostali Browseri
- Chrome 80+
- Firefox 78+
- Edge 88+

## Implementirane Izmjene

### 1. JavaScript Transpilacija
- **Babel** konfiguracija za transpilaciju modernih JS funkcija
- **Core-js** polyfills za starije browsere
- Transpilacija optional chaining (`?.`) i drugih ES2020+ funkcija

### 2. Build Konfiguracija
- Vite build target: `es2015` i `safari12`
- Browserslist konfiguracija za precizno targetiranje
- CSS target za Safari 12+

### 3. iOS Specifični Fixevi

#### Meta Tagovi
```html
- viewport sa maximum-scale=5 (sprečava zoom probleme)
- apple-mobile-web-app-capable (PWA podrška)
- format-detection (kontrola automatskog linkovanja)
- text-size-adjust (sprečava automatsko skaliranje teksta)
```

#### CSS Fixevi
- Input font-size 16px (sprečava automatski zoom na iOS)
- -webkit-sticky za sticky positioning
- -webkit-fill-available za 100vh fix
- -webkit-tap-highlight-color za tap highlight
- -webkit-appearance za button styling
- -webkit-overflow-scrolling za smooth scrolling

### 4. Poznati iOS Safari Problemi i Rješenja

#### Problem: Input Zoom
**Rješenje:** Svi input elementi imaju minimum font-size od 16px

#### Problem: 100vh na iOS
**Rješenje:** Koristi se -webkit-fill-available za min-height

#### Problem: Sticky Positioning
**Rješenje:** Dodato -webkit-sticky uz position: sticky

#### Problem: Date Input
**Rješenje:** Custom DateInput komponenta sa iOS kompatibilnim kalendarom

## Instalacija i Build

### 1. Instaliraj Dependencies
```bash
cd backend
npm install
```

### 2. Development Build
```bash
npm run dev
```

### 3. Production Build
```bash
npm run build
```

## Testiranje na iOS

### Safari Desktop (macOS)
1. Otvori Safari
2. Developer > User Agent > Safari — iOS 12.x
3. Testiraj funkcionalnost

### iOS Simulator (macOS)
1. Otvori Xcode
2. Open Developer Tool > Simulator
3. Odaberi iOS verziju (12+)
4. Otvori Safari i testiraj

### Pravi iOS Uređaj
1. Povežite iPhone/iPad na istu mrežu
2. Pristupite aplikaciji preko IP adrese
3. Testirajte sve funkcionalnosti

## Checklist za iOS Testiranje

- [ ] Login/Logout
- [ ] Dashboard prikaz
- [ ] Dodavanje novog plaćanja
- [ ] Editovanje plaćanja
- [ ] Date picker funkcionalnost
- [ ] Autocomplete dropdown-ovi
- [ ] Modal dialozi
- [ ] Export funkcionalnosti
- [ ] Responsive layout na različitim veličinama
- [ ] Touch gestures (swipe, tap, long press)
- [ ] Scroll performance
- [ ] Input fokus i keyboard

## Performanse

### Optimizacije
- Code splitting
- Lazy loading komponenti
- Optimizovani bundle size
- CSS minifikacija
- Tree shaking

### Preporučene Prakse
- Testirajte na pravim uređajima, ne samo simulatorima
- Koristite Safari Web Inspector za debugging
- Provjerite Network tab za performance
- Testirajte na sporijoj mreži (3G)

## Troubleshooting

### Problem: Aplikacija ne radi na iOS 12
**Rješenje:** 
1. Provjerite da li je build napravljen sa `npm run build`
2. Očistite cache: `php artisan cache:clear`
3. Rebuild assets: `npm run build`

### Problem: Input zoom na iOS
**Rješenje:** Provjerite da li su CSS fixevi primijenjeni (font-size 16px)

### Problem: Date picker ne radi
**Rješenje:** Custom DateInput komponenta bi trebala raditi - provjerite console za greške

### Problem: Dropdown ne radi
**Rješenje:** Autocomplete komponenta koristi Teleport - provjerite z-index

## Dodatne Napomene

- **PWA Podrška:** Aplikacija može biti instalirana kao PWA na iOS
- **Offline Mode:** Trenutno nije implementiran, ali može se dodati
- **Push Notifications:** iOS Safari ne podržava Web Push (potreban je native app)
- **Touch Events:** Svi touch eventi su optimizovani za iOS

## Kontakt

Za dodatna pitanja ili probleme, kontaktirajte development tim.
