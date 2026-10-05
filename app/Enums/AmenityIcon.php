<?php

namespace App\Enums;

/**
 * Lucide icon names an admin may assign to an amenity. The frontend maps
 * each value to its component in resources/js/lib/amenity-icons.ts, so the
 * two lists must stay in sync.
 */
enum AmenityIcon: string
{
    case Wifi = 'Wifi';
    case Waves = 'Waves';
    case CircleParking = 'CircleParking';
    case Coffee = 'Coffee';
    case Croissant = 'Croissant';
    case Utensils = 'Utensils';
    case UtensilsCrossed = 'UtensilsCrossed';
    case Wine = 'Wine';
    case Martini = 'Martini';
    case Beer = 'Beer';
    case Dumbbell = 'Dumbbell';
    case Sparkles = 'Sparkles';
    case Bath = 'Bath';
    case ShowerHead = 'ShowerHead';
    case Snowflake = 'Snowflake';
    case Heater = 'Heater';
    case Fan = 'Fan';
    case PawPrint = 'PawPrint';
    case Accessibility = 'Accessibility';
    case Umbrella = 'Umbrella';
    case Sun = 'Sun';
    case TreePalm = 'TreePalm';
    case Trees = 'Trees';
    case Flower2 = 'Flower2';
    case Mountain = 'Mountain';
    case Sailboat = 'Sailboat';
    case Fish = 'Fish';
    case Bike = 'Bike';
    case Car = 'Car';
    case Bus = 'Bus';
    case Plane = 'Plane';
    case ConciergeBell = 'ConciergeBell';
    case BedDouble = 'BedDouble';
    case Baby = 'Baby';
    case Tv = 'Tv';
    case WashingMachine = 'WashingMachine';
    case Refrigerator = 'Refrigerator';
    case Vault = 'Vault';
    case Briefcase = 'Briefcase';
    case Laptop = 'Laptop';
    case Gamepad2 = 'Gamepad2';
    case Music = 'Music';
    case CigaretteOff = 'CigaretteOff';
    case Clock = 'Clock';
    case Landmark = 'Landmark';
}
