function toCamel(str) {
    return str
        .toLowerCase()
        .split('_')
        .map((part, i) => (i === 0 ? part : part.charAt(0).toUpperCase() + part.slice(1)))
        .join('')
}

export function mapKeysToCamelCase(input) {
    if (input == null) return input
    if (Array.isArray(input)) return input.map(mapKeysToCamelCase)
    if (typeof input !== 'object') return input

    const out = {}
    for (const key of Object.keys(input)) {
        const value = input[key]
        const newKey = toCamel(key)
        out[newKey] = mapKeysToCamelCase(value)
    }
    return out
}
