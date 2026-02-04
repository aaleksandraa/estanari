import { ref, onMounted, onUnmounted } from 'vue';

export function useDevice() {
    const isMobile = ref(false);
    const isTablet = ref(false);
    const isDesktop = ref(false);
    const screenWidth = ref(window.innerWidth);

    const updateDevice = () => {
        screenWidth.value = window.innerWidth;
        isMobile.value = screenWidth.value < 768;
        isTablet.value = screenWidth.value >= 768 && screenWidth.value < 1024;
        isDesktop.value = screenWidth.value >= 1024;
    };

    onMounted(() => {
        updateDevice();
        window.addEventListener('resize', updateDevice);
    });

    onUnmounted(() => {
        window.removeEventListener('resize', updateDevice);
    });

    return {
        isMobile,
        isTablet,
        isDesktop,
        screenWidth,
    };
}
