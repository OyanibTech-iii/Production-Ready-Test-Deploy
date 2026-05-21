<?php

namespace App\DataFixtures;

use App\Entity\AnswerChoice;
use App\Entity\Course;
use App\Entity\Question;
use App\Entity\Quiz;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Aligns with DB seed: quiz id 3, course_id 3 — "Grafting Introduction".
 * Question bank follows course PDFs (Modules 1–5 grafting).
 */
class GraftingFixtures extends Fixture
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
                'courseName' => 'Grafting',
                'description' => 'Harvest within a short period of time',
                'tier' => 'Free',
                'price' => '0.00',
                'thumbnail' => 'grafting_tumbnail-69cb5fc8225fa.png',
            ],
        ];
    }

    /**
     * @return array<int, array{sqlId:int, courseSqlId:int, title:string, passingScore:int}>
     */
    private function getQuizRows(): array
    {
        return [
            ['sqlId' => 1, 'courseSqlId' => 1, 'title' => 'Grafting Introduction', 'passingScore' => 75],
        ];
    }

    /**
     * @return array<int, array{quizSqlId:int, questionText:string, choices:array<int, string>, correctChoiceIndex:int}>
     */
    private function getQuestionRows(): array
    {
        return $this->getGraftingQuestionRows();
    }

    /**
     * @return array<int, array{quizSqlId:int, questionText:string, choices:array<int, string>, correctChoiceIndex:int}>
     */
    private function getGraftingQuestionRows(): array
    {
        $q = 1;

        return [
            [
                'quizSqlId' => $q,
                'questionText' => 'Grafting joins two plant parts so they grow as one. The upper part that becomes stems, leaves, flowers, and fruit is the:',
                'choices' => [
                    'Scion',
                    'Rootstock',
                    'Rhizome',
                    'Stolon',
                ],
                'correctChoiceIndex' => 0,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'The lower part that provides roots, support, and often disease resistance or size control is the:',
                'choices' => [
                    'Scion',
                    'Rootstock',
                    'Petiole',
                    'Stipule',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'For a graft to heal, the cambium layers of stock and scion should be:',
                'choices' => [
                    'Left apart to air-dry',
                    'Properly aligned so they can knit and reconnect vascular tissue',
                    'Removed before joining',
                    'Coated only; contact does not matter',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Compared with growing from seed alone, grafting is especially useful when growers need to:',
                'choices' => [
                    'Introduce more random genetic variation every generation',
                    'Eliminate the need for photosynthesis',
                    'Combine desired scion traits with strong roots or resistance from the rootstock',
                    'Avoid any contact between living tissues',
                ],
                'correctChoiceIndex' => 2,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Genetic compatibility in grafting means that success is generally highest when:',
                'choices' => [
                    'Any unrelated species can be joined with enough tape',
                    'Scion and rootstock are the same species or closely related',
                    'Only annual herbs are used',
                    'Only the roots need to match; scion variety does not matter',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'As an example of compatibility, apple varieties are typically grafted onto apple rootstocks, not onto unrelated crops such as:',
                'choices' => [
                    'Another apple cultivar',
                    'Pear on special interstocks only',
                    'Dwarf apple rootstocks',
                    'Mango or other unrelated species',
                ],
                'correctChoiceIndex' => 3,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'A whip or splice graft is best suited when:',
                'choices' => [
                    'The rootstock is much thicker than the scion',
                    'Scion and rootstock are similar in diameter',
                    'Only a single bud is available',
                    'The plant has no cambium',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'A cleft (wedge) graft is especially appropriate when:',
                'choices' => [
                    'Stock and scion are pencil-thin and the same size',
                    'The rootstock branch is thicker than the scion',
                    'You are grafting only grasses',
                    'No sealant or wrap is used',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Bud grafting (budding) differs from a stem scion graft mainly because it:',
                'choices' => [
                    'Never requires cambium contact',
                    'Uses a single bud (often with a thin sliver of wood) as the scion piece',
                    'Only works on dormant hardwood in mid-winter freeze',
                    'Replaces the need for any rootstock',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Essential grafting tools and supplies from the course include a sharp grafting knife, wrapping material, sealant, and:',
                'choices' => [
                    'Dull blades for slower cuts',
                    'Sanitizers such as alcohol or dilute bleach for tools',
                    'Only household scissors shared between diseased and healthy plants',
                    'No need to clean tools between plants',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Why must grafting knives and blades be sharp and clean?',
                'choices' => [
                    'Crushed, torn tissue heals faster than smooth cuts',
                    'Smooth cuts expose healthy cambium and reduce disease spread between plants',
                    'Dull tools always improve cambium alignment',
                    'Rust helps seal the union',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Pruning shears (secateurs) in grafting prep are mainly used to:',
                'choices' => [
                    'Cut branches and help collect scion wood before detailed knife work',
                    'Replace the grafting knife for all final union cuts',
                    'Sterilize the cambium chemically',
                    'Remove the rootstock entirely',
                ],
                'correctChoiceIndex' => 0,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'When selecting scion wood, you should prefer material that is:',
                'choices' => [
                    'From visibly diseased branches for hardiness',
                    'Healthy, free of pests, mature but not overly old, from a desirable variety',
                    'Fully dry and stored for months without moisture',
                    'Taken only from the rootstock suckers',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Good timing for many grafts is when cambium is active—often around early spring bud swell—while you should generally avoid:',
                'choices' => [
                    'Mild weather and active healing',
                    'Extreme heat that dries tissue quickly and freezing that stops healing',
                    'Working in partial shade for tender new grafts',
                    'Joining pieces soon after cuts',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'An ideal grafting workspace is described as:',
                'choices' => [
                    'Dark, dusty, and crowded',
                    'Clean, well lit, organized, with disinfected surfaces and moist (not soggy) plant material',
                    'Outdoor only in driving wind with no cover',
                    'Shared with no tool cleaning between batches',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Basic safety with a grafting knife includes:',
                'choices' => [
                    'Cutting toward your body for control',
                    'Storing open blades loose in a pile',
                    'Cutting away from the body, firm grip, and avoiding distractions',
                    'Working fastest without planning the cut',
                ],
                'correctChoiceIndex' => 2,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'After joining scion and rootstock, wrapping should hold the cambium in contact yet:',
                'choices' => [
                    'Be so loose that pieces move freely',
                    'Not be so tight long-term that it girdles the swelling union',
                    'Eliminate all oxygen forever',
                    'Never be loosened or adjusted as the plant grows',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'In whip (splice) grafting, the rootstock and scion typically receive:',
                'choices' => [
                    'Random jagged breaks',
                    'Matching long, smooth slanted cuts so the pieces fit like puzzle pieces',
                    'Only a shallow scrape with no angle',
                    'A square punch with no cambium exposed',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'The whip-and-tongue variation adds a small interlocking “tongue” cut to both pieces mainly to:',
                'choices' => [
                    'Prevent any cambium contact',
                    'Increase stability and strength of the union',
                    'Stop callus from forming',
                    'Eliminate the need for wrapping',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'In cleft grafting, when stock is much thicker than the scion, cambium alignment should focus on:',
                'choices' => [
                    'Neither side; bark only',
                    'At least one side where cambium lines up well',
                    'Only the pith of the stem',
                    'Removing all cambium first',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'In T-budding, the rootstock bark is prepared with:',
                'choices' => [
                    'A single vertical slit only',
                    'Complete removal of all bark from the trunk',
                    'A hole drilled through the wood',
                    'A T-shaped cut; bark flaps are lifted and the bud is slid underneath',
                ],
                'correctChoiceIndex' => 3,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'After a bud graft takes and the bud begins growing, the course recommends you:',
                'choices' => [
                    'Let the rootstock top compete indefinitely',
                    'Cut back the rootstock above the bud so energy feeds the new shoot',
                    'Remove the bud to strengthen the rootstock',
                    'Never remove any growth from the plant',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Common execution mistakes that cause graft failure include:',
                'choices' => [
                    'Aligning cambium and wrapping soon after cuts',
                    'Cambium misalignment, loose wraps, rough cuts, or letting the scion dry before joining',
                    'Using sealant on exposed cuts',
                    'Choosing compatible stock and scion',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Signs that a graft is likely succeeding include:',
                'choices' => [
                    'Scion shriveling and blackening',
                    'Green scion tissue, bud swell or new growth, and callus at the union',
                    'Only rootstock suckers with a dead scion',
                    'A loose, moving union with no healing',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Early healing right after the graft depends on keeping cambium pressed together and:',
                'choices' => [
                    'Maximizing moisture loss from the cut surfaces',
                    'Retaining moisture (wrap, sealant) so tissue does not dry before callus forms',
                    'Exposing the union to constant high wind without support',
                    'Avoiding any contact between stock and scion',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Callus tissue at the graft union mainly helps by:',
                'choices' => [
                    'Drying the wound only',
                    'Bridging and reconnecting vascular tissues as healing progresses',
                    'Preventing any new cell division',
                    'Replacing the need for cambium alignment',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Shoots (suckers) growing from the rootstock below the graft should often be removed because they:',
                'choices' => [
                    'Always carry the same fruit as the scion',
                    'Compete for water and nutrients and steal energy from the grafted variety',
                    'Are required for pollination',
                    'Prove the graft has failed',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'As the graft union thickens, wraps may need to be loosened or removed to:',
                'choices' => [
                    'Stop all diameter increase',
                    'Prevent girdling as the stem expands',
                    'Keep the union dark forever',
                    'Kill emerging buds',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Topworking means:',
                'choices' => [
                    'Removing all roots each season',
                    'Grafting two root systems together only',
                    'Changing the variety on an existing tree by grafting new scions onto mature branches',
                    'Avoiding any cuts above knee height',
                ],
                'correctChoiceIndex' => 2,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'An interstock (intermediate stem piece) between rootstock and scion can help when:',
                'choices' => [
                    'You want to eliminate cambium entirely',
                    'Direct stock–scion union is difficult, or you want added compatibility or size control',
                    'The plant has no vascular system',
                    'Only herbs are involved',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Viruses and diseases spread through grafting especially when:',
                'choices' => [
                    'Tools are disinfected between every cut',
                    'Infected scion or stock is used, or tools move between plants without sanitation',
                    'The union is wrapped firmly',
                    'Cambium layers are aligned',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'According to the technique selection guide, limited scion material points toward:',
                'choices' => [
                    'Always cleft graft on large logs',
                    'Budding',
                    'Only whip graft on thick trunks',
                    'Skipping cambium alignment',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Dwarfing rootstocks are valued in urban and intensive plantings because they tend to produce:',
                'choices' => [
                    'No roots at all',
                    'Smaller, more manageable trees than the same scion on a very vigorous stock',
                    'Fruit with no need for light',
                    'Automatic disease immunity without hygiene',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'For high success rates, the course emphasizes working quickly after cuts mainly to:',
                'choices' => [
                    'Avoid aligning cambium',
                    'Limit drying and exposure of cambium before the union is joined and sealed',
                    'Ensure tools stay dirty',
                    'Delay wrapping until the scion wilts',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Scaling grafting in production often includes standardizing methods, training workers, batch prep, and:',
                'choices' => [
                    'Never recording which combinations were used',
                    'Record keeping of techniques, combinations, and success rates',
                    'Using one dull knife for all crews',
                    'Avoiding controlled nursery environments',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Ethical, sustainable grafting practices include using healthy material, preserving diversity, and:',
                'choices' => [
                    'Over-harvesting all scion wood from one mother plant',
                    'Avoiding disease spread and overexploitation of scion sources',
                    'Discarding sanitation to save time',
                    'Using only a single clone worldwide',
                ],
                'correctChoiceIndex' => 1,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Graft failure may appear as:',
                'choices' => [
                    'Strong union with green scion growth only',
                    'Immediate heavy fruit on day one',
                    'Faster leaf expansion with firm attachment only',
                    'Brown or black scion, no bud growth, weak union, or excessive rootstock shoots',
                ],
                'correctChoiceIndex' => 3,
            ],
            [
                'quizSqlId' => $q,
                'questionText' => 'Real-world roles of grafting mentioned in the course include fruit tree production, ornamentals (e.g. roses), commercial uniformity, and:',
                'choices' => [
                    'Eliminating all seed use globally',
                    'Conserving rare varieties and enabling small-space gardening with dwarf stocks',
                    'Replacing photosynthesis in roots',
                    'Guaranteeing any two species will unite',
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
