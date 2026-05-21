<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $courses = [
            [
                'name' => 'Grafting',
                'description' => 'Harvest within a short period of time',
                'tier' => 'Free',
                'price' => '0.00',
                'thumbnail' => 'grafting_tumbnail-69cb5fc8225fa.png',
            ],
            [
                'name' => 'Marcotting',
                'description' => 'Air layering technique',
                'tier' => 'Free',
                'price' => '0.00',
                'thumbnail' => 'marcot-69cb61b6ef1ee.png',
            ],
            [
                'name' => 'Pruning',
                'description' => 'Remove not useful twigs and branches',
                'tier' => 'Free',
                'price' => '0.00',
                'thumbnail' => 'Prunning.png',
            ],
        ];

        foreach ($courses as $courseData) {
            $course = $manager->getRepository(Course::class)->findOneBy(['courseName' => $courseData['name']]) ?? new Course();
            
            $course->setCourseName($courseData['name']);
            $course->setDescription($courseData['description']);
            $course->setTier($courseData['tier']);
            $course->setPrice($courseData['price']);
            $course->setThumbnail('/uploads/courses/' . $courseData['thumbnail']);

            $manager->persist($course);
        }

        $manager->flush();
    }
}
