(function (window) {
    if (!window) {
        return;
    }

    window.tailwind = window.tailwind || {};
    window.tailwind.config = {
        corePlugins: { preflight: false },
        theme: {
            extend: {
                colors: {
                    brand: {
                        500: '#14b8a6',
                        600: '#0d9488',
                        700: '#0f766e'
                    }
                }
            }
        }
    };
})(window);
