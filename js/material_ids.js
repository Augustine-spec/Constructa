// Script to add product IDs to all material cards
// This is a helper script to generate the updates needed

const materials = [
    { id: 5, name: "Red Clay Bricks", price: 12, unit: "per piece" },
    { id: 6, name: "Solid Concrete Blocks", price: 38, unit: "per block" },
    { id: 7, name: "Polycarbonate Sheet", price: 85, unit: "per sq.ft" },
    { id: 8, name: "Waterproof Chemical", price: 4200, unit: "per bucket" },
    { id: 9, name: "Vitrified Tiles (2x2)", price: 55, unit: "per sq.ft" },
    { id: 10, name: "Granite Slab", price: 140, unit: "per sq.ft" },
    { id: 11, name: "Interior Emulsion", price: 3800, unit: "per bucket" },
    { id: 12, name: "Teak Wood Door", price: 25000, unit: "per unit" },
    { id: 13, name: "Copper Wire (2.5 sqmm)", price: 1850, unit: "per coil" },
    { id: 14, name: "PVC Pipe (4 inch)", price: 450, unit: "per length" },
    { id: 15, name: "SS Kitchen Sink", price: 3200, unit: "per unit" },
    { id: 16, name: "Interlocking Pavers", price: 42, unit: "per sq.ft" }
];

console.log("Material IDs for reference:");
materials.forEach(m => {
    console.log(`${m.id}: ${m.name} - ₹${m.price} ${m.unit}`);
});
