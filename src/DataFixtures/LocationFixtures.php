<?php

namespace App\DataFixtures;

use App\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LocationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Mapping your existing cities to their coordinates
        $data = [
            'Bayawan'  => ['lat' => '9.36000000',  'long' => '122.80000000'],
            'Bagacay'  => ['lat' => '9.30000000',  'long' => '123.30000000'],
            'Daro'     => ['lat' => '9.31670000',  'long' => '123.30000000'],
            'Sibulan'  => ['lat' => '9.36210000',  'long' => '123.28420000'],
            'Bacolod'  => ['lat' => '10.67650000', 'long' => '122.95090000'],
            'Sipalay'  => ['lat' => '9.75120000',  'long' => '122.46490000'],
            'Pamplona' => ['lat' => '9.47000000',  'long' => '123.12000000'],
        ];

        $repository = $manager->getRepository(Location::class);

        foreach ($data as $name => $coords) {
            // Find existing record by name
            $location = $repository->findOneBy(['name' => $name]);

            if ($location) {
                $location->setLatitude($coords['lat']);
                $location->setLongitude($coords['long']);
            } else {
                // If it doesn't exist, create it (optional)
                $location = new Location();
                $location->setName($name);
                $location->setLatitude($coords['lat']);
                $location->setLongitude($coords['long']);
                $manager->persist($location);
            }
        }

        $manager->flush();
    }
}