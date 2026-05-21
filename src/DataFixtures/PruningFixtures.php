<?php

namespace App\DataFixtures;

use App\Entity\AnswerChoice;
use App\Entity\Course;
use App\Entity\Question;
use App\Entity\Quiz;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PruningFixtures extends Fixture
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
                'courseName' => 'Pruning',
                'description' => 'Remove not useful twigs and branches',
                'tier' => 'Free',
                'price' => '0.00',
                'thumbnail' => 'Prunning.png',
            ],
        ];
    }

    /**
     * @return array<int, array{sqlId:int, courseSqlId:int, title:string, passingScore:int}>
     */
    private function getQuizRows(): array
    {
        return [
            ['sqlId' => 1, 'courseSqlId' => 1, 'title' => 'Pruning Quiz', 'passingScore' => 75],
        ];
    }

    /**
     * @return array<int, array{quizSqlId:int, questionText:string, choices:array<int, string>, correctChoiceIndex:int}>
     */
    private function getQuestionRows(): array
    {
        return $this->getPruningQuestionRows();
    }

    /**
     * @return array<int, array{quizSqlId:int, questionText:string, choices:array<int, string>, correctChoiceIndex:int}>
     */
    private function getPruningQuestionRows(): array
    {
        $q = 1;

        return [
            [
                'quizSqlId' => $q,
                'questionText' => 'What is a primary goal of pruning fruit trees?',
                'choices' => [
                    'Stop all fruit production',
                    'Improve structure, light penetration, and fruit quality',
                    'Remove the bark each season',
                    'Keep the tree the same height forever without cuts',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Which tools are best for clean cuts on living branches?',
                'choices' => [
                    'Dull anvil pruners only',
                    'Sharp bypass pruners or a sharp pruning saw',
                    'Ordinary paper scissors',
                    'Tearing by hand for speed',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Why should crossed or rubbing branches be removed?',
                'choices' => [
                    'They always produce the sweetest fruit',
                    'They wound bark and invite disease and pests',
                    'They stop the roots from absorbing water',
                    'They are required for pollination',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'A conservative rule for how much live canopy to remove in one year is roughly:',
                'choices' => [
                    'Up to 80% of the crown if you are in a hurry',
                    'There is no safe limit',
                    'About one-quarter to one-third per year',
                    'Only one leaf per branch',
                ],
                'correctChoiceIndex' => 2,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Water sprouts (vigorous upright shoots in the canopy) are often removed because they:',
                'choices' => [
                    'Are always the future main trunk',
                    'Waste energy and crowd the tree without good fruiting positions',
                    'Cannot legally be pruned',
                    'Are the only branches that flower',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'When removing a branch at its attachment, the final cut should:',
                'choices' => [
                    'Be perfectly flush with the trunk, shaving the collar',
                    'Follow the branch collar—no stub, no flush cut into the collar',
                    'Always leave a 15 cm stub to “feed” the tree',
                    'Be sealed with thick paint the same day',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Sanitize pruning tools between trees especially when:',
                'choices' => [
                    'The weather is hot',
                    'Disease is suspected, or after cutting infected wood',
                    'The tools are brand new',
                    'You are only pruning small twigs',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Summer pruning is often used to:',
                'choices' => [
                    'Replace winter dormancy entirely',
                    'Check excessive vigor and improve light penetration on fast growers',
                    'Eliminate all need to irrigate',
                    'Guarantee no pests will visit the orchard',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Dead, diseased, or damaged branches should generally be:',
                'choices' => [
                    'Painted bright colors for visibility',
                    'Removed back to sound wood or to the branch collar',
                    'Left until they fall naturally every time',
                    'Weighted down with wire to strengthen them',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Suckers arising from below the graft union on a fruit tree should be:',
                'choices' => [
                    'Kept as the main crop wood',
                    'Removed at the base so the scion variety dominates',
                    'Grafted again on top of the scion',
                    'Allowed to fruit because they are always true-to-type',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Apical dominance means the highest buds tend to inhibit growth lower on the stem. Pruning the leader can:',
                'choices' => [
                    'Permanently stop all lower buds from ever growing',
                    'Release lateral buds so side branches develop for a spreading canopy',
                    'Only work on herbaceous annuals, not trees',
                    'Eliminate the need for any future pruning',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Routine tar-like wound dressings or sealants on fresh pruning cuts are:',
                'choices' => [
                    'Required on every species',
                    'Usually unnecessary; trees wall off injury by compartmentalization',
                    'More important than using sharp tools',
                    'The only way to stop decay in healthy wood',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'A thinning cut removes an entire branch back to its point of origin mainly to:',
                'choices' => [
                    'Stop photosynthesis completely',
                    'Open the canopy for light and air without a cluster of shoots at one stub',
                    'Force the trunk to grow thicker roots underground',
                    'Convert the branch into a second trunk automatically',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'A heading cut shortens a branch and typically:',
                'choices' => [
                    'Removes the branch at the collar in one step',
                    'Stimulates shoots near the cut; use when you want bushier regrowth',
                    'Is the same as girdling the trunk',
                    'Should only be done on dead wood',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'For shrubs that flower on old wood in early spring, the safest pruning time is often:',
                'choices' => [
                    'Just before buds open in late winter',
                    'Soon after bloom finishes',
                    'Only during harvest',
                    'Never—they cannot be shaped',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Heavy restorative pruning of a neglected fruit tree should usually be:',
                'choices' => [
                    'Done in one day regardless of tree size',
                    'Spread over several seasons to limit stress and sunburn on inner wood',
                    'Avoided; old trees cannot respond to pruning',
                    'Limited to removing roots only',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Basic safety when pruning includes:',
                'choices' => [
                    'Working from an unstable chair at full reach',
                    'Eye protection, stable footing, and planning how cut pieces will fall',
                    'Using the smallest possible gloves on power tools',
                    'Pruning during thunderstorms to save time',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'The three-cut method for removing a heavy limb is used to:',
                'choices' => [
                    'Make three separate trees from one limb',
                    'Prevent bark from stripping down the trunk when the branch falls',
                    'Apply three different paints to the wound',
                    'Triple the number of fruit buds',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Dormant-season pruning (before spring growth) is often preferred because:',
                'choices' => [
                    'Leaves block your view of branch structure and disease risk can be lower',
                    'Sap must never flow in deciduous trees',
                    'Tools work only below 10 °C',
                    'Flowers are easier to remove in winter',
                ],
                'correctChoiceIndex' => 0,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Why avoid leaving long stubs when pruning?',
                'choices' => [
                    'Stubs usually die back slowly and invite rot into adjacent sound wood',
                    'Stubs always grow into perfect fruiting branches',
                    'Stubs are required for cambium “healing paste”',
                    'Stubs increase root pressure',
                ],
                'correctChoiceIndex' => 0,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Codominant stems (two leaders of similar size with a tight V) are a concern because:',
                'choices' => [
                    'They always produce double fruit',
                    'Included bark in narrow unions can lead to splitting; often one stem is removed early',
                    'They prove the tree is self-pollinating',
                    'They mean the tree needs no thinning cuts',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Crown raising (lifting lower branches) is done to:',
                'choices' => [
                    'Stop the tree from making any leaves',
                    'Improve clearance for people, equipment, or understory light',
                    'Force all fruit to grow underground',
                    'Eliminate the need for a trunk',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Reduction cuts shorten a limb by cutting back to a lateral that can assume the terminal role. They differ from topping because:',
                'choices' => [
                    'They leave random stubs with no lateral',
                    'They preserve a live branch tip that can continue growth',
                    'They remove only roots',
                    'They are illegal on fruit trees',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Pruning during long wet weather is often discouraged because:',
                'choices' => [
                    'Rain makes tools too light to hold',
                    'Moisture can favor spore spread and infection of fresh wounds',
                    'Trees absorb no water when it rains',
                    'Leaves grow faster in rain',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Renewal pruning of cane-type berries (e.g., removing oldest fruiting canes) aims to:',
                'choices' => [
                    'Eliminate all new growth every year',
                    'Keep a mix of one-year and older productive wood depending on species habit',
                    'Stop berries from ripening',
                    'Convert canes into roots',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Espalier training (fruit trees pruned flat against a support) mainly depends on:',
                'choices' => [
                    'Never pruning after planting',
                    'Regular heading and tying to direct growth along wires or frames',
                    'Removing all leaves monthly',
                    'Grafting new roots each year',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Young citrus trees are often shaped to:',
                'choices' => [
                    'A single pencil-thin whip with no side branches',
                    'An open center or low scaffold system for light inside the canopy',
                    'A hollow trunk for drainage',
                    'Maximum height in the first month only',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Tip pruning (pinching or light heading) young mango seedlings can help:',
                'choices' => [
                    'Prevent any lateral branches from forming',
                    'Encourage branching and a manageable spreading frame',
                    'Stop the taproot from growing',
                    'Eliminate flowering for life',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'When pruning avocado, very heavy opening of the canopy in hot climates can:',
                'choices' => [
                    'Always increase yield the same season',
                    'Sunburn bark and fruit on exposed limbs; lighter, staged pruning is safer',
                    'Remove the need for mulch',
                    'Guarantee thicker peel on every fruit',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'A pole saw or long-reach pruner is appropriate when:',
                'choices' => [
                    'You can reach the cut safely from the ground or a stable platform',
                    'You climb the tree without ropes to save time',
                    'The branch is under 5 mm thick only',
                    'You want to shave bark off the trunk',
                ],
                'correctChoiceIndex' => 0,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'After pruning out fire blight–infected wood in pome fruit, tools should be:',
                'choices' => [
                    'Wiped on grass only',
                    'Disinfected between cuts (e.g., alcohol or dilute bleach) to limit spread',
                    'Heated red-hot on every cut',
                    'Left dirty to build immunity',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Root suckers from the rootstock far from the graft should be:',
                'choices' => [
                    'Left to compete with the scion',
                    'Removed so energy goes to the named variety on top',
                    'Topped but not removed',
                    'Used as understory trees without grafting',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Why is “topping” (random heavy heading of large limbs) considered poor practice?',
                'choices' => [
                    'It produces weak, crowded sprouts and decay-prone stubs',
                    'It is the same as scientific pollarding on every species',
                    'It always increases fruit size in one year',
                    'It is required for organic certification',
                ],
                'correctChoiceIndex' => 0,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Pruning for training (first years after planting) focuses on:',
                'choices' => [
                    'Removing every flower the tree ever makes',
                    'Selecting scaffold angles, height, and spacing before heavy cropping years',
                    'Burying the graft union deeper each season',
                    'Avoiding any cuts until the tree is 20 years old',
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
