const plans = [
    {
        id: 1, title: 'Modern Compact Villa', area: '1200', baseCost: 2500000, floors: 2, beds: 3, style: 'Modern',
        planImage: 'uploads/plans/modern_villa.png',
        builtImage: 'uploads/plans/modern_villa.png',
        reasoning: ['Optimal for 30x40 Plot', 'High Rental Yield', 'Vastu Neutral'],
        gallery: [
            'uploads/plans/modern_villa.png',
            'uploads/plans/modern_villa_side.png',
            'uploads/plans/modern_villa_living.png',
            'uploads/plans/modern_villa_bedroom.png',
            'uploads/plans/modern_villa_kitchen.png'
        ]
    },
    {
        id: 2, title: 'Traditional Courtyard Home', area: '1500', baseCost: 3200000, floors: 1, beds: 3, style: 'Traditional',
        planImage: 'uploads/plans/traditional_house.png',
        builtImage: 'uploads/plans/traditional_house.png',
        reasoning: ['Perfect for South Façade', 'Elderly Friendly', 'Eco-Materials'],
        gallery: [
            'uploads/plans/traditional_house.png',
            'uploads/plans/traditional_house_courtyard.png',
            'uploads/plans/traditional_house_hall.png',
            'uploads/plans/traditional_house_kitchen.png'
        ]
    },
    {
        id: 3, title: 'Urban Duplex', area: '1000', baseCost: 2800000, floors: 2, beds: 2, style: 'Modern',
        planImage: 'uploads/plans/urban_duplex.png',
        builtImage: 'uploads/plans/urban_duplex.png',
        reasoning: ['Compact City Design', 'Smart Storage', 'Low Maintenance'],
        gallery: [
            'uploads/plans/urban_duplex.png',
            'uploads/plans/urban_duplex_street.png',
            'uploads/plans/urban_duplex_living.png',
            'uploads/plans/urban_duplex_bedroom.png'
        ]
    },
    {
        id: 4, title: 'Vastu Compliant 3BHK', area: '1800', baseCost: 4000000, floors: 2, beds: 3, style: 'Vastu',
        planImage: 'uploads/plans/vastu_mansion.png',
        builtImage: 'uploads/plans/vastu_mansion.png',
        reasoning: ['100% Vastu Score', 'Max Natural Light', 'Resale Value High'],
        gallery: [
            'uploads/plans/vastu_mansion.png',
            'uploads/plans/vastu_mansion_rear.png',
            'uploads/plans/vastu_mansion_foyer.png',
            'uploads/plans/vastu_mansion_theater.png'
        ]
    },
    {
        id: 5, title: 'Modern Ground Villa', area: '1200', baseCost: 2000000, floors: 1, beds: 2, style: 'Modern',
        planImage: 'uploads/plans/modern_villa.png',
        builtImage: 'uploads/plans/modern_villa.png',
        reasoning: ['Single Floor Comfort', 'Open Plan Living', 'Budget Friendly'],
        gallery: [
            'uploads/plans/modern_villa.png',
            'uploads/plans/modern_villa_side.png',
            'uploads/plans/modern_villa_living.png',
            'uploads/plans/modern_villa_kitchen.png'
        ]
    },
    {
        id: 6, title: 'Grand Vastu Mansion', area: '2400', baseCost: 6000000, floors: 2, beds: 4, style: 'Vastu',
        planImage: 'uploads/plans/vastu_mansion.png',
        builtImage: 'uploads/plans/vastu_mansion.png',
        reasoning: ['Luxury Living', 'Large Garden Space', 'Premium Assessment'],
        gallery: [
            'uploads/plans/vastu_mansion.png',
            'uploads/plans/vastu_mansion_rear.png',
            'uploads/plans/vastu_mansion_foyer.png',
            'uploads/plans/vastu_mansion_theater.png'
        ]
    },
    {
        id: 7, title: 'Traditional G+2 Joint Family', area: '1500', baseCost: 5500000, floors: 3, beds: 5, style: 'Traditional',
        planImage: 'uploads/plans/traditional_house.png',
        builtImage: 'uploads/plans/traditional_house.png',
        reasoning: ['Multi-Gen Living', 'Separate Floor Units', 'Terrace Garden'],
        gallery: [
            'uploads/plans/traditional_house.png',
            'uploads/plans/traditional_house_courtyard.png',
            'uploads/plans/traditional_house_hall.png'
        ]
    },
    {
        id: 8, title: 'Traditional Starter Home', area: '1200', baseCost: 2200000, floors: 1, beds: 2, style: 'Traditional',
        planImage: 'uploads/plans/traditional_house.png',
        builtImage: 'uploads/plans/traditional_house.png',
        reasoning: ['Starter Choice', 'Porch Included', 'Low Cost'],
        gallery: [
            'uploads/plans/traditional_house.png',
            'uploads/plans/traditional_house_courtyard.png',
            'uploads/plans/traditional_house_kitchen.png'
        ]
    },
    {
        id: 9, title: 'Vastu Duplex 1200', area: '1200', baseCost: 2800000, floors: 2, beds: 3, style: 'Vastu',
        planImage: 'uploads/plans/vastu_mansion.png',
        builtImage: 'uploads/plans/vastu_mansion.png',
        reasoning: ['N-E Entrance', 'Double Height Hall', 'Pooja Room'],
        gallery: [
            'uploads/plans/vastu_mansion.png',
            'uploads/plans/vastu_mansion_foyer.png',
            'uploads/plans/vastu_mansion_theater.png'
        ]
    },
    {
        id: 10, title: 'Compact Modern G', area: '1000', baseCost: 1800000, floors: 1, beds: 2, style: 'Modern',
        planImage: 'uploads/plans/urban_duplex.png',
        builtImage: 'uploads/plans/urban_duplex.png',
        reasoning: ['Cube Design', 'Minimalist', 'Efficient'],
        gallery: [
            'uploads/plans/urban_duplex.png',
            'uploads/plans/urban_duplex_living.png',
            'uploads/plans/urban_duplex_bedroom.png'
        ]
    },
    {
        id: 11, title: 'Spacious Vastu Ground', area: '1500', baseCost: 2500000, floors: 1, beds: 3, style: 'Vastu',
        planImage: 'uploads/plans/vastu_mansion.png',
        builtImage: 'uploads/plans/vastu_mansion.png',
        reasoning: ['Brahmasthan Center', 'East Facet', 'Ventilated'],
        gallery: [
            'uploads/plans/vastu_mansion.png',
            'uploads/plans/vastu_mansion_rear.png',
            'uploads/plans/vastu_mansion_foyer.png'
        ]
    },
    {
        id: 12, title: 'Traditional Villa G+1', area: '1800', baseCost: 4500000, floors: 2, beds: 4, style: 'Traditional',
        planImage: 'uploads/plans/traditional_house.png',
        builtImage: 'uploads/plans/traditional_house.png',
        reasoning: ['Classic Aesthetic', 'Wood Finishes', 'Large Living'],
        gallery: [
            'uploads/plans/traditional_house.png',
            'uploads/plans/traditional_house_courtyard.png',
            'uploads/plans/traditional_house_hall.png'
        ]
    },
    {
        id: 13, title: 'Luxury Modern G+2', area: '2400', baseCost: 7500000, floors: 3, beds: 5, style: 'Modern',
        planImage: 'uploads/plans/urban_duplex.png',
        builtImage: 'uploads/plans/urban_duplex.png',
        reasoning: ['Penthouse Suite', 'Infinity Pool', 'Glass Walls'],
        gallery: [
            'uploads/plans/urban_duplex.png',
            'uploads/plans/urban_duplex_street.png',
            'uploads/plans/urban_duplex_living.png'
        ]
    }
];
