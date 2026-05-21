<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    private ?Quiz $quiz = null;

    #[ORM\Column(length: 255)]
    private ?string $questionText = null;

    #[ORM\Column]
    private ?int $points = null;

    /**
     * @var Collection<int, AnswerChoice>
     */
    #[ORM\OneToMany(targetEntity: AnswerChoice::class, mappedBy: 'question')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $answerChoices;

    public function __construct()
    {
        $this->answerChoices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;

        return $this;
    }

    public function getQuestionText(): ?string
    {
        return $this->questionText;
    }

    public function setQuestionText(string $questionText): static
    {
        $this->questionText = $questionText;

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    /**
     * @return Collection<int, AnswerChoice>
     */
    public function getAnswerChoices(): Collection
    {
        return $this->answerChoices;
    }

    public function addAnswerChoice(AnswerChoice $answerChoice): static
    {
        if (!$this->answerChoices->contains($answerChoice)) {
            $this->answerChoices->add($answerChoice);
            $answerChoice->setQuestion($this);
        }

        return $this;
    }

    public function removeAnswerChoice(AnswerChoice $answerChoice): static
    {
        if ($this->answerChoices->removeElement($answerChoice)) {
            // set the owning side to null (unless already changed)
            if ($answerChoice->getQuestion() === $this) {
                $answerChoice->setQuestion(null);
            }
        }

        return $this;
    }
}
