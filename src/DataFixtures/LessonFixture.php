<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LessonFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $courses = [
            'Grafting' => [
                ['title' => 'Module 1: Introduction to Grafting', 'file' => 'Module_01_grafting-69d29bfd527740.58786326.pdf'],
                ['title' => 'Module 2: Grafting Tools and Supplies', 'file' => 'Module_02_grafting-69d29c7d8b65e6.37113040.pdf'],
                ['title' => 'Module 3: Grafting Techniques', 'file' => 'Module_03_grafting-69d29d701b5624.69527016.pdf'],
                ['title' => 'Module 4: Execution of Grafting', 'file' => 'Module_04_grafting-69d29dc3be6453.95642292.pdf'],
                ['title' => 'Module 5: Post-Grafting Care', 'file' => 'Module_5_grafting-69d29e0411e629.86932956.pdf'],
            ],
            'Marcotting' => [
                ['title' => 'Module 1: Introduction to Air Layering (Marcoting)', 'file' => 'Module_01_marcotting-69d29ee88315e8.30524763.pdf'],
                ['title' => 'Module 2: Planning and Preparation', 'file' => 'Module_02_marcotting-69d29f15ddc2c7.99020447.pdf'],
                ['title' => 'Module 3: Execution', 'file' => 'Module_3_marcotting-69d29f9204ac39.61123698.pdf'],
                ['title' => 'Module 4: Care and Maintenance', 'file' => 'Module_04_marcotting-69d29fd21f8eb6.01419055.pdf'],
                ['title' => 'Module 5: Harvesting and Transplanting', 'file' => 'Module_05_marcotting-69d2a0034b8860.53531098.pdf'],
            ],
            'Pruning' => [
                ['title' => 'Module 1: Introduction to Pruning', 'file' => 'Module_01_pruning-69d2a09c2d39e4.88583484.pdf'],
                ['title' => 'Module 2: Pruning Tools and Safety', 'file' => 'Module_02_pruning-69d2a0e0d64333.84652936.pdf'],
                ['title' => 'Module 3: Pruning Techniques', 'file' => 'Module_03_pruning-69d2a11e908cf0.63610679.pdf'],
                ['title' => 'Module 4: Fruit Tree Pruning', 'file' => 'Module_04_pruning-69d2a15c0c8659.35948407.pdf'],
                ['title' => 'Module 5: Maintenance Pruning', 'file' => 'Module_05_pruning-69d2a18c6097d3.37618534.pdf'],
            ],
        ];

        foreach ($courses as $courseName => $lessonsData) {
            $course = $manager->getRepository(Course::class)->findOneBy(['courseName' => $courseName]);
            
            if (!$course) {
                continue;
            }

            foreach ($lessonsData as $data) {
                $lesson = $manager->getRepository(Lesson::class)->findOneBy([
                    'title' => $data['title'],
                    'course' => $course,
                ]) ?? new Lesson();

                $lesson->setTitle($data['title']);
                $lesson->setCourse($course);
                $lesson->setContentFile($data['file']);
                // Content can be empty as we are using PDF files
                $lesson->setContent(null);

                $manager->persist($lesson);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CourseFixtures::class,
            GraftingFixtures::class,
            MarcotFixtures::class,
            PruningFixtures::class,
        ];
    }
}
