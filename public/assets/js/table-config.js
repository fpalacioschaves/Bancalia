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
                perPage: "registros por página", // Removed {select} if it's causing literal output
                noRows: "No se encontraron registros",
                info: "Mostrando {start} a {end} de {rows} registros",
                noResults: "No hay resultados coincidentes",
            },
            // Try to force the select visibility/style
            layout: {
                top: "{select}{search}",
                bottom: "{info}{pager}"
            }
        };

        const finalOptions = { ...defaultOptions, ...options };
        const dt = new window.simpleDatatables.DataTable(el, finalOptions);

        // Fix for the label issue: simple-datatables sometimes doesn't replace {select}
        // correctly if the label is modified. We'll wait a bit and fix the DOM if needed.
        dt.on("datatable.init", () => {
            const label = document.querySelector(".datatable-dropdown label");
            if (label && label.innerHTML.includes("{select}")) {
                // If it's still there, it's a bug in the library's label replacement
                // but usually the select is already there. Let's just clean up the text.
                label.innerHTML = label.innerHTML.replace("{select}", "");
            }
        });

        return dt;
    }
}

// Global availability
window.TableManager = TableManager;
