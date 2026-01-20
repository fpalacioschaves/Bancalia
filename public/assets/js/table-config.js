/**
 * Bancalia Table Config
 * Centralizes the configuration for simple-datatables with Spanish localization
 * and Tailwind-compatible styling.
 */

class TableManager {
    static init(selector, options = {}) {
        const el = document.querySelector(selector);
        if (!el) return null;

        const defaultOptions = {
            searchable: true,
            fixedHeight: false,
            perPage: 10,
            perPageSelect: [10, 25, 50, 100],
            labels: {
                placeholder: "Buscar...",
                perPage: "{select} registros por página",
                noRows: "No se encontraron registros",
                info: "Mostrando {start} a {end} de {rows} registros",
                noResults: "No hay resultados que coincidan con tu búsqueda",
            },
            // Customizing classes for Tailwind compatibility if needed
            // simple-datatables has its own CSS, but we can nudge it
        };

        const finalOptions = { ...defaultOptions, ...options };
        return new window.simpleDatatables.DataTable(el, finalOptions);
    }
}

// Global availability
window.TableManager = TableManager;
