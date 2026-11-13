const root = document.documentElement;
const toggle = document.getElementById('themeToggle');

const currentTheme = () => root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';

const applyTheme = (theme) => {
    root.setAttribute('data-bs-theme', theme);
    if (toggle) toggle.checked = theme === 'dark';
};

// Show the saved state on the switch
applyTheme(currentTheme());

toggle?.addEventListener('change', () => {
    const theme = toggle.checked ? 'dark' : 'light';
    applyTheme(theme);
    try {
        localStorage.setItem('theme', theme);
    } catch {
        // Storage blocked: the theme still works for this page view
    }
});

// Keep other open tabs in sync
window.addEventListener('storage', (event) => {
    if (event.key === 'theme' && event.newValue) applyTheme(event.newValue);
});