(() => {
    'use strict';
    function normalize(value) {
        if (typeof value !== 'string' || /[<>\p{Cc}]/u.test(value)) {
            throw new Error('Use um nome sem marcação ou caracteres de controle.');
        }
        const name = value.trim().replace(/\s+/gu, ' ');
        if (!name) throw new Error('Informe o nome da categoria.');
        if ([...name].length > 80) throw new Error('Use até 80 caracteres no nome da categoria.');
        return name;
    }
    function isOther(value) {
        return typeof value === 'string' && /^outros?$/iu.test(value);
    }
    function resolve(selected, custom) {
        return isOther(selected) ? normalize(custom) : selected;
    }
    function options(defaults, records = [], current = '') {
        defaults = defaults.some(isOther) ? defaults : [...defaults, 'Outro'];
        const result = new Map();
        for (const value of [...defaults, ...records.map(record => record.categoria), current]) {
            try {
                const name = normalize(value);
                const key = name.toLocaleLowerCase('pt-BR');
                if (!result.has(key)) result.set(key, name);
            } catch (_) { /* Uma ficha sem categoria não acrescenta opção. */ }
        }
        return [...result.values()];
    }
    const api = { normalize, options, isOther, resolve };
    if (typeof module !== 'undefined') module.exports = api;
    if (typeof window !== 'undefined') window.NookWorldCategories = api;
})();
