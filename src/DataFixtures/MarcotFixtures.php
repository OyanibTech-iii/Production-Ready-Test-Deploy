<?php

namespace App\DataFixtures;

use App\Entity\AnswerChoice;
use App\Entity\Course;
use App\Entity\Question;
use App\Entity\Quiz;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MarcotFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $coursesBySqlId = [];

        foreach ($this->getCourseRows() as $row) {
            $course = $manager->getRepository(Course::class)->findOneBy(['courseName' => $row['courseName']]) ?? new Course();
            $course->setCourseName($row['courseName']);
            $course->setDescription($row['description']);
            $course->setTier($row['tier']);
            $course->setPrice($row['price']);
            $course->setThumbnail('/uploads/courses/' . $row['thumbnail']);

            $manager->persist($course);
            $coursesBySqlId[$row['sqlId']] = $course;
        }

        $quizzesBySqlId = [];

        foreach ($this->getQuizRows() as $row) {
            if (!isset($coursesBySqlId[$row['courseSqlId']])) {
                continue;
            }

            $quiz = $manager->getRepository(Quiz::class)->findOneBy(['title' => $row['title']]) ?? new Quiz();
            $quiz->setCourse($coursesBySqlId[$row['courseSqlId']]);
            $quiz->setTitle($row['title']);
            $quiz->setPassingScore($row['passingScore']);

            $manager->persist($quiz);
            $quizzesBySqlId[$row['sqlId']] = $quiz;
        }

        foreach ($this->getQuestionRows() as $row) {
            if (!isset($quizzesBySqlId[$row['quizSqlId']])) {
                continue;
            }

            $question = $manager->getRepository(Question::class)->findOneBy([
                'quiz' => $quizzesBySqlId[$row['quizSqlId']],
                'questionText' => $row['questionText'],
            ]) ?? new Question();

            $question->setQuiz($quizzesBySqlId[$row['quizSqlId']]);
            $question->setQuestionText($row['questionText']);
            $question->setPoints(3);
            $manager->persist($question);

            $this->syncAnswerChoices(
                $manager,
                $question,
                $row['choices'],
                $row['correctChoiceIndex']
            );
        }

        $manager->flush();
    }

    /**
     * @return array<int, array{sqlId:int, courseName:string, description:string, tier:string, price:string, thumbnail:?string}>
     */
    private function getCourseRows(): array
    {
        return [
            [
                'sqlId' => 1,
                'courseName' => 'Marcotting',
                'description' => 'Air layering technique',
                'tier' => 'Free',
                'price' => '0.00',
                'thumbnail' => 'marcot-69cb61b6ef1ee.png',
            ],
        ];
    }

    /**
     * @return array<int, array{sqlId:int, courseSqlId:int, title:string, passingScore:int}>
     */
    private function getQuizRows(): array
    {
        return [
            ['sqlId' => 1, 'courseSqlId' => 1, 'title' => 'Air Layering 101', 'passingScore' => 75],
        ];
    }

    /**
     * @return array<int, array{quizSqlId:int, questionText:string, choices:array<int, string>, correctChoiceIndex:int}>
     */
    private function getQuestionRows(): array
    {
        return $this->getAirLayeringQuestionRows();
    }

    /**
     * @return array<int, array{quizSqlId:int, questionText:string, choices:array<int, string>, correctChoiceIndex:int}>
     */
    private function getAirLayeringQuestionRows(): array
    {
        $q = 1;

        return [
            [
                'quizSqlId' => $q,
                'questionText' => 'What is air layering (marcoting)?',
                'choices' => [
                    'Planting seeds in soil',
                    'Rooting a branch while still attached to the mother plant',
                    'Cutting roots from the ground',
                    'Grafting two plants together',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'The main advantage of air layering is:',
                'choices' => [
                    'Slower growth',
                    'Produces genetically identical plants',
                    'Requires no water',
                    'Only works on flowers',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Air layering is commonly used for:',
                'choices' => [
                    'Rice plants',
                    'Moss plants',
                    'Woody plants and fruit trees',
                    'Aquatic plants',
                ],
                'correctChoiceIndex' => 2,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Which is NOT a benefit of air layering?',
                'choices' => [
                    'High success rate',
                    'Faster propagation',
                    'Produces clones',
                    'Requires no maintenance',
                ],
                'correctChoiceIndex' => 3,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Air layering is also called:',
                'choices' => [
                    'Germination',
                    'Pollination',
                    'Marcoting',
                    'Composting',
                ],
                'correctChoiceIndex' => 2,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'What tool is used to remove bark in air layering?',
                'choices' => [
                    'Hammer',
                    'Knife or pruning shears',
                    'Shovel',
                    'Rake',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Which is a recommended rooting medium?',
                'choices' => [
                    'Sand only',
                    'Coconut coir or sphagnum moss',
                    'Plastic only',
                    'Stones',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'What is used to wrap the rooting medium?',
                'choices' => [
                    'Paper',
                    'Plastic wrap',
                    'Cloth',
                    'Wood',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Rooting hormone is used to:',
                'choices' => [
                    'Kill pests',
                    'Speed up root formation',
                    'Change plant color',
                    'Stop plant growth',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Which is optional but useful in air layering?',
                'choices' => [
                    'Aluminum foil',
                    'Paint',
                    'Cement',
                    'Glue',
                ],
                'correctChoiceIndex' => 0,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'What branch diameter is ideal for marcoting?',
                'choices' => [
                    '5–10 cm',
                    '1–2 cm',
                    '10–20 cm',
                    'Very thin twigs only',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Girdling means:',
                'choices' => [
                    'Watering the branch',
                    'Removing a ring of bark',
                    'Cutting the branch completely',
                    'Painting the branch',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Why scrape the cambium layer?',
                'choices' => [
                    'To kill the branch',
                    'To prevent bark from healing over',
                    'To add fertilizer',
                    'To attract insects',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'After adding rooting medium, what is the next step?',
                'choices' => [
                    'Cut the branch',
                    'Wrap with plastic',
                    'Burn the branch',
                    'Leave it open to dry',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Roots usually develop within:',
                'choices' => [
                    '1 day',
                    '1 week',
                    '2–8 weeks',
                    '1 year',
                ],
                'correctChoiceIndex' => 2,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'When should you cut the air layered branch?',
                'choices' => [
                    'Immediately after wrapping',
                    'When roots are well developed',
                    'After one day',
                    'Before roots form',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'After cutting the layered branch, you should:',
                'choices' => [
                    'Throw away the roots',
                    'Remove the plastic carefully',
                    'Dry the roots in sun',
                    'Burn the branch',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Which part of the plant transports sugars and hormones downward?',
                'choices' => [
                    'Xylem',
                    'Phloem',
                    'Pith',
                    'Epidermis',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Girdling blocks the downward movement of:',
                'choices' => [
                    'Water and minerals',
                    'Carbohydrates and auxins',
                    'Oxygen',
                    'Soil nutrients',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Which fruit tree is mentioned as suitable for air layering?',
                'choices' => [
                    'Mango',
                    'Guava',
                    'Calamansi',
                    'All of the above',
                ],
                'correctChoiceIndex' => 3,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'What is the purpose of using sphagnum moss in air layering?',
                'choices' => [
                    'To provide structural support',
                    'To retain moisture for root growth',
                    'To kill bacteria',
                    'To repel insects',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'When selecting a branch for air layering, you should choose one that is:',
                'choices' => [
                    'Heavily diseased',
                    'Pencil-thick and healthy',
                    'Completely dead',
                    'Growing underground',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'The girdled area should typically be how long?',
                'choices' => [
                    '1 mm',
                    '2–3 cm',
                    '10–15 cm',
                    '1 meter',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'What is the primary sign that roots are ready for harvesting?',
                'choices' => [
                    'The plastic turns black',
                    'Visible brown or white roots through the plastic',
                    'The branch falls off on its own',
                    'The leaves turn yellow',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Before transplanting, what should be done to the new plant?',
                'choices' => [
                    'Remove all leaves',
                    'Prune some leaves to reduce transpiration',
                    'Place in direct hot sun immediately',
                    'Wash away all the rooting medium',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Why is it important to keep the rooting medium moist?',
                'choices' => [
                    'To prevent the branch from breaking',
                    'Because dry medium will stop root development',
                    'To attract more pests',
                    'To make the branch heavier',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'A common mistake in air layering is:',
                'choices' => [
                    'Using too much rooting hormone',
                    'Not removing the cambium layer completely',
                    'Using a sharp knife',
                    'Selecting a healthy branch',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'What is the ideal time of year for air layering in many regions?',
                'choices' => [
                    'Mid-winter freeze',
                    'Active growing season (spring or rainy season)',
                    'Peak summer drought',
                    'Whenever the plant is dormant',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'What happens if you don\'t scrape the cambium after girdling?',
                'choices' => [
                    'The plant grows faster',
                    'The bark may heal over and prevent root formation',
                    'The branch will instantly die',
                    'Roots will grow faster',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Which of these is a vegetative propagation method?',
                'choices' => [
                    'Seeds',
                    'Air layering',
                    'Pollination',
                    'Fertilization',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'What does "adventitious roots" mean in the context of air layering?',
                'choices' => [
                    'Roots that grow from the bottom of the tree',
                    'Roots that grow from an unusual place like a stem',
                    'Roots that never grow',
                    'Roots that grow only from seeds',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Auxins are often used in air layering to:',
                'choices' => [
                    'Color the stem',
                    'Promote root initiation',
                    'Kill fungi',
                    'Increase fruit size',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Aluminum foil is sometimes used over the plastic wrap to:',
                'choices' => [
                    'Keep the roots cold',
                    'Protect roots from light and heat',
                    'Attract birds',
                    'Strengthen the branch',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'The parent plant in air layering is also called the:',
                'choices' => [
                    'Clone',
                    'Mother plant',
                    'Scion',
                    'Stock',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Which propagation method creates a genetic clone?',
                'choices' => [
                    'Sexual reproduction',
                    'Asexual reproduction (like air layering)',
                    'Seed planting',
                    'Hybridization',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'What is the primary risk of using unsterilized tools?',
                'choices' => [
                    'The tools will become too sharp',
                    'Spreading diseases between plants',
                    'The tools will change color',
                    'No risk involved',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'What should you do if the rooting medium dries out before roots form?',
                'choices' => [
                    'Abandon the layer',
                    'Carefully inject water or re-moisten it',
                    'Cut the branch immediately',
                    'Do nothing and wait',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Transpiration is the process of:',
                'choices' => [
                    'Root growth',
                    'Water loss from leaves',
                    'Nutrient absorption',
                    'Fruit ripening',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'When transplanting, the new plant should initially be kept in:',
                'choices' => [
                    'Direct scorching sunlight',
                    'A shaded, humid environment',
                    'A dark closet',
                    'Standing water',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Sustainability in propagation means:',
                'choices' => [
                    'Over-harvesting mother plants',
                    'Using ethical practices to ensure plant health for the future',
                    'Using only chemical fertilizers',
                    'Ignoring record keeping',
                ],
                'correctChoiceIndex' => 1,
            ],
        ];
    }

    /**
     * @param array<int, string> $choices
     */
    private function syncAnswerChoices(
        ObjectManager $manager,
        Question $question,
        array $choices,
        int $correctChoiceIndex
    ): void {
        $maxChoices = array_values(array_slice($choices, 0, 4));
        if ($maxChoices === []) {
            return;
        }

        foreach ($question->getAnswerChoices() as $existingChoice) {
            $manager->remove($existingChoice);
        }

        foreach ($maxChoices as $index => $choiceText) {
            $choice = new AnswerChoice();
            $choice->setQuestion($question);
            $choice->setChoiceText($choiceText);
            $choice->setIsCorrect($index === $correctChoiceIndex);
            $manager->persist($choice);
        }
    }
}
