<?php

namespace App\Enum;

enum Hobby: string
{
    case URBAN_GARDENING = 'urban_gardening';
    case HYDROPONICS = 'hydroponics';
    case COMPOSTING = 'composting';
    case ORGANIC_FARMING = 'organic_farming';
    case PLANT_PROPAGATION = 'plant_propagation';
    case SUSTAINABLE_LIVING = 'sustainable_living';
    case PERMACULTURE = 'permaculture';
    case BEEKEEPING = 'beekeeping';

    public const ALL = [
        self::URBAN_GARDENING,
        self::HYDROPONICS,
        self::COMPOSTING,
        self::ORGANIC_FARMING,
        self::PLANT_PROPAGATION,
        self::SUSTAINABLE_LIVING,
        self::PERMACULTURE,
        self::BEEKEEPING,
    ];

    public function label(): string
    {
        return match($this) {
            self::URBAN_GARDENING => 'Urban Gardening',
            self::HYDROPONICS => 'Hydroponics',
            self::COMPOSTING => 'Composting',
            self::ORGANIC_FARMING => 'Organic Farming',
            self::PLANT_PROPAGATION => 'Plant Propagation',
            self::SUSTAINABLE_LIVING => 'Sustainable Living',
            self::PERMACULTURE => 'Permaculture',
            self::BEEKEEPING => 'Beekeeping',
        };
    }
}
